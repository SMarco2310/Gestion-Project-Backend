<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory;
use App\Auth\CustomOAuthProvider;

class CustomSocialiteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $socialite = $this->app->make(Factory::class);
        $socialite->extend(
            'custom_app',
            function ($app) use ($socialite) {
                $config = $app['config']['services.custom_oauth'];
                return $socialite->buildProvider(CustomOAuthProvider::class, $config);
            }
        );
    }
}
