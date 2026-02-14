<?php

namespace App\Providers;

use App\Listeners\RecordAiUsageFromAgentPrompted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Ai\Events\AgentPrompted;

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
        Event::listen(AgentPrompted::class, RecordAiUsageFromAgentPrompted::class);
    }
}
