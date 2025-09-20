<?php

namespace App\Services;

class LocalCaseDatabaseService
{
    /**
     * Create a local database for case testing (using SQLite for simplicity)
     */
    public function createLocalCaseDatabase(string $caseFileId, string $description): array
    {
        try {
            // Generate database name and path
            $databaseName = 'case_'.str_replace('-', '_', $caseFileId);
            $databasePath = database_path("case_databases/{$databaseName}.sqlite");

            // Ensure directory exists
            $directory = dirname($databasePath);
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Create SQLite database file if it doesn't exist
            if (! file_exists($databasePath)) {
                touch($databasePath);
            }

            return [
                'success' => true,
                'database_name' => $databaseName,
                'database_user' => '', // SQLite doesn't use users
                'database_password' => '', // SQLite doesn't use passwords
                'database_host' => $databasePath, // For SQLite, this is the file path
                'connection_name' => $databaseName,
                'host' => $databasePath,
                'database' => $databasePath,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a local case database
     */
    public function deleteLocalCaseDatabase(string $databaseName): bool
    {
        try {
            $databasePath = database_path("case_databases/{$databaseName}.sqlite");

            if (file_exists($databasePath)) {
                unlink($databasePath);

                return true;
            }

            return true; // Consider it successful if file doesn't exist
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * List all local case databases
     */
    public function listLocalCaseDatabases(): array
    {
        try {
            $directory = database_path('case_databases');

            if (! is_dir($directory)) {
                return [];
            }

            $files = scandir($directory);
            $databases = [];

            foreach ($files as $file) {
                if (str_ends_with($file, '.sqlite') && str_starts_with($file, 'case_')) {
                    $databases[] = str_replace('.sqlite', '', $file);
                }
            }

            return $databases;
        } catch (\Exception $e) {
            return [];
        }
    }
}
