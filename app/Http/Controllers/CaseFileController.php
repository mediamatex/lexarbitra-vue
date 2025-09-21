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
            $case = null;

            if ($reference->tenant_case_id) {
                // Case has tenant data - try to load it
                $connectionName = $this->caseDatabaseService->switchToCaseDatabase($reference);

                if ($connectionName) {
                    try {
                        $tenantCase = \DB::connection($connectionName)
                            ->table('case_files')
                            ->where('id', $reference->tenant_case_id)
                            ->first();

                        if ($tenantCase) {
                            // Merge tenant data with reference info
                            // IMPORTANT: Use case reference ID as the main ID for routing
                            $case = (object) array_merge((array) $tenantCase, [
                                'id' => $reference->id, // Case reference ID for routing
                                'tenant_case_id' => $tenantCase->id, // Original tenant case ID
                                'reference_id' => $reference->id, // Alias for clarity
                                'database_name' => $reference->database_name,
                                'connection_name' => $reference->connection_name,
                            ]);
                        }
                    } catch (\Exception $e) {
                        logger()->warning('Failed to load tenant case data for index', [
                            'reference_id' => $reference->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // If no tenant data found or failed to load, create fallback case from reference
            if (!$case) {
                $case = (object) [
                    'id' => $reference->id, // Case reference ID for routing
                    'case_number' => 'CASE-' . substr($reference->id, 0, 8), // Generate fallback case number
                    'title' => 'Unvollständiger Fall', // Placeholder title
                    'status' => 'draft', // Default status
                    'initiated_at' => $reference->created_at, // Use reference creation date
                    'tenant_case_id' => $reference->tenant_case_id,
                    'reference_id' => $reference->id,
                    'database_name' => $reference->database_name,
                    'is_incomplete' => true, // Flag to identify incomplete cases
                ];
            }

            $cases[] = $case;
        }

        // Switch back to main database after processing all cases
        $this->caseDatabaseService->switchBackToMainDatabase();

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
            $case = $this->caseDatabaseService->createCaseDatabase();

            // Switch to the case database and run migrations
            $connectionName = $this->caseDatabaseService->switchToCaseDatabase($case);

            if ($connectionName) {
                try {
                    // Test tenant database connection first
                    logger()->info('Testing tenant database connection', [
                        'connection_name' => $connectionName,
                        'case_id' => $case->id,
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

                    // Check what tables exist after migration using database-agnostic method
                    try {
                        $tableNames = \Schema::connection($connectionName)->getTableListing();
                        logger()->info('Tables in tenant database after migration', [
                            'tables' => $tableNames,
                            'connection_name' => $connectionName,
                        ]);
                    } catch (\Exception $e) {
                        logger()->warning('Could not list tables after migration', [
                            'connection_name' => $connectionName,
                            'error' => $e->getMessage(),
                        ]);
                    }

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
                    try {
                        if (\Schema::connection($connectionName)->hasColumn('case_files', 'created_by')) {
                            $tenantCaseData['created_by'] = auth()->id();
                            logger()->info('Set created_by for tenant case', ['created_by' => auth()->id()]);
                        } else {
                            logger()->info('created_by column does not exist in tenant database');
                        }
                    } catch (\Exception $e) {
                        logger()->warning('Could not check for created_by column', [
                            'error' => $e->getMessage(),
                            'connection_name' => $connectionName,
                        ]);
                        // Continue without created_by
                    }

                    logger()->info('Inserting case data directly to tenant database', [
                        'connection_name' => $connectionName,
                        'case_data' => $tenantCaseData,
                    ]);

                    // Use Query Builder to force insertion into tenant database
                    try {
                        $insertResult = \DB::connection($connectionName)
                            ->table('case_files')
                            ->insert($tenantCaseData);

                        $tenantCaseId = $tenantCaseData['id'];

                        logger()->info('Case data inserted to tenant database', [
                            'tenant_case_id' => $tenantCaseId,
                            'connection_name' => $connectionName,
                            'insert_result' => $insertResult,
                        ]);
                    } catch (\Exception $e) {
                        logger()->error('Failed to insert case data to tenant database', [
                            'connection_name' => $connectionName,
                            'error' => $e->getMessage(),
                            'case_data' => $tenantCaseData,
                        ]);
                        throw $e;
                    }

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
                    try {
                        $updateResult = $case->update([
                            'tenant_case_id' => $tenantCaseId,
                        ]);

                        logger()->info('Case reference updated with tenant case ID', [
                            'case_reference_id' => $case->id,
                            'tenant_case_id' => $tenantCaseId,
                            'update_result' => $updateResult,
                        ]);
                    } catch (\Exception $e) {
                        logger()->error('Failed to update case reference with tenant case ID', [
                            'case_reference_id' => $case->id,
                            'tenant_case_id' => $tenantCaseId,
                            'error' => $e->getMessage(),
                        ]);
                        throw $e;
                    }

                    // Return the case reference
                    $caseFile = $case;

                    // Switch back to main database
                    $this->caseDatabaseService->switchBackToMainDatabase();

                } catch (\Exception $e) {
                    logger()->error('Failed to setup tenant database or save data', [
                        'connection_name' => $connectionName,
                        'case_id' => $case->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    // Continue without failing the entire case creation
                    // The case will exist in landlord DB even if tenant setup fails
                    $caseFile = $case;

                    // Switch back to main database even if tenant setup failed
                    $this->caseDatabaseService->switchBackToMainDatabase();
                }
            } else {
                $caseFile = $case;
            }

        } catch (\Exception $e) {
            // If database creation fails, clean up and show error
            if (isset($case)) {
                $case->delete();
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

    public function show(CaseReference $case): Response
    {
        logger()->info('CaseFileController::show - Start', [
            'case_reference_id' => $case->id,
            'url' => request()->url(),
            'method' => request()->method(),
            'user_id' => auth()->id(),
        ]);

        logger()->info('CaseFileController::show - Case reference details', [
            'case_reference_id' => $case->id,
            'tenant_case_id' => $case->tenant_case_id,
            'database_name' => $case->database_name,
            'database_host' => $case->database_host,
            'connection_name' => $case->connection_name,
            'is_active' => $case->is_active,
        ]);

        $caseFile = null;

        if ($case->tenant_case_id) {
            logger()->info('CaseFileController::show - Has tenant_case_id, attempting to load tenant data', [
                'case_reference_id' => $case->id,
                'tenant_case_id' => $case->tenant_case_id,
            ]);

            $connectionName = $this->caseDatabaseService->switchToCaseDatabase($case);

            logger()->info('CaseFileController::show - Database switch result', [
                'case_reference_id' => $case->id,
                'connection_name' => $connectionName,
                'success' => $connectionName ? true : false,
            ]);

            if ($connectionName) {
                try {
                    logger()->info('CaseFileController::show - Attempting to query tenant database', [
                        'case_reference_id' => $case->id,
                        'connection_name' => $connectionName,
                        'tenant_case_id' => $case->tenant_case_id,
                        'database_host' => $case->database_host,
                        'file_exists' => file_exists($case->database_host),
                    ]);

                    $tenantCase = \DB::connection($connectionName)
                        ->table('case_files')
                        ->where('id', $case->tenant_case_id)
                        ->first();

                    logger()->info('CaseFileController::show - Tenant query result', [
                        'case_reference_id' => $case->id,
                        'tenant_case_found' => $tenantCase ? true : false,
                        'tenant_case_data' => $tenantCase ? (array) $tenantCase : null,
                    ]);

                    if ($tenantCase) {
                        // Merge tenant data with reference info
                        // IMPORTANT: Use case reference ID as the main ID for routing
                        $caseFile = (object) array_merge((array) $tenantCase, [
                            'id' => $case->id, // Case reference ID for routing
                            'tenant_case_id' => $tenantCase->id, // Original tenant case ID
                            'reference_id' => $case->id,
                            'database_name' => $case->database_name,
                            'connection_name' => $case->connection_name,
                        ]);

                        logger()->info('CaseFileController::show - Case file object created successfully', [
                            'case_reference_id' => $case->id,
                            'case_file_title' => $caseFile->title ?? 'No title',
                        ]);
                    }
                } catch (\Exception $e) {
                    logger()->error('CaseFileController::show - Failed to load tenant case data', [
                        'case_reference_id' => $case->id,
                        'tenant_case_id' => $case->tenant_case_id,
                        'connection_name' => $connectionName,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            } else {
                logger()->warning('CaseFileController::show - Database switch failed', [
                    'case_reference_id' => $case->id,
                    'database_name' => $case->database_name,
                    'database_host' => $case->database_host,
                ]);
            }
        } else {
            logger()->warning('CaseFileController::show - No tenant_case_id found', [
                'case_reference_id' => $case->id,
                'database_name' => $case->database_name,
                'created_at' => $case->created_at,
            ]);
        }

        if (! $caseFile) {
            logger()->error('CaseFileController::show - About to abort with 404', [
                'case_reference_id' => $case->id,
                'tenant_case_id' => $case->tenant_case_id,
                'has_tenant_case_id' => $case->tenant_case_id ? true : false,
                'reason' => $case->tenant_case_id ? 'Tenant case not found or database unavailable' : 'No tenant_case_id set',
            ]);

            abort(404, 'Case file not found or tenant database unavailable');
        }

        // Switch back to main database after reading tenant data
        $this->caseDatabaseService->switchBackToMainDatabase();

        logger()->info('CaseFileController::show - Successfully returning case file view', [
            'case_reference_id' => $case->id,
            'case_file_title' => $caseFile->title ?? 'No title',
        ]);

        return Inertia::render('CaseFiles/Show', [
            'caseFile' => $caseFile,
            'caseReference' => $case,
        ]);
    }

    public function edit(CaseReference $case): Response
    {
        logger()->info('CaseFileController::edit - Start', [
            'case_reference_id' => $case->id,
            'url' => request()->url(),
            'method' => request()->method(),
            'user_id' => auth()->id(),
        ]);

        logger()->info('CaseFileController::edit - Case reference details', [
            'case_reference_id' => $case->id,
            'tenant_case_id' => $case->tenant_case_id,
            'database_name' => $case->database_name,
            'database_host' => $case->database_host,
            'connection_name' => $case->connection_name,
            'is_active' => $case->is_active,
        ]);

        $caseFile = null;

        if ($case->tenant_case_id) {
            logger()->info('CaseFileController::edit - Has tenant_case_id, attempting to load tenant data', [
                'case_reference_id' => $case->id,
                'tenant_case_id' => $case->tenant_case_id,
            ]);

            $connectionName = $this->caseDatabaseService->switchToCaseDatabase($case);

            logger()->info('CaseFileController::edit - Database switch result', [
                'case_reference_id' => $case->id,
                'connection_name' => $connectionName,
                'success' => $connectionName ? true : false,
            ]);

            if ($connectionName) {
                try {
                    logger()->info('CaseFileController::edit - Attempting to query tenant database', [
                        'case_reference_id' => $case->id,
                        'connection_name' => $connectionName,
                        'tenant_case_id' => $case->tenant_case_id,
                        'database_host' => $case->database_host,
                        'file_exists' => file_exists($case->database_host),
                    ]);

                    $tenantCase = \DB::connection($connectionName)
                        ->table('case_files')
                        ->where('id', $case->tenant_case_id)
                        ->first();

                    logger()->info('CaseFileController::edit - Tenant query result', [
                        'case_reference_id' => $case->id,
                        'tenant_case_found' => $tenantCase ? true : false,
                        'tenant_case_data' => $tenantCase ? (array) $tenantCase : null,
                    ]);

                    if ($tenantCase) {
                        // Merge tenant data with reference info for form submission
                        // IMPORTANT: Use case reference ID as the main ID for routing
                        $caseFile = (object) array_merge((array) $tenantCase, [
                            'id' => $case->id, // Case reference ID for routing
                            'tenant_case_id' => $tenantCase->id, // Original tenant case ID
                            'reference_id' => $case->id, // For form submission
                            'database_name' => $case->database_name,
                            'connection_name' => $case->connection_name,
                        ]);

                        logger()->info('CaseFileController::edit - Case file object created successfully', [
                            'case_reference_id' => $case->id,
                            'case_file_title' => $caseFile->title ?? 'No title',
                        ]);
                    }
                } catch (\Exception $e) {
                    logger()->error('CaseFileController::edit - Failed to load tenant case data for editing', [
                        'case_reference_id' => $case->id,
                        'tenant_case_id' => $case->tenant_case_id,
                        'connection_name' => $connectionName,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            } else {
                logger()->warning('CaseFileController::edit - Database switch failed', [
                    'case_reference_id' => $case->id,
                    'database_name' => $case->database_name,
                    'database_host' => $case->database_host,
                ]);
            }
        } else {
            logger()->warning('CaseFileController::edit - No tenant_case_id found', [
                'case_reference_id' => $case->id,
                'database_name' => $case->database_name,
                'created_at' => $case->created_at,
            ]);
        }

        if (! $caseFile) {
            logger()->error('CaseFileController::edit - About to abort with 404', [
                'case_reference_id' => $case->id,
                'tenant_case_id' => $case->tenant_case_id,
                'has_tenant_case_id' => $case->tenant_case_id ? true : false,
                'reason' => $case->tenant_case_id ? 'Tenant case not found or database unavailable' : 'No tenant_case_id set',
            ]);

            abort(404, 'Case file not found or tenant database unavailable');
        }

        // Switch back to main database after reading tenant data
        $this->caseDatabaseService->switchBackToMainDatabase();

        logger()->info('CaseFileController::edit - Successfully returning case file edit view', [
            'case_reference_id' => $case->id,
            'case_file_title' => $caseFile->title ?? 'No title',
        ]);

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

            // Switch back to main database after successful update
            $this->caseDatabaseService->switchBackToMainDatabase();

            return redirect()->route('cases.show', $case)
                ->with('success', 'Falldatei erfolgreich aktualisiert.');

        } catch (\Exception $e) {
            logger()->error('Failed to update case in tenant database', [
                'case_reference_id' => $case->id,
                'tenant_case_id' => $case->tenant_case_id,
                'connection' => $connectionName,
                'error' => $e->getMessage(),
            ]);

            // Switch back to main database even if update failed
            $this->caseDatabaseService->switchBackToMainDatabase();

            return redirect()->back()
                ->withInput()
                ->withErrors(['database' => 'Fehler beim Aktualisieren der Falldatei: '.$e->getMessage()]);
        }
    }

    public function destroy(CaseReference $case)
    {
        // Delete case database
        try {
            $this->caseDatabaseService->deleteCaseDatabase($case);
        } catch (\Exception $e) {
            logger()->error('Failed to delete case database', [
                'case_reference_id' => $case->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('cases.index')
            ->with('success', 'Falldatei erfolgreich gelöscht.');
    }

    public function testDatabase(CaseReference $case)
    {
        $result = $this->caseDatabaseService->testCaseDatabase($case);

        return response()->json($result);
    }
}
