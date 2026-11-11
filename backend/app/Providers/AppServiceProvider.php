<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Models\Equipment;
use App\Models\ServiceLog;
use App\Observers\EquipmentObserver;
use App\Observers\ServiceLogObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register EIM observers
        Equipment::observe(EquipmentObserver::class);
        ServiceLog::observe(ServiceLogObserver::class);

        // R9: Log permanently-failed ULM dispatch jobs to the dedicated channel
        Queue::failing(function ($event) {
            $payload = $event->job->payload();
            $commandName = $payload['displayName'] ?? 'unknown';
            if (str_contains($commandName, 'DispatchUlmOrder')) {
                Log::channel('ulm')->error('[ULM] job failed permanently', [
                    'job'       => $commandName,
                    'exception' => $event->exception->getMessage(),
                    'attempts'  => $payload['attempts'] ?? null,
                ]);
            }
        });
    }
}
