<?php

namespace Elimuswift\Connection;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

class ConnectionServiceProvider extends ServiceProvider
{

    public function register(){

        $this->app->singleton(Resolver::class, function($app){
            return new Resolver($app);
        });
    }

    public function boot(Resolver $resolver){
        $this->mergeConfigFrom(
            realpath(__DIR__ .'/../').'/config/elimuswift.php', 'elimuswift'
        );
         //resolve tenant, catch PDOExceptions to prevent errors during migration
        try {
            $resolver->resolveTenant();
            if(!$resolver->isResolved()){
                //abort('404');
            }
        } catch( \PDOException $e ) {
            throw new Exceptions\TenantNotResolvedException("Tenant not resolved or does not exist");
         }

        $this->publishes(array(
            realpath(__DIR__.'/../migrations') => database_path('migrations')
        ),'migrations');

         if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\HandleResolver::class,
            ]);
        }

    }

}