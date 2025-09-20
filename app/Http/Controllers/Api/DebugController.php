<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseReference;
use App\Services\CaseDatabaseService;
use Illuminate\Http\Request;

class DebugController extends Controller
{
    public function __construct(
        private CaseDatabaseService $caseDatabaseService
    ) {}

    public function databaseInfo()
    {
        $caseReferences = CaseReference::all();

        $stats = [
            'total_cases' => $caseReferences->count(),
            'active_cases' => $caseReferences->where('is_active', true)->count(),
            'cases_with_tenant_data' => $caseReferences->whereNotNull('tenant_case_id')->count(),
            'broken_cases' => $caseReferences->whereNull('tenant_case_id')->count(),
            'case_details' => []
        ];

        // Add details for each case (limit to 10 for performance)
        foreach ($caseReferences->take(10) as $caseRef) {
            $caseDetail = [
                'id' => $caseRef->id,
                'database_name' => $caseRef->database_name,
                'tenant_case_id' => $caseRef->tenant_case_id,
                'is_active' => $caseRef->is_active,
                'connection_works' => false,
                'table_exists' => false,
                'case_data_exists' => false
            ];

            try {
                // Test connection
                $connectionName = $this->caseDatabaseService->switchToCaseDatabase($caseRef);
                if ($connectionName) {
                    $caseDetail['connection_works'] = true;

                    // Check if table exists
                    $caseDetail['table_exists'] = \Schema::connection($connectionName)->hasTable('case_files');

                    // Check if case data exists
                    if ($caseRef->tenant_case_id && $caseDetail['table_exists']) {
                        $caseData = \DB::connection($connectionName)
                            ->table('case_files')
                            ->where('id', $caseRef->tenant_case_id)
                            ->exists();
                        $caseDetail['case_data_exists'] = $caseData;
                    }
                }
            } catch (\Exception $e) {
                $caseDetail['error'] = $e->getMessage();
            }

            $stats['case_details'][] = $caseDetail;
        }

        return response()->json($stats);
    }
}
