<?php

namespace App\Console\Commands;

use App\Models\CaseReference;
use App\Services\CaseDatabaseService;
use App\Services\KasApiService;
use Illuminate\Console\Command;

class RefreshCaseDatabase extends Command
{
    protected $signature = 'case:refresh-database {case_id : The case reference ID to refresh}';

    protected $description = 'Refresh database credentials for a case that has connection issues';

    public function handle()
    {
        $caseId = $this->argument('case_id');

        $case = CaseReference::find($caseId);
        if (!$case) {
            $this->error("Case reference not found: {$caseId}");
            return 1;
        }

        $this->info("Refreshing database credentials for case: {$case->id}");
        $this->info("Current database: {$case->database_name}");

        // Test current connection first
        $service = app(CaseDatabaseService::class);
        $this->info("Testing current connection...");

        $connectionName = $service->switchToCaseDatabase($case);
        if ($connectionName) {
            $this->info("✅ Current connection works - no refresh needed");
            $service->switchBackToMainDatabase();
            return 0;
        }

        $this->warn("❌ Current connection failed - attempting to refresh credentials");

        // For production, try to recreate the database via KAS API
        if (!env('LOCAL_CASE_DB_TEST', false) || !app()->environment('local')) {
            $this->info("Attempting to recreate database via KAS API...");

            try {
                $kasService = app(KasApiService::class);

                // Delete old database if it exists
                $this->info("Deleting old database...");
                $kasService->deleteCaseDatabase($case->database_name);

                // Create new database with safe comment (no special characters)
                $this->info("Creating new database...");
                $safeComment = "Refreshed database for case " . str_replace('-', '_', $case->id);
                $databaseInfo = $kasService->createCaseDatabase(
                    $case->database_name,
                    $safeComment
                );

                if ($databaseInfo['success']) {
                    // Update case reference with new credentials
                    $password = $databaseInfo['database_password'];
                    if (!empty($password)) {
                        $password = encrypt($password);
                    }

                    $case->update([
                        'database_user' => $databaseInfo['database_user'],
                        'database_password' => $password,
                        'database_host' => $databaseInfo['database_host'],
                    ]);

                    $this->info("✅ Database credentials refreshed successfully");

                    // Test new connection
                    $this->info("Testing new connection...");
                    $newConnectionName = $service->switchToCaseDatabase($case->fresh());

                    if ($newConnectionName) {
                        $this->info("✅ New connection works!");

                        // Run migrations on refreshed database
                        $this->info("Running migrations...");
                        \Artisan::call('migrate', [
                            '--database' => $newConnectionName,
                            '--force' => true,
                            '--path' => 'database/migrations/tenant',
                        ]);

                        $this->info("✅ Database refresh complete");
                        $service->switchBackToMainDatabase();
                        return 0;
                    } else {
                        $this->error("❌ New connection still fails");
                        $service->switchBackToMainDatabase();
                        return 1;
                    }
                } else {
                    $this->error("Failed to create new database: " . ($databaseInfo['error'] ?? 'Unknown error'));
                    return 1;
                }
            } catch (\Exception $e) {
                $this->error("Exception during database refresh: " . $e->getMessage());
                return 1;
            }
        } else {
            $this->error("Local SQLite refresh not implemented");
            return 1;
        }
    }
}