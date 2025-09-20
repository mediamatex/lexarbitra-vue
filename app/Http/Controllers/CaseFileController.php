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

        $caseFile = CaseFile::create($validated);

        // Create case database
        try {
            $this->caseDatabaseService->createCaseDatabase($caseFile);
        } catch (\Exception $e) {
            // Log error but don't fail case creation
            logger()->error('Failed to create case database', [
                'case_id' => $caseFile->id,
                'error' => $e->getMessage()
            ]);
        }

        return redirect()->route('cases.show', $caseFile)
            ->with('success', 'Falldatei erfolgreich erstellt.');
    }

    public function show(CaseFile $caseFile): Response
    {
        $caseFile->load([
            'referee',
            'participants.user',
            'parties',
            'documents',
            'databaseConnection'
        ]);

        return Inertia::render('CaseFiles/Show', [
            'caseFile' => $caseFile,
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
