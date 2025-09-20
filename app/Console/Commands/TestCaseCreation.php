<?php

namespace App\Console\Commands;

use App\Models\CaseFile;
use App\Services\CaseDatabaseService;
use Illuminate\Console\Command;

class TestCaseCreation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:case-creation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test case creation and database setup workflow';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing case creation workflow...');

        try {
            // Create a test case
            $caseFile = CaseFile::create([
                'case_number' => 'Az. TEST/' . now()->year,
                'title' => 'Test Case - ' . now()->format('Y-m-d H:i:s'),
                'description' => 'This is a test case to verify the database creation workflow.',
                'initiated_at' => now(),
                'dispute_value' => 50000.00,
                'currency' => 'EUR',
                'jurisdiction' => 'Germany',
                'case_category' => 'Test',
                'complexity_level' => 'medium',
                'urgency_level' => 'normal',
            ]);

            $this->info('✅ Case file created: ' . $caseFile->case_number);

            // Test database creation
            $this->info('Creating case database...');
            $caseDatabaseService = app(CaseDatabaseService::class);

            $connection = $caseDatabaseService->createCaseDatabase($caseFile);

            $this->info('✅ Database connection created: ' . $connection->connection_name);
            $this->info('   Database: ' . $connection->database_name);
            $this->info('   Host: ' . $connection->database_host);

            // Test database connection
            $this->info('Testing database connection...');
            $testResult = $caseDatabaseService->testCaseDatabase($caseFile);

            if ($testResult['success']) {
                $this->info('✅ Database connection test successful!');
            } else {
                $this->error('❌ Database connection test failed: ' . $testResult['error']);
            }

            // Cleanup
            if ($this->confirm('Delete test case and database?', true)) {
                $this->info('Cleaning up...');
                $caseDatabaseService->deleteCaseDatabase($caseFile);
                $caseFile->delete();
                $this->info('✅ Cleanup completed');
            }

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        $this->info('🎉 Case creation workflow test completed successfully!');
        return 0;
    }
}
