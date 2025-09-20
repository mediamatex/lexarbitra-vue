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
        $cases = CaseReference::with(['createdBy'])
            ->latest()
            ->paginate(15);

        return Inertia::render('CaseFiles/Index', [
            'cases' => $cases,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('CaseFiles/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'case_number' => 'required|string|unique:case_files',
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
            $caseReference = $this->caseDatabaseService->createCaseDatabase([
                'case_number' => $validated['case_number'],
                'title' => $validated['title'],
                'initiated_at' => $validated['initiated_at'] ?? now(),
                'created_by' => auth()->id(),
            ]);

            // Switch to the case database and run migrations
            $connectionName = $this->caseDatabaseService->switchToCaseDatabase($caseReference);

            if ($connectionName) {
                try {
                    // Test tenant database connection first
                    logger()->info('Testing tenant database connection', [
                        'connection_name' => $connectionName,
                        'case_id' => $caseReference->id
                    ]);

                    $testConnection = \DB::connection($connectionName)->getPdo();
                    logger()->info('Tenant database connection test successful', [
                        'connection_name' => $connectionName,
                        'driver' => $testConnection->getAttribute(\PDO::ATTR_DRIVER_NAME)
                    ]);

                    // Run migrations on the case database
                    logger()->info('Running migrations on tenant database', [
                        'connection_name' => $connectionName
                    ]);

                    // Force clear any cached connections first
                    \DB::purge($connectionName);

                    $migrateResult = \Artisan::call('migrate', [
                        '--database' => $connectionName,
                        '--force' => true,
                        '--path' => 'database/migrations/tenant'
                    ]);

                    $migrateOutput = \Artisan::output();
                    logger()->info('Migration result', [
                        'result' => $migrateResult,
                        'output' => $migrateOutput
                    ]);

                    // Check what tables exist after migration
                    $tables = \DB::connection($connectionName)->select('SHOW TABLES');
                    logger()->info('Tables in tenant database after migration', [
                        'tables' => $tables,
                        'connection_name' => $connectionName
                    ]);

                    // Check if case_files table exists in tenant database
                    $tablesExist = \Schema::connection($connectionName)->hasTable('case_files');
                    logger()->info('Tenant database table check', [
                        'case_files_exists' => $tablesExist,
                        'connection_name' => $connectionName
                    ]);

                    if (!$tablesExist) {
                        throw new \Exception('case_files table does not exist in tenant database after migration. Migration output: ' . $migrateOutput);
                    }

                    // Now create the REAL case file directly in the tenant database using Query Builder
                    logger()->info('Creating case file for tenant database', [
                        'connection_name' => $connectionName,
                        'validated_data' => $validated
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
                        'case_data' => $tenantCaseData
                    ]);

                    // Use Query Builder to force insertion into tenant database
                    $insertResult = \DB::connection($connectionName)
                        ->table('case_files')
                        ->insert($tenantCaseData);

                    $tenantCaseId = $tenantCaseData['id'];

                    logger()->info('Case data inserted to tenant database', [
                        'tenant_case_id' => $tenantCaseId,
                        'connection_name' => $connectionName,
                        'insert_result' => $insertResult
                    ]);

                    // Verify the case was actually saved in tenant database
                    $verifyCase = \DB::connection($connectionName)
                        ->table('case_files')
                        ->where('id', $tenantCaseId)
                        ->first();

                    logger()->info('Verification: Case in tenant database', [
                        'found_in_tenant' => $verifyCase ? 'YES' : 'NO',
                        'case_data' => $verifyCase
                    ]);

                    // Update the case reference with tenant case ID
                    $caseReference->update([
                        'tenant_case_id' => $tenantCaseId,
                        'status' => 'active', // Update status once tenant case is created
                    ]);

                    logger()->info('Case reference updated with tenant case ID', [
                        'case_reference_id' => $caseReference->id,
                        'tenant_case_id' => $tenantCaseId
                    ]);

                    // Return the case reference
                    $caseFile = $caseReference;

                } catch (\Exception $e) {
                    logger()->error('Failed to setup tenant database or save data', [
                        'connection_name' => $connectionName,
                        'case_id' => $caseReference->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
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
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['database' => 'Fehler beim Erstellen der Falldatenbank: ' . $e->getMessage()]);
        }

        return redirect()->route('cases.show', $caseFile)
            ->with('success', 'Falldatei erfolgreich erstellt.');
    }

    public function show(CaseReference $caseReference): Response
    {
        // If we have a tenant database, get the full case data from there
        $tenantCaseData = null;
        if ($caseReference->tenant_case_id) {
            $connectionName = $this->caseDatabaseService->switchToCaseDatabase($caseReference);

            if ($connectionName) {
                try {
                    // Use raw query to get tenant case data since we don't have Eloquent models for tenant DB
                    $tenantCase = \DB::connection($connectionName)
                        ->table('case_files')
                        ->where('id', $caseReference->tenant_case_id)
                        ->first();

                    $tenantCaseData = $tenantCase;
                } catch (\Exception $e) {
                    logger()->error('Failed to load tenant case data', [
                        'case_reference_id' => $caseReference->id,
                        'tenant_case_id' => $caseReference->tenant_case_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // Use tenant data if available, otherwise fall back to reference data
        $displayCase = $tenantCaseData ?? $caseReference;

        return Inertia::render('CaseFiles/Show', [
            'caseFile' => $displayCase,
            'hasTenantDatabase' => $tenantCaseData !== null,
            'caseReference' => $caseReference,
        ]);
    }

    public function edit(CaseReference $caseReference): Response
    {
        return Inertia::render('CaseFiles/Edit', [
            'caseFile' => $caseReference,
        ]);
    }

    public function update(Request $request, CaseReference $caseReference)
    {
        $validated = $request->validate([
            'case_number' => 'required|string|unique:case_references,case_number,' . $caseReference->id,
            'title' => 'required|string|max:255',
            'status' => 'nullable|string',
        ]);

        $caseReference->update($validated);

        return redirect()->route('cases.show', $caseReference)
            ->with('success', 'Falldatei erfolgreich aktualisiert.');
    }

    public function destroy(CaseReference $caseReference)
    {
        // Delete case database
        try {
            $this->caseDatabaseService->deleteCaseDatabase($caseReference);
        } catch (\Exception $e) {
            logger()->error('Failed to delete case database', [
                'case_reference_id' => $caseReference->id,
                'error' => $e->getMessage()
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
