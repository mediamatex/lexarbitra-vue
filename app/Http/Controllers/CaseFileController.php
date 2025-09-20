<?php

namespace App\Http\Controllers;

use App\Models\CaseFile;
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
        $cases = CaseFile::with(['referee', 'databaseConnection'])
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

        // First, create a temporary minimal case file record in landlord database for database creation
        $tempCaseFileData = [
            'case_number' => $validated['case_number'],
            'title' => $validated['title'],
            'status' => 'draft',
            'initiated_at' => $validated['initiated_at'] ?? now(),
        ];

        // Only add created_by if the column exists (for backward compatibility during migration)
        if (\Schema::hasColumn('case_files', 'created_by')) {
            $tempCaseFileData['created_by'] = auth()->id();
        }

        $tempCaseFile = CaseFile::create($tempCaseFileData);

        // Create case database
        try {
            $connection = $this->caseDatabaseService->createCaseDatabase($tempCaseFile);

            // Switch to the case database and run migrations
            $connectionName = $this->caseDatabaseService->switchToCaseDatabase($tempCaseFile);

            if ($connectionName) {
                try {
                    // Test tenant database connection first
                    logger()->info('Testing tenant database connection', [
                        'connection_name' => $connectionName,
                        'case_id' => $tempCaseFile->id
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
                        '--path' => 'database/migrations'
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

                    // Update the landlord record with tenant case ID for reference and remove temp data
                    $tempCaseFile->update([
                        'tenant_case_id' => $tenantCaseId,
                        'database_connection_id' => $connection->id,
                        // Clear the temp data from landlord - keep only references
                        'description' => null,
                        'dispute_value' => null,
                        'currency' => null,
                        'jurisdiction' => null,
                        'case_category' => null,
                        'complexity_level' => null,
                        'urgency_level' => null,
                    ]);

                    logger()->info('Landlord record updated with tenant references', [
                        'landlord_case_id' => $tempCaseFile->id,
                        'tenant_case_id' => $tenantCaseId
                    ]);

                    // Return the temp case file (which now acts as landlord reference)
                    $caseFile = $tempCaseFile;

                } catch (\Exception $e) {
                    logger()->error('Failed to setup tenant database or save data', [
                        'connection_name' => $connectionName,
                        'case_id' => $tempCaseFile->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    // Continue without failing the entire case creation
                    // The case will exist in landlord DB even if tenant setup fails
                    $caseFile = $tempCaseFile;
                }
            } else {
                $caseFile = $tempCaseFile;
            }

        } catch (\Exception $e) {
            // If database creation fails, clean up and show error
            $tempCaseFile->delete();

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

    public function show(CaseFile $caseFile): Response
    {
        // Load the database connection info first
        $caseFile->load(['databaseConnection']);

        // If we have a tenant database, get the full case data from there
        $tenantCaseData = null;
        if ($caseFile->database_connection_id && $caseFile->tenant_case_id) {
            $connectionName = $this->caseDatabaseService->switchToCaseDatabase($caseFile);

            if ($connectionName) {
                try {
                    $tenantCase = CaseFile::on($connectionName)
                        ->with([
                            'referee',
                            'participants.user',
                            'parties',
                            'documents'
                        ])
                        ->find($caseFile->tenant_case_id);

                    $tenantCaseData = $tenantCase;
                } catch (\Exception $e) {
                    logger()->error('Failed to load tenant case data', [
                        'landlord_case_id' => $caseFile->id,
                        'tenant_case_id' => $caseFile->tenant_case_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // Use tenant data if available, otherwise fall back to landlord data
        $displayCase = $tenantCaseData ?? $caseFile;

        return Inertia::render('CaseFiles/Show', [
            'caseFile' => $displayCase,
            'hasTenantDatabase' => $tenantCaseData !== null,
            'landlordCase' => $tenantCaseData ? $caseFile : null,
        ]);
    }

    public function edit(CaseFile $caseFile): Response
    {
        return Inertia::render('CaseFiles/Edit', [
            'caseFile' => $caseFile,
        ]);
    }

    public function update(Request $request, CaseFile $caseFile)
    {
        $validated = $request->validate([
            'case_number' => 'required|string|unique:case_files,case_number,' . $caseFile->id,
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'dispute_value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'jurisdiction' => 'nullable|string',
            'case_category' => 'nullable|string',
            'complexity_level' => 'nullable|string',
            'urgency_level' => 'nullable|string',
        ]);

        $caseFile->update($validated);

        return redirect()->route('cases.show', $caseFile)
            ->with('success', 'Falldatei erfolgreich aktualisiert.');
    }

    public function destroy(CaseFile $caseFile)
    {
        // Delete case database
        try {
            $this->caseDatabaseService->deleteCaseDatabase($caseFile);
        } catch (\Exception $e) {
            logger()->error('Failed to delete case database', [
                'case_id' => $caseFile->id,
                'error' => $e->getMessage()
            ]);
        }

        $caseFile->delete();

        return redirect()->route('cases.index')
            ->with('success', 'Falldatei erfolgreich gelöscht.');
    }

    public function testDatabase(CaseFile $caseFile)
    {
        $result = $this->caseDatabaseService->testCaseDatabase($caseFile);

        return response()->json($result);
    }
}
