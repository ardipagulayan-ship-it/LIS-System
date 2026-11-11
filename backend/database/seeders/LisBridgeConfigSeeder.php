<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LisBridgeConfig;
use App\Models\LisBridgeApiKey;
use Illuminate\Support\Facades\Artisan;

class LisBridgeConfigSeeder extends Seeder
{
    /**
     * Seed a default LIS Bridge configuration and API key for development/testing.
     *
     * This seeder creates a bridge config representing the local Laravel app's
     * receiver endpoint. The raw API key is printed to the console — it is only
     * shown once and cannot be retrieved later.
     *
     * @return void
     */
    public function run(): void
    {
        // ---------------------------------------------------------------------------
        // 1. Create or update the default bridge config
        // ---------------------------------------------------------------------------
        $config = LisBridgeConfig::updateOrCreate(
            ['name' => 'Local LIS Bridge Receiver'],
            [
                'lis_url'       => config('app.url'),
                'patient_lis_url' => config('app.url') . '/api/v1/patient-results',
                'qc_lis_url'    => config('app.url') . '/api/v1/qc-results',
                'control_id_map' => [], // Populate via config edit after seed if needed
                'is_active'     => true,
            ]
        );

        $this->command->info("LIS Bridge Config ID: {$config->id}");
        $this->command->info("LIS Bridge Config Name: {$config->name}");

        // ---------------------------------------------------------------------------
        // 2. Generate a new API key for this config
        //    The raw token is returned ONCE here — it cannot be recovered later.
        // ---------------------------------------------------------------------------
        $expiresAt = now()->addYear(); // Key valid for 1 year; pass null for no expiry

        $result = LisBridgeApiKey::createForConfig($config, $expiresAt);

        /** @var LisBridgeApiKey $apiKey */
        $apiKey = $result['model'];
        $rawToken = $result['raw_token'];

        // ---------------------------------------------------------------------------
        // 3. Output the raw token to the console — operator must copy it now
        // ---------------------------------------------------------------------------
        $this->command->newLine();
        $this->command->warn('==========================================================');
        $this->command->warn('  LIS BRIDGE API KEY — COPY THIS NOW!');
        $this->command->warn('  This value will NOT be shown again.');
        $this->command->warn('==========================================================');
        $this->command->info("  Bridge Config ID : {$config->id}");
        $this->command->info("  API Key ID       : {$apiKey->id}");
        $this->command->info("  Expires At       : " . ($expiresAt ? $expiresAt->toDateTimeString() : 'never'));
        $this->command->newLine();
        $this->command->line("  Raw API Token:");
        $this->command->newLine();
        $this->command->info("  {$rawToken}");
        $this->command->newLine();
        $this->command->warn('==========================================================');
        $this->command->newLine();

        // ---------------------------------------------------------------------------
        // 4. Register the key in the middleware's .env documentation
        // ---------------------------------------------------------------------------
        $this->registerKeyInEnv($rawToken);

        $this->command->info('LIS Bridge API key seeded successfully.');
        $this->command->info('Add the following to your middleware .env:');
        $this->command->line("  LIS_BRIDGE_API_KEY={$rawToken}");
    }

    /**
     * Record the raw token in the .env file (development convenience only).
     *
     * @param string $rawToken
     * @return void
     */
    protected function registerKeyInEnv(string $rawToken): void
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            return;
        }

        $envContents = file_get_contents($envPath);

        // Replace existing LIS_BRIDGE_API_KEY or append it
        if (preg_match('/^LIS_BRIDGE_API_KEY=.*$/m', $envContents)) {
            $envContents = preg_replace(
                '/^LIS_BRIDGE_API_KEY=.*$/m',
                "LIS_BRIDGE_API_KEY={$rawToken}",
                $envContents
            );
        } else {
            $envContents .= "\nLIS_BRIDGE_API_KEY={$rawToken}\n";
        }

        file_put_contents($envPath, $envContents);
    }
}
