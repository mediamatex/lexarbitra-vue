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
                        // Silently skip failed tenant connections
                    }
                }
            }

            // Only add case to list if tenant data was successfully loaded
            if ($case) {
                $cases[] = $case;
            }
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
                    // Run migrations on the case database
                    \Artisan::call('migrate', [
                        '--database' => $connectionName,
                        '--force' => true,
                        '--path' => 'database/migrations/tenant',
                    ]);

                    // Check if case_files table exists in tenant database
                    $tablesExist = \Schema::connection($connectionName)->hasTable('case_files');

                    if (! $tablesExist) {
                        throw new \Exception('case_files table does not exist in tenant database after migration.');
                    }

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

                    // Add created_by if the column exists in tenant database
                    try {
                        if (\Schema::connection($connectionName)->hasColumn('case_files', 'created_by')) {
                            $tenantCaseData['created_by'] = auth()->id();
                        }
                    } catch (\Exception $e) {
                        // Continue without created_by
                    }

                    // Insert case data to tenant database
                    \DB::connection($connectionName)
                        ->table('case_files')
                        ->insert($tenantCaseData);

                    $tenantCaseId = $tenantCaseData['id'];

                    // Update the case reference with tenant case ID
                    $case->update(['tenant_case_id' => $tenantCaseId]);

                    // Return the case reference
                    $caseFile = $case;

                    // Switch back to main database
                    $this->caseDatabaseService->switchBackToMainDatabase();

                } catch (\Exception $e) {
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

            return redirect()->back()
                ->withInput()
                ->withErrors(['database' => 'Fehler beim Erstellen der Falldatenbank: '.$e->getMessage()]);
        }

        return redirect()->route('cases.show', $caseFile)
            ->with('success', 'Falldatei erfolgreich erstellt.');
    }

    public function show(CaseReference $case): Response
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
                        // Merge tenant data with reference info
                        // IMPORTANT: Use case reference ID as the main ID for routing
                        $caseFile = (object) array_merge((array) $tenantCase, [
                            'id' => $case->id, // Case reference ID for routing
                            'tenant_case_id' => $tenantCase->id, // Original tenant case ID
                            'reference_id' => $case->id,
                            'database_name' => $case->database_name,
                            'connection_name' => $case->connection_name,
                        ]);
                    }
                } catch (\Exception $e) {
                    // Failed to load tenant case data
                }
            }
        }

        if (! $caseFile) {
            abort(404, 'Case file not found or tenant database unavailable');
        }

        // Switch back to main database after reading tenant data
        $this->caseDatabaseService->switchBackToMainDatabase();

        return Inertia::render('CaseFiles/Show', [
            'caseFile' => $caseFile,
            'caseReference' => $case,
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
                        // IMPORTANT: Use case reference ID as the main ID for routing
                        $caseFile = (object) array_merge((array) $tenantCase, [
                            'id' => $case->id, // Case reference ID for routing
                            'tenant_case_id' => $tenantCase->id, // Original tenant case ID
                            'reference_id' => $case->id, // For form submission
                            'database_name' => $case->database_name,
                            'connection_name' => $case->connection_name,
                        ]);
                    }
                } catch (\Exception $e) {
                    // Failed to load tenant case data
                }
            }
        }

        if (! $caseFile) {
            abort(404, 'Case file not found or tenant database unavailable');
        }

        // Switch back to main database after reading tenant data
        $this->caseDatabaseService->switchBackToMainDatabase();

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

            // Switch back to main database after successful update
            $this->caseDatabaseService->switchBackToMainDatabase();

            return redirect()->route('cases.show', $case)
                ->with('success', 'Falldatei erfolgreich aktualisiert.');

        } catch (\Exception $e) {
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
            // Log error but continue with deletion
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