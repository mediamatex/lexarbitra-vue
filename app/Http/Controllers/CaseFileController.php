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

        // First, create a minimal case file record in landlord database (just for reference)
        $caseFileData = [
            'case_number' => $validated['case_number'],
            'title' => $validated['title'],
            'status' => 'draft',
            'initiated_at' => $validated['initiated_at'] ?? now(),
        ];

        // Only add created_by if the column exists (for backward compatibility during migration)
        if (\Schema::hasColumn('case_files', 'created_by')) {
            $caseFileData['created_by'] = auth()->id();
        }

        $caseFile = CaseFile::create($caseFileData);

        // Create case database
        try {
            $connection = $this->caseDatabaseService->createCaseDatabase($caseFile);

            // Switch to the case database and run migrations
            $connectionName = $this->caseDatabaseService->switchToCaseDatabase($caseFile);

            if ($connectionName) {
                // Run migrations on the case database
                \Artisan::call('migrate', [
                    '--database' => $connectionName,
                    '--force' => true,
                ]);

                // Now save the full case data in the tenant database
                $tenantCaseFile = new CaseFile();
                $tenantCaseFile->setConnection($connectionName);
                $tenantCaseFile->fill($validated);

                // Only set created_by if the column exists in tenant database
                if (\Schema::connection($connectionName)->hasColumn('case_files', 'created_by')) {
                    $tenantCaseFile->created_by = auth()->id();
                }

                $tenantCaseFile->save();

                // Update the landlord record with tenant case ID for reference
                $caseFile->update([
                    'tenant_case_id' => $tenantCaseFile->id,
                    'database_connection_id' => $connection->id,
                ]);
            }

        } catch (\Exception $e) {
            // If database creation fails, clean up and show error
            $caseFile->delete();

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
