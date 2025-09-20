<?php

namespace App\Console\Commands;

use App\Services\KasApiService;
use Illuminate\Console\Command;

class TestKasApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kas:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test KAS API connection and list databases';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing KAS API connection...');

        try {
            $kasApiService = app(KasApiService::class);

            // Test connection
            $this->info('Testing connection...');
            $connected = $kasApiService->testConnection();

            if ($connected) {
                $this->info('✅ KAS API connection successful!');

                // List existing databases
                $this->info('Fetching database list...');
                $databases = $kasApiService->getDatabases();

                if (empty($databases)) {
                    $this->info('No databases found.');
                } else {
                    $this->info('Found ' . count($databases) . ' databases:');
                    foreach ($databases as $db) {
                        $this->line('  - ' . (is_array($db) ? json_encode($db) : $db));
                    }
                }
            } else {
                $this->error('❌ KAS API connection failed!');
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Error testing KAS API: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
