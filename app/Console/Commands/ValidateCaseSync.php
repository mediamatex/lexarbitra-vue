<?php

namespace App\Console\Commands;

use App\Models\CaseReference;
use App\Services\CaseDatabaseService;
use Illuminate\Console\Command;

class ValidateCaseSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cases:validate-sync {--fix : Attempt to fix sync issues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate data synchronization between landlord and tenant databases';

    /**
     * Execute the console command.
     */
    public function handle(CaseDatabaseService $caseDatabaseService)
    {
        $this->info('🔍 Validating case data synchronization...');

        $cases = CaseReference::whereNotNull('tenant_case_id')->get();
        $totalIssues = 0;
        $totalCases = $cases->count();

        if ($totalCases === 0) {
            $this->info('✅ No cases with tenant databases found.');

            return;
        }

        $this->withProgressBar($cases, function ($case) use ($caseDatabaseService, &$totalIssues) {
            $issues = $this->validateCaseConsistency($case, $caseDatabaseService);

            if (! empty($issues)) {
                $totalIssues += count($issues);
                $this->newLine();
                $this->error("❌ Case {$case->case_number} (ID: {$case->id}) has issues:");
                foreach ($issues as $issue) {
                    $this->line("   • {$issue}");
                }

                if ($this->option('fix')) {
                    $this->attemptFix($case, $caseDatabaseService);
                }
            }
        });

        $this->newLine(2);

        if ($totalIssues === 0) {
            $this->info("✅ All {$totalCases} cases are properly synchronized!");
        } else {
            $this->warn("⚠️  Found {$totalIssues} sync issues across {$totalCases} cases.");
            if (! $this->option('fix')) {
                $this->info('💡 Run with --fix to attempt automatic corrections.');
            }
        }
    }

    private function validateCaseConsistency($case, $caseDatabaseService): array
    {
        $issues = [];

        if (! $case->tenant_case_id) {
            return $issues;
        }

        $connectionName = $caseDatabaseService->switchToCaseDatabase($case);
        if (! $connectionName) {
            $issues[] = 'Cannot connect to tenant database';

            return $issues;
        }

        try {
            $tenantCase = \DB::connection($connectionName)
                ->table('case_files')
                ->where('id', $case->tenant_case_id)
                ->first();

            if (! $tenantCase) {
                $issues[] = 'Tenant case not found';

                return $issues;
            }

            // Check critical fields
            if ($case->case_number !== $tenantCase->case_number) {
                $issues[] = "Case number mismatch: landlord='{$case->case_number}', tenant='{$tenantCase->case_number}'";
            }

            if ($case->title !== $tenantCase->title) {
                $issues[] = "Title mismatch: landlord='{$case->title}', tenant='{$tenantCase->title}'";
            }

            if ($case->status !== $tenantCase->status) {
                $issues[] = "Status mismatch: landlord='{$case->status}', tenant='{$tenantCase->status}'";
            }

        } catch (\Exception $e) {
            $issues[] = 'Error accessing tenant data: '.$e->getMessage();
        }

        return $issues;
    }

    private function attemptFix($case, $caseDatabaseService): void
    {
        $this->info("   🔧 Attempting to fix sync for case {$case->case_number}...");

        $connectionName = $caseDatabaseService->switchToCaseDatabase($case);
        if (! $connectionName) {
            $this->error('   ❌ Cannot connect to tenant database for fixes');

            return;
        }

        try {
            $updated = \DB::connection($connectionName)
                ->table('case_files')
                ->where('id', $case->tenant_case_id)
                ->update([
                    'case_number' => $case->case_number,
                    'title' => $case->title,
                    'status' => $case->status,
                    'updated_at' => now(),
                ]);

            if ($updated) {
                $this->info('   ✅ Successfully synced landlord data to tenant database');
            } else {
                $this->error('   ❌ No tenant record was updated');
            }

        } catch (\Exception $e) {
            $this->error('   ❌ Fix failed: '.$e->getMessage());
        }
    }
}
