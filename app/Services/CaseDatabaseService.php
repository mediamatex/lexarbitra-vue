<?php

namespace App\Services;

use App\Models\CaseDatabaseConnection;
use App\Models\CaseFile;
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

    public function createCaseDatabase(CaseFile $caseFile): CaseDatabaseConnection
    {
        try {
            // Check if database already exists for this case
            $existingConnection = CaseDatabaseConnection::where('case_file_id', $caseFile->id)->first();
            if ($existingConnection) {
                throw new Exception("Database already exists for case: {$caseFile->id}");
            }

            // Create database via KAS API
            $databaseInfo = $this->kasApiService->createCaseDatabase(
                $caseFile->id,
                "Database for case: {$caseFile->title}"
            );

            if (! $databaseInfo['success']) {
                throw new Exception('Failed to create database via KAS API');
            }

            // Create database connection record
            $password = $databaseInfo['database_password'];

            // Only encrypt password if not empty (for local SQLite testing, password is empty)
            if (!empty($password)) {
                $password = encrypt($password);
            }

            $connection = CaseDatabaseConnection::create([
                'case_file_id' => $caseFile->id,
                'database_name' => $databaseInfo['database_name'],
                'database_user' => $databaseInfo['database_user'],
                'database_password' => $password,
                'database_host' => $databaseInfo['database_host'],
                'connection_name' => $this->generateConnectionName($caseFile->id),
                'is_active' => true,
            ]);

            try {
                // Configure Laravel database connection
                $this->configureDatabaseConnection($connection);

                Log::info('Case database setup completed successfully', [
                    'case_file_id' => $caseFile->id,
                    'database_name' => $databaseInfo['database_name'],
                    'environment' => app()->environment(),
                ]);

            } catch (Exception $e) {
                Log::error('Database setup failed after successful creation', [
                    'case_file_id' => $caseFile->id,
                    'database_name' => $databaseInfo['database_name'],
                    'environment' => app()->environment(),
                    'setup_error' => $e->getMessage(),
                ]);
            }

            Log::info('Case database created successfully', [
                'case_file_id' => $caseFile->id,
                'database_name' => $databaseInfo['database_name'],
                'connection_name' => $connection->connection_name,
            ]);

            return $connection;

        } catch (Exception $e) {
            Log::error('Failed to create case database', [
                'case_file_id' => $caseFile->id,
                'error' => $e->getMessage(),
            ]);

            // Cleanup: try to delete the database if it was created
            if (isset($databaseInfo['database_name'])) {
                $this->kasApiService->deleteCaseDatabase($databaseInfo['database_name']);
            }

            throw $e;
        }
    }

    public function deleteCaseDatabase(CaseFile $caseFile): bool
    {
        try {
            $connection = CaseDatabaseConnection::where('case_file_id', $caseFile->id)->first();
            if (! $connection) {
                Log::warning('No database connection found for case', ['case_file_id' => $caseFile->id]);

                return true; // Consider it successful if no database exists
            }

            // Remove Laravel database connection
            $this->removeDatabaseConnection($connection->connection_name);

            // Delete database via KAS API
            $deleted = $this->kasApiService->deleteCaseDatabase($connection->database_name);

            // Remove connection record
            $connection->delete();

            Log::info('Case database deleted', [
                'case_file_id' => $caseFile->id,
                'database_name' => $connection->database_name,
                'api_success' => $deleted,
            ]);

            return $deleted;

        } catch (Exception $e) {
            Log::error('Failed to delete case database', [
                'case_file_id' => $caseFile->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getCaseDatabaseConnection(CaseFile $caseFile): ?CaseDatabaseConnection
    {
        return CaseDatabaseConnection::where('case_file_id', $caseFile->id)
            ->where('is_active', true)
            ->first();
    }

    public function switchToCaseDatabase(CaseFile $caseFile): ?string
    {
        $connection = $this->getCaseDatabaseConnection($caseFile);

        if (! $connection) {
            return null;
        }

        // Ensure the connection is configured
        $this->configureDatabaseConnection($connection);

        return $connection->connection_name;
    }

    private function configureDatabaseConnection(CaseDatabaseConnection $connection): void
    {
        $connectionName = $connection->connection_name;
        $host = $connection->database_host;

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
            if ($connection->database_password) {
                try {
                    $password = decrypt($connection->database_password);
                } catch (\Exception $e) {
                    // If decryption fails, assume it's already plain text (for local testing)
                    $password = $connection->database_password;
                }
            }

            Config::set("database.connections.{$connectionName}", [
                'driver' => 'mysql',
                'host' => $host,
                'port' => $mysqlConfig['port'] ?? 3306,
                'database' => $connection->database_name,
                'username' => $connection->database_user,
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
            'database' => $connection->database_name,
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

    public function testCaseDatabase(CaseFile $caseFile): array
    {
        $connection = $this->getCaseDatabaseConnection($caseFile);

        if (! $connection) {
            return [
                'success' => false,
                'error' => 'No database connection found for case',
            ];
        }

        try {
            $this->configureDatabaseConnection($connection);

            // Test database connection
            DB::connection($connection->connection_name)->getPdo();

            return [
                'success' => true,
                'connection_name' => $connection->connection_name,
                'database_name' => $connection->database_name,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
