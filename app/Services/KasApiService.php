<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class KasApiService
{
    private string $kasUser;

    private string $kasPassword;

    private string $kasApiUrl;

    public function __construct()
    {
        $this->kasUser = config('services.kas.user') ?? '';
        $this->kasPassword = config('services.kas.password') ?? '';
        // Use the correct SOAP API endpoint from documentation
        $this->kasApiUrl = 'https://kasapi.kasserver.com/soap/';
    }

    public function createCaseDatabase(string $caseFileId, ?string $comment = null): array
    {
        // If in local environment with LOCAL_CASE_DB_TEST enabled, use local database creation
        if (env('LOCAL_CASE_DB_TEST', false) && app()->environment('local')) {
            $localService = new LocalCaseDatabaseService();
            return $localService->createLocalCaseDatabase($caseFileId, $comment ?? "Database for case: {$caseFileId}");
        }

        $databaseName = $this->generateDatabaseName($caseFileId);
        $databasePassword = $this->generateSecurePassword();
        $databaseComment = $comment ?? "Database for case: {$caseFileId}";

        try {
            $response = $this->callKasApi('add_database', [
                'database_password' => $databasePassword,
                'database_comment' => $databaseComment,
                'database_allowed_hosts' => 'localhost',
            ], $databasePassword);

            if ($response['success']) {
                Log::info('Database created successfully', [
                    'case_file_id' => $caseFileId,
                    'database_name' => $response['database_name'],
                ]);

                return [
                    'success' => true,
                    'database_name' => $response['database_name'],
                    'database_user' => $response['database_user'],
                    'database_password' => $response['database_password'],
                    'database_host' => $response['database_host'],
                ];
            }

            throw new Exception($response['error'] ?? 'Unknown error creating database');
        } catch (Exception $e) {
            Log::error('Failed to create case database', [
                'case_file_id' => $caseFileId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function deleteCaseDatabase(string $databaseLogin): bool
    {
        // If in local environment with LOCAL_CASE_DB_TEST enabled, use local database deletion
        if (env('LOCAL_CASE_DB_TEST', false) && app()->environment('local')) {
            $localService = new LocalCaseDatabaseService();
            return $localService->deleteLocalCaseDatabase($databaseLogin);
        }

        try {
            $response = $this->callKasApi('delete_database', [
                'database_login' => $databaseLogin,
            ]);

            if ($response['success']) {
                Log::info('Database deleted successfully', [
                    'database_login' => $databaseLogin,
                ]);

                return true;
            }

            Log::warning('Failed to delete database', [
                'database_login' => $databaseLogin,
                'error' => $response['error'] ?? 'Unknown error',
            ]);

            return false;

        } catch (Exception $e) {
            Log::error('Exception while deleting database', [
                'database_login' => $databaseLogin,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getDatabases(?string $databaseLogin = null): array
    {
        try {
            $params = [];
            if ($databaseLogin) {
                $params['database_login'] = $databaseLogin;
            }

            $response = $this->callKasApi('get_databases', $params);

            if ($response['success']) {
                return is_array($response['databases']) ? $response['databases'] : [];
            }

            throw new Exception($response['error'] ?? 'Unknown error retrieving databases');
        } catch (Exception $e) {
            Log::error('Failed to retrieve databases', [
                'database_login' => $databaseLogin,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function updateDatabasePassword(string $databaseLogin, string $newPassword): bool
    {
        try {
            $response = $this->callKasApi('update_database', [
                'database_login' => $databaseLogin,
                'database_new_password' => $newPassword,
            ]);

            if ($response['success']) {
                Log::info('Database password updated successfully', [
                    'database_login' => $databaseLogin,
                ]);

                return true;
            }

            Log::warning('Failed to update database password', [
                'database_login' => $databaseLogin,
                'error' => $response['error'] ?? 'Unknown error',
            ]);

            return false;

        } catch (Exception $e) {
            Log::error('Exception while updating database password', [
                'database_login' => $databaseLogin,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function callKasApi(string $function, array $parameters = [], ?string $databasePassword = null): array
    {
        try {
            Log::debug('KAS API SOAP Request', [
                'function' => $function,
                'parameters' => $parameters,
                'user' => $this->kasUser,
            ]);

            // Create SOAP client for KAS API
            $soapClient = new \SoapClient('https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl', [
                'trace' => true,
                'exceptions' => true,
                'connection_timeout' => 60,
                'default_socket_timeout' => 60,
                'stream_context' => stream_context_create([
                    'http' => [
                        'timeout' => 60,
                        'user_agent' => 'LexArbitra/1.0',
                    ],
                ]),
            ]);

            // Prepare request data as shown in documentation
            $requestData = [
                'kas_login' => $this->kasUser,
                'kas_auth_type' => 'plain',
                'kas_auth_data' => $this->kasPassword,
                'kas_action' => $function,
                'KasRequestParams' => $parameters,
            ];

            // Make SOAP request
            $response = $soapClient->KasApi(json_encode($requestData));

            Log::debug('KAS API SOAP Response', [
                'response' => $response,
            ]);

            // Parse response - it might already be an array from SOAP
            if (is_string($response)) {
                $responseData = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Response might not be JSON, treat as raw data
                    $responseData = $response;
                }
            } else {
                // Response is already parsed (array/object)
                $responseData = $response;
            }

            // Handle SOAP response format
            if (is_array($responseData) && isset($responseData['Response'])) {
                $response = $responseData['Response'];
                $isSuccess = isset($response['ReturnString']) && $response['ReturnString'] === 'TRUE';

                if ($function === 'add_database' && $isSuccess) {
                    $databaseLogin = $response['ReturnInfo'] ?? null;

                    // Use MySQL connection config, or fall back to localhost
                    $mysqlConfig = config('database.connections.mysql');
                    $host = $mysqlConfig['host'] ?? 'localhost';

                    return [
                        'success' => true,
                        'database_name' => $databaseLogin,
                        'database_user' => $databaseLogin,
                        'database_password' => $databasePassword,
                        'database_host' => $host,
                        'database_login' => $databaseLogin,
                        'data' => $databaseLogin,
                    ];
                }

                if ($function === 'get_databases' && $isSuccess) {
                    return [
                        'success' => true,
                        'data' => $response['ReturnInfo'] ?? [],
                        'databases' => is_array($response['ReturnInfo']) ? $response['ReturnInfo'] : [],
                    ];
                }

                return [
                    'success' => $isSuccess,
                    'data' => $response['ReturnInfo'] ?? null,
                    'error' => ! $isSuccess ? ($response['Msg'][0]['text'] ?? 'Unknown error') : null,
                ];
            }

            // Handle legacy array responses
            if (is_array($responseData)) {
                return [
                    'success' => true,
                    'data' => $responseData,
                    'databases' => $responseData, // For get_databases
                ];
            }

            // Handle string responses
            if (is_string($responseData)) {
                // Handle TRUE/FALSE responses
                if (trim($responseData) === 'TRUE') {
                    return [
                        'success' => true,
                        'data' => [],
                    ];
                }

                if (trim($responseData) === 'FALSE') {
                    return [
                        'success' => false,
                        'error' => 'API returned FALSE',
                    ];
                }

                // Handle database login responses (for add_database)
                if ($function === 'add_database' && ! empty($responseData) && trim($responseData) !== 'FALSE') {
                    return [
                        'success' => true,
                        'database_login' => trim($responseData),
                        'data' => trim($responseData),
                    ];
                }

                // Handle other string responses
                return [
                    'success' => true,
                    'data' => $responseData,
                ];
            }

            return [
                'success' => true,
                'data' => $responseData,
            ];

        } catch (\SoapFault $fault) {
            Log::error('KAS API SOAP Fault', [
                'function' => $function,
                'parameters' => $parameters,
                'faultcode' => $fault->faultcode,
                'faultstring' => $fault->faultstring,
                'faultactor' => $fault->faultactor ?? 'N/A',
                'detail' => $fault->detail ?? 'N/A',
            ]);

            return [
                'success' => false,
                'error' => "SOAP Fault: {$fault->faultstring} (Code: {$fault->faultcode})",
            ];

        } catch (Exception $e) {
            Log::error('KAS API call failed', [
                'function' => $function,
                'parameters' => $parameters,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function generateDatabaseName(string $caseFileId): string
    {
        // Generate a short, unique database identifier
        // KAS might have length limitations on database names
        $shortId = substr(str_replace('-', '', $caseFileId), 0, 8);

        return "case_{$shortId}";
    }

    private function generateSecurePassword(int $length = 12): string
    {
        // Generate a secure password that meets KAS requirements
        // Using simpler character set to avoid special character restrictions
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';

        $password = '';

        // Ensure at least one character from each set
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];

        // Fill the rest with random characters (alphanumeric only)
        $allChars = $uppercase.$lowercase.$numbers;
        for ($i = 3; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Shuffle the password
        return str_shuffle($password);
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->callKasApi('get_databases', []);

            return $response['success'];
        } catch (Exception $e) {
            Log::error('KAS API connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}