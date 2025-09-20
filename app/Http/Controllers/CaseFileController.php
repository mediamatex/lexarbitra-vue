<?php

namespace App\Http\Controllers;

use App\Models\CaseReference;
use App\Services\CaseDatabaseService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CaseFileController extends Controller
{
    public function __construct(
        private CaseDatabaseService $caseDatabaseService
    ) {}

    public function index(): Response
    {
        $caseReferences = CaseReference::latest()->paginate(15);

        // Load case data from tenant databases
        $cases = [];
        foreach ($caseReferences as $reference) {
            if ($reference->tenant_case_id) {
                $connectionName = $this->caseDatabaseService->switchToCaseDatabase($reference);

                if ($connectionName) {
                    try {
                        $tenantCase = \DB::connection($connectionName)
                            ->table('case_files')
                            ->where('id', $reference->tenant_case_id)
                            ->first();

                        if ($tenantCase) {
                            // Merge tenant data with reference info
                            $case = (object) array_merge((array) $tenantCase, [
                                'reference_id' => $reference->id,
                                'database_name' => $reference->database_name,
                                'connection_name' => $reference->connection_name,
                            ]);
                            $cases[] = $case;
                        }
                    } catch (\Exception $e) {
                        logger()->warning('Failed to load tenant case data for index', [
                            'reference_id' => $reference->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        return Inertia::render('CaseFiles/Index', [
            'cases' => [
                'data' => $cases,
                'current_page' => $caseReferences->currentPage(),
                'last_page' => $caseReferences->lastPage(),
                'per_page' => $caseReferences->perPage(),
                'total' => $caseReferences->total(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('CaseFiles/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'case_number' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'dispute_value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'initiated_at' => 'required|date',
            'jurisdiction' => 'nullable|string',
            'case_category' => 'nullable|string',
            'complexity_level' => 'nullable|string',
            'urgency_level' => 'nullable|string',
        ]);

        // Create case database and reference in one step
        try {
            $caseReference = $this->caseDatabaseService->createCaseDatabase([]);

            // Switch to the case database and run migrations
            $connectionName = $this->caseDatabaseService->switchToCaseDatabase($caseReference);

            if ($connectionName) {
                try {
                    // Test tenant database connection first
                    logger()->info('Testing tenant database connection', [
                        'connection_name' => $connectionName,
                        'case_id' => $caseReference->id,
                    ]);

                    $testConnection = \DB::connection($connectionName)->getPdo();
                    logger()->info('Tenant database connection test successful', [
                        'connection_name' => $connectionName,
                        'driver' => $testConnection->getAttribute(\PDO::ATTR_DRIVER_NAME),
                    ]);

                    // Run migrations on the case database
                    logger()->info('Running migrations on tenant database', [
                        'connection_name' => $connectionName,
                    ]);

                    // Force clear any cached connections first
                    \DB::purge($connectionName);

                    $migrateResult = \Artisan::call('migrate', [
                        '--database' => $connectionName,
                        '--force' => true,
                        '--path' => 'database/migrations/tenant',
                    ]);

                    $migrateOutput = \Artisan::output();
                    logger()->info('Migration result', [
                        'result' => $migrateResult,
                        'output' => $migrateOutput,
                    ]);

                    // Check what tables exist after migration
                    $tables = \DB::connection($connectionName)->select('SHOW TABLES');
                    logger()->info('Tables in tenant database after migration', [
                        'tables' => $tables,
                        'connection_name' => $connectionName,
                    ]);

                    // Check if case_files table exists in tenant database
                    $tablesExist = \Schema::connection($connectionName)->hasTable('case_files');
                    logger()->info('Tenant database table check', [
                        'case_files_exists' => $tablesExist,
                        'connection_name' => $connectionName,
                    ]);

                    if (! $tablesExist) {
                        throw new \Exception('case_files table does not exist in tenant database after migration. Migration output: '.$migrateOutput);
                    }

                    // Now create the REAL case file directly in the tenant database using Query Builder
                    logger()->info('Creating case file for tenant database', [
                        'connection_name' => $connectionName,
                        'validated_data' => $validated,
                    ]);

                    // Prepare the data for insertion
                    $tenantCaseData = [
                        'id' => \Str::uuid(),
                        'case_number' => $validated['case_number'],
                        'title' => $validated['title'],
                        'description' => $validated['description'] ?? null,
                        'status' => 'active',
                        'dispute_value' => $validated['dispute_value'] ?? null,
                        'currency' => $validated['currency'] ?? null,
                        'initiated_at' => $validated['initiated_at'] ?? now(),
                        'jurisdiction' => $validated['jurisdiction'] ?? null,
                        'case_category' => $validated['case_category'] ?? null,
                        'complexity_level' => $validated['complexity_level'] ?? null,
                        'urgency_level' => $validated['urgency_level'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Only add created_by if the column exists in tenant database
                    if (\Schema::connection($connectionName)->hasColumn('case_files', 'created_by')) {
                        $tenantCaseData['created_by'] = auth()->id();
                        logger()->info('Set created_by for tenant case', ['created_by' => auth()->id()]);
                    }

                    logger()->info('Inserting case data directly to tenant database', [
                        'connection_name' => $connectionName,
                        'case_data' => $tenantCaseData,
                    ]);

                    // Use Query Builder to force insertion into tenant database
                    $insertResult = \DB::connection($connectionName)
                        ->table('case_files')
                        ->insert($tenantCaseData);

                    $tenantCaseId = $tenantCaseData['id'];

                    logger()->info('Case data inserted to tenant database', [
                        'tenant_case_id' => $tenantCaseId,
                        'connection_name' => $connectionName,
                        'insert_result' => $insertResult,
                    ]);

                    // Verify the case was actually saved in tenant database
                    $verifyCase = \DB::connection($connectionName)
                        ->table('case_files')
                        ->where('id', $tenantCaseId)
                        ->first();

                    logger()->info('Verification: Case in tenant database', [
                        'found_in_tenant' => $verifyCase ? 'YES' : 'NO',
                        'case_data' => $verifyCase,
                    ]);

                    // Update the case reference with tenant case ID
                    $caseReference->update([
                        'tenant_case_id' => $tenantCaseId,
                        'status' => 'active', // Update status once tenant case is created
                    ]);

                    logger()->info('Case reference updated with tenant case ID', [
                        'case_reference_id' => $caseReference->id,
                        'tenant_case_id' => $tenantCaseId,
                    ]);

                    // Return the case reference
                    $caseFile = $caseReference;

                } catch (\Exception $e) {
                    logger()->error('Failed to setup tenant database or save data', [
                        'connection_name' => $connectionName,
                        'case_id' => $caseReference->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    // Continue without failing the entire case creation
                    // The case will exist in landlord DB even if tenant setup fails
                    $caseFile = $caseReference;
                }
            } else {
                $caseFile = $caseReference;
            }

        } catch (\Exception $e) {
            // If database creation fails, clean up and show error
            if (isset($caseReference)) {
                $caseReference->delete();
            }

            logger()->error('Failed to create case database', [
                'case_number' => $validated['case_number'],
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['database' => 'Fehler beim Erstellen der Falldatenbank: '.$e->getMessage()]);
        }

        return redirect()->route('cases.show', $caseFile)
            ->with('success', 'Falldatei erfolgreich erstellt.');
    }

    public function show(CaseReference $caseReference): Response
    {
        $caseFile = null;

        if ($caseReference->tenant_case_id) {
            $connectionName = $this->caseDatabaseService->switchToCaseDatabase($caseReference);

            if ($connectionName) {
                try {
                    $tenantCase = \DB::connection($connectionName)
                        ->table('case_files')
                        ->where('id', $caseReference->tenant_case_id)
                        ->first();

                    if ($tenantCase) {
                        // Merge tenant data with reference info
                        $caseFile = (object) array_merge((array) $tenantCase, [
                            'reference_id' => $caseReference->id,
                            'database_name' => $caseReference->database_name,
                            'connection_name' => $caseReference->connection_name,
                        ]);
                    }
                } catch (\Exception $e) {
                    logger()->error('Failed to load tenant case data', [
                        'case_reference_id' => $caseReference->id,
                        'tenant_case_id' => $caseReference->tenant_case_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if (! $caseFile) {
            abort(404, 'Case file not found or tenant database unavailable');
        }

        return Inertia::render('CaseFiles/Show', [
            'caseFile' => $caseFile,
            'caseReference' => $caseReference,
        ]);
    }

    public function edit(CaseReference $case): Response
    {
        $caseFile = null;

        if ($case->tenant_case_id) {
            $connectionName = $this->caseDatabaseService->switchToCaseDatabase($case);

            if ($connectionName) {
                try {
                    $tenantCase = \DB::connection($connectionName)
                        ->table('case_files')
                        ->where('id', $case->tenant_case_id)
                        ->first();

                    if ($tenantCase) {
                        // Merge tenant data with reference info for form submission
                        $caseFile = (object) array_merge((array) $tenantCase, [
                            'reference_id' => $case->id, // For form submission
                            'database_name' => $case->database_name,
                            'connection_name' => $case->connection_name,
                        ]);
                    }
                } catch (\Exception $e) {
                    logger()->error('Failed to load tenant case data for editing', [
                        'case_reference_id' => $case->id,
                        'tenant_case_id' => $case->tenant_case_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if (! $caseFile) {
            abort(404, 'Case file not found or tenant database unavailable');
        }

        return Inertia::render('CaseFiles/Edit', [
            'caseFile' => $caseFile,
            'caseReference' => $case,
        ]);
    }

    public function update(Request $request, CaseReference $case)
    {
        $validated = $request->validate([
            'case_number' => 'required|string',
            'title' => 'required|string|max:255',
            'status' => 'nullable|string',
        ]);

        if (! $case->tenant_case_id) {
            abort(404, 'Case file not found or tenant database unavailable');
        }

        $connectionName = $this->caseDatabaseService->switchToCaseDatabase($case);

        if (! $connectionName) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['database' => 'Fehler beim Verbinden zur Falldatenbank']);
        }

        try {
            // Update only in tenant database
            $updated = \DB::connection($connectionName)
                ->table('case_files')
                ->where('id', $case->tenant_case_id)
                ->update([
                    'case_number' => $validated['case_number'],
                    'title' => $validated['title'],
                    'status' => $validated['status'],
                    'updated_at' => now(),
                ]);

            if (! $updated) {
                throw new \Exception('No case record was updated in tenant database');
            }

            logger()->info('Case updated successfully in tenant database', [
                'case_reference_id' => $case->id,
                'tenant_case_id' => $case->tenant_case_id,
                'connection' => $connectionName,
            ]);

            return redirect()->route('cases.show', $case)
                ->with('success', 'Falldatei erfolgreich aktualisiert.');

        } catch (\Exception $e) {
            logger()->error('Failed to update case in tenant database', [
                'case_reference_id' => $case->id,
                'tenant_case_id' => $case->tenant_case_id,
                'connection' => $connectionName,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['database' => 'Fehler beim Aktualisieren der Falldatei: '.$e->getMessage()]);
        }
    }

    public function destroy(CaseReference $caseReference)
    {
        // Delete case database
        try {
            $this->caseDatabaseService->deleteCaseDatabase($caseReference);
        } catch (\Exception $e) {
            logger()->error('Failed to delete case database', [
                'case_reference_id' => $caseReference->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('cases.index')
            ->with('success', 'Falldatei erfolgreich gelöscht.');
    }

    public function testDatabase(CaseReference $caseReference)
    {
        $result = $this->caseDatabaseService->testCaseDatabase($caseReference);

        return response()->json($result);
    }
}
