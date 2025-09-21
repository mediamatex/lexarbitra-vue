<?php

namespace App\Console\Commands;

use App\Models\CaseReference;
use Illuminate\Console\Command;

class DebugCases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:cases {--limit=10 : Number of cases to show}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug case references in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Case References Debug Information ===');
        $this->newLine();

        // Basic stats
        $totalCases = CaseReference::count();
        $activeCases = CaseReference::where('is_active', true)->count();
        $casesWithTenantData = CaseReference::whereNotNull('tenant_case_id')->count();

        $this->info("Total case references: {$totalCases}");
        $this->info("Active cases: {$activeCases}");
        $this->info("Cases with tenant data: {$casesWithTenantData}");
        $this->newLine();

        if ($totalCases === 0) {
            $this->warn('No case references found in database!');

            return 0;
        }

        // Show recent cases
        $limit = $this->option('limit');
        $this->info("Recent {$limit} cases:");
        $this->newLine();

        $cases = CaseReference::latest()->limit($limit)->get();

        $headers = ['ID', 'Created', 'Database', 'Tenant Case ID', 'Active'];
        $rows = [];

        foreach ($cases as $case) {
            $rows[] = [
                substr($case->id, 0, 8).'...',
                $case->created_at->format('Y-m-d H:i'),
                $case->database_name,
                $case->tenant_case_id ? substr($case->tenant_case_id, 0, 8).'...' : 'NULL',
                $case->is_active ? 'Yes' : 'No',
            ];
        }

        $this->table($headers, $rows);

        // Test route model binding
        if ($totalCases > 0) {
            $this->newLine();
            $this->info('Testing route model binding...');

            $firstCase = CaseReference::first();
            $this->info("Testing with case ID: {$firstCase->id}");

            $model = new CaseReference;
            $resolved = $model->resolveRouteBinding($firstCase->id);

            if ($resolved) {
                $this->info("✅ Route model binding works - found case: {$resolved->id}");
            } else {
                $this->error("❌ Route model binding failed for existing case ID: {$firstCase->id}");
            }
        }

        return 0;
    }
}
