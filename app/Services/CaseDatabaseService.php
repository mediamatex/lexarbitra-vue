<?php

namespace App\Services;

use App\Models\CaseReference;
use Exception;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CaseDatabaseService
{
    private KasApiService $kasApiService;

    private DatabaseManager $databaseManager;

    public function __construct(KasApiService $kasApiService, DatabaseManager $databaseManager)
    {
        $this->kasApiService = $kasApiService;
        $this->databaseManager = $databaseManager;
    }

    public function createCaseDatabase(): CaseReference
    {
        try {
            // Create case reference for database creation
            $caseReference = CaseReference::create([
                'database_name' => 'temp', // Will be updated after database creation
                'database_user' => 'temp',
                'database_host' => 'temp',
                'connection_name' => 'temp',
            ]);

            // Use local database service for development
            $localService = new LocalCaseDatabaseService;
            $databaseInfo = $localService->createLocalCaseDatabase(
                $caseReference->id,
                "Database for case: {$caseReference->id}"
            );

            if (! $databaseInfo['success']) {
                $caseReference->delete();
                throw new Exception('Failed to create case database: '.($databaseInfo['error'] ?? 'Unknown error'));
            }

            // Update with actual database connection info
            $password = $databaseInfo['database_password'];

            // Only encrypt password if not empty (for local SQLite testing, password is empty)
            if (! empty($password)) {
                $password = encrypt($password);
            }

            $caseReference->update([
                'database_name' => $databaseInfo['database_name'],
                'database_user' => $databaseInfo['database_user'],
                'database_password' => $password,
                'database_host' => $databaseInfo['database_host'],
                'connection_name' => $this->generateConnectionName($caseReference->id),
                'is_active' => true,
            ]);

            $connection = $caseReference;

            try {
                // Configure Laravel database connection
                $this->configureDatabaseConnection($connection);

                Log::info('Case database setup completed successfully', [
                    'case_reference_id' => $connection->id,
                    'database_name' => $databaseInfo['database_name'],
                    'environment' => app()->environment(),
                ]);

            } catch (Exception $e) {
                Log::error('Database setup failed after successful creation', [
                    'case_reference_id' => $connection->id,
                    'database_name' => $databaseInfo['database_name'],
                    'environment' => app()->environment(),
                    'setup_error' => $e->getMessage(),
                ]);
            }

            Log::info('Case database created successfully', [
                'case_reference_id' => $connection->id,
                'database_name' => $databaseInfo['database_name'],
                'connection_name' => $connection->connection_name,
            ]);

            return $connection;

        } catch (Exception $e) {
            Log::error('Failed to create case database', [
                'case_reference_id' => isset($caseReference) ? $caseReference->id : 'unknown',
                'error' => $e->getMessage(),
            ]);

            // Cleanup: try to delete the database if it was created
            if (isset($databaseInfo['database_name'])) {
                $this->kasApiService->deleteCaseDatabase($databaseInfo['database_name']);
            }

            throw $e;
        }
    }

    public function deleteCaseDatabase(CaseReference $caseReference): bool
    {
        try {
            // Remove Laravel database connection
            $this->removeDatabaseConnection($caseReference->connection_name);

            // Delete database via KAS API
            $deleted = $this->kasApiService->deleteCaseDatabase($caseReference->database_name);

            // Remove connection record
            $caseReference->delete();

            Log::info('Case database deleted', [
                'case_reference_id' => $caseReference->id,
                'database_name' => $caseReference->database_name,
                'api_success' => $deleted,
            ]);

            return $deleted;

        } catch (Exception $e) {
            Log::error('Failed to delete case database', [
                'case_reference_id' => $caseReference->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getCaseReference(string $caseReferenceId): ?CaseReference
    {
        return CaseReference::where('id', $caseReferenceId)
            ->where('is_active', true)
            ->first();
    }

    public function switchToCaseDatabase(CaseReference $caseReference): ?string
    {
        if (! $caseReference) {
            return null;
        }

        // Ensure the connection is configured
        $this->configureDatabaseConnection($caseReference);

        return $caseReference->connection_name;
    }

    private function configureDatabaseConnection(CaseReference $caseReference): void
    {
        $connectionName = $caseReference->connection_name;
        $host = $caseReference->database_host;

        // Check if this is a local SQLite database (indicated by file path in host)
        if (env('LOCAL_CASE_DB_TEST', false) && str_contains($host, '.sqlite')) {
            // Configure SQLite connection for local testing
            Config::set("database.connections.{$connectionName}", [
                'driver' => 'sqlite',
                'database' => $host, // For SQLite, host contains the full file path
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]);
        } else {
            // Use MySQL connection config for case databases (production)
            $mysqlConfig = config('database.connections.mysql');

            $password = '';
            if ($caseReference->database_password) {
                try {
                    $password = decrypt($caseReference->database_password);
                } catch (\Exception $e) {
                    // If decryption fails, assume it's already plain text (for local testing)
                    $password = $caseReference->database_password;
                }
            }

            Config::set("database.connections.{$connectionName}", [
                'driver' => 'mysql',
                'host' => $host,
                'port' => $mysqlConfig['port'] ?? 3306,
                'database' => $caseReference->database_name,
                'username' => $caseReference->database_user,
                'password' => $password,
                'unix_socket' => $mysqlConfig['unix_socket'] ?? '',
                'charset' => $mysqlConfig['charset'] ?? 'utf8mb4',
                'collation' => $mysqlConfig['collation'] ?? 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
                'options' => $mysqlConfig['options'] ?? [],
            ]);
        }

        // Clear any cached connections
        $this->databaseManager->purge($connectionName);

        Log::info('Database connection configured successfully', [
            'connection_name' => $connectionName,
            'host' => $host,
            'database' => $caseReference->database_name,
        ]);
    }

    private function removeDatabaseConnection(string $connectionName): void
    {
        // Purge the connection from the database manager
        $this->databaseManager->purge($connectionName);

        // Remove from config
        Config::offsetUnset("database.connections.{$connectionName}");
    }

    private function generateConnectionName(string $caseFileId): string
    {
        return 'case_'.str_replace('-', '_', $caseFileId);
    }

    public function testCaseDatabase(CaseReference $caseReference): array
    {
        try {
            $this->configureDatabaseConnection($caseReference);

            // Test database connection
            DB::connection($caseReference->connection_name)->getPdo();

            return [
                'success' => true,
                'connection_name' => $caseReference->connection_name,
                'database_name' => $caseReference->database_name,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
