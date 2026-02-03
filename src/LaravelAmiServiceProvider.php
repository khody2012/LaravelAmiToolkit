<?php

namespace Khody2012\LaravelAmiToolkit;

use Illuminate\Support\ServiceProvider;
use Khody2012\LaravelAmiToolkit\Commands\OriginateCall;

class LaravelAmiServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/ami.php' => config_path('ami.php'),
        ], 'ami-config');

    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/ami.php',
            'ami'
        );

        $this->app->singleton(AmiClient::class, function ($app) {
            return new AmiClient(
                config('ami.host'),
                config('ami.port'),
                config('ami.username'),
                config('ami.secret'),
                config('ami.timeout')
            );
        });

        $this->app->singleton('ami', function ($app) {
            return new AmiService($app->make(AmiClient::class));
        });

        $this->app->singleton(OriginateCall::class, function ($app) {
            return new OriginateCall($app->make('ami'));
        });
    }
}
