<?php

namespace Elimuswift\Connection;

use Illuminate\Support\ServiceProvider;

class ConnectionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(Resolver::class, function ($app) {
            return new Resolver($app);
        });
    }

//end register()

    public function boot(Resolver $resolver)
    {
        $this->mergeConfigFrom(
            realpath(__DIR__.'/../').'/config/db-resolver.php',
            'db-resolver'
        );
        try {
            $resolver->resolveTenant();
        } catch (\PDOException $e) {
            $resolver->purgeTenantConnection();
        }

        $this->publishes(
                [
                 realpath(__DIR__.'/../migrations') => database_path('migrations'),
                ],
                'migrations'
            );

        if ($this->app->runningInConsole()) {
            $this->commands(
                [Commands\HandleResolver::class]
            );
        }
    }

//end boot()
}//end class
