<?php

namespace App\Console\Commands;

use App\Models\CaseReference;
use App\Services\CaseDatabaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestCaseDatabase extends Command
{
    protected $signature = 'case:test-database {case_id : The case reference ID to test}';

    protected $description = 'Test database connection for a specific case';

    public function handle()
    {
        $caseId = $this->argument('case_id');

        $case = CaseReference::find($caseId);
        if (!$case) {
            $this->error("Case reference not found: {$caseId}");
            return 1;
        }

        $this->info("Testing database connection for case: {$case->id}");
        $this->info("Database name: {$case->database_name}");
        $this->info("Database host: {$case->database_host}");
        $this->info("Database user: {$case->database_user}");
        $this->info("Has password: " . ($case->database_password ? 'YES' : 'NO'));

        if ($case->database_password) {
            try {
                $decryptedPassword = decrypt($case->database_password);
                $this->info("Password decryption: SUCCESS");
                $this->info("Password length: " . strlen($decryptedPassword));
            } catch (\Exception $e) {
                $this->error("Password decryption: FAILED - " . $e->getMessage());
                $this->info("Trying as plain text...");
                $decryptedPassword = $case->database_password;
            }
        } else {
            $this->error("No password stored for this case");
            return 1;
        }

        // Test connection using CaseDatabaseService
        $this->info("\n--- Testing via CaseDatabaseService ---");
        $service = app(CaseDatabaseService::class);
        $connectionName = $service->switchToCaseDatabase($case);

        if ($connectionName) {
            $this->info("✅ CaseDatabaseService connection: SUCCESS");

            try {
                $result = DB::connection($connectionName)->select('SELECT 1 as test');
                $this->info("✅ Basic query test: SUCCESS");
            } catch (\Exception $e) {
                $this->error("❌ Basic query test: FAILED - " . $e->getMessage());
            }
        } else {
            $this->error("❌ CaseDatabaseService connection: FAILED");
        }

        // Test direct MySQL connection
        $this->info("\n--- Testing direct MySQL connection ---");
        try {
            $pdo = new \PDO(
                "mysql:host={$case->database_host};dbname={$case->database_name}",
                $case->database_user,
                $decryptedPassword
            );
            $this->info("✅ Direct MySQL connection: SUCCESS");
        } catch (\Exception $e) {
            $this->error("❌ Direct MySQL connection: FAILED - " . $e->getMessage());
        }

        $service->switchBackToMainDatabase();

        return 0;
    }
}