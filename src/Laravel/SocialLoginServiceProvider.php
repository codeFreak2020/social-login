<?php

declare(strict_types=1);

namespace SocialLogin\Laravel;

use Illuminate\Support\ServiceProvider;
use SocialLogin\Manager;

class SocialLoginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge default config
        $this->mergeConfigFrom(__DIR__ . '/../../config/social-login.php', 'social-login');

        // Bind Manager as a singleton
        $this->app->singleton(Manager::class, function ($app) {
            $config = $app['config']->get('social-login', []);
            return new Manager($config);
        });

        // Optional: alias for convenience
        $this->app->alias(Manager::class, 'social-login');
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/social-login.php' => $this->app->configPath('social-login.php'),
        ], 'social-login-config');
    }
}
