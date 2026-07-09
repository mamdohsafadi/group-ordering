<?php

namespace App\Providers;

use App\Adapters\LocalOrderSubmitter;
use App\Adapters\LocalTokenValidator;
use App\Adapters\LogNotificationDispatcher;
use App\Contracts\NotificationDispatcher;
use App\Contracts\OrderSubmitter;
use App\Contracts\TokenValidator;
use Illuminate\Support\ServiceProvider;

/**
 * Binds the live-system integration seams to their local demo implementations.
 * At adoption time these bindings swap to live implementations — nothing else
 * in the codebase changes.
 */
class IntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OrderSubmitter::class, LocalOrderSubmitter::class);
        $this->app->singleton(TokenValidator::class, LocalTokenValidator::class);
        $this->app->singleton(NotificationDispatcher::class, LogNotificationDispatcher::class);
    }
}
