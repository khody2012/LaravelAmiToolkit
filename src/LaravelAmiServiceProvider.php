<?php

namespace Khody2012\LaravelAmiToolkit;

use Illuminate\Support\ServiceProvider;
use Khody2012\LaravelAmiToolkit\AmiClient;
use Khody2012\LaravelAmiToolkit\AmiService;
use Khody2012\LaravelAmiToolkit\Commands\OriginateCall;
use Khody2012\LaravelAmiToolkit\AmiConnection;

class LaravelAmiServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/ami.php' => config_path('ami.php'),
        ], 'ami-config');

        $this->commands([
            OriginateCall::class,
        ]);
    }

    public function register()
    {
        // merge کانفیگ
        $this->mergeConfigFrom(
            __DIR__.'/../config/ami.php',
            'ami'
        );

        // اتصال async (جدید)
        $this->app->singleton(AmiConnection::class, function ($app) {
            return new AmiConnection(config('ami'));
        });

        // AmiClient → حالا می‌تونه از AmiConnection استفاده کنه
        $this->app->singleton(AmiClient::class, function ($app) {
            $connection = $app->make(AmiConnection::class);
            return new AmiClient($connection);
        });

        // سرویس اصلی (Facade هم از این استفاده می‌کنه)
        $this->app->singleton(AmiService::class, function ($app) {
            return new AmiService($app->make(AmiClient::class));
        });

        // alias برای راحت‌تر استفاده کردن (Ami::method())
        $this->app->alias(AmiService::class, 'ami');

        // کامند originate (اگر هنوز نیاز داری)
        $this->app->singleton(OriginateCall::class, function ($app) {
            return new OriginateCall($app->make('ami'));
        });
        $this->commands([
            \Khody2012\LaravelAmiToolkit\Commands\AmiListenCommand::class,
        ]);
    }
}