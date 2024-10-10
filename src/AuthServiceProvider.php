<?php

namespace wildcats1369\auth;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register() : void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/auth.php', 'auth');
        $this->app->singleton('jwt.service', function ($app) {
            return new Services\AuthService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot() : void
    {
        Log::info('booting jwt service provider');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        $this->publishes([__DIR__ . '/../config/auth.php' => config_path('auth.php'),], 'config');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'jwt');

        // Register the migration path
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Publish the migration
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'migrations');
    }

}
