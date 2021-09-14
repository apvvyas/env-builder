<?php

namespace Appspubs\EnvBuilder\Providers;

use Illuminate\Support\ServiceProvider;

class EnvBuildServiceProvider extends ServiceProvider
{

    protected $commands = [
        'Appspubs\EnvBuilder\Console\Commands\GenerateEnvironment',
    ];

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'env-builder');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateEnvironment::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('env-builder.php'),
            ], 'config');
        }
    }
}