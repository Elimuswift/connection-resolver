<?php

namespace Elimuswift\Connection;

use Illuminate\Http\Request;
use Symfony\Component\Console\ConsoleEvents;
use Illuminate\Console\Application as Artisan;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputOption;
use Elimuswift\Connection\Events\TenantResolvedEvent;
use Elimuswift\Connection\Events\SetActiveTenantEvent;
use Elimuswift\Connection\Events\TenantNotResolvedEvent;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

class Resolver
{
    private $app = null;

    private $request = null;

    private $activeTenant = null;

    private $consoleDispatcher = false;

    public function __construct($app)
    {
        $this->app = $app;
    }

//end __construct()

    public function setActiveTenant(Tenant $activeTenant)
    {
        $this->activeTenant = $activeTenant;
        $this->setDefaultConnection();

        config()->set('app.url', $this->getActiveTenant()->domain);

        event(new SetActiveTenantEvent($this->activeTenant));
    }

//end setActiveTenant()

    public function getActiveTenant()
    {
        return $this->activeTenant;
    }

//end getActiveTenant()

    public function isResolved()
    {
        return !is_null($this->getActiveTenant());
    }

//end isResolved()

    /**
     * Set the default db connection.
     *
     * @property string $driver
     * @property string $host
     * @property string $database
     * @property string $username
     * @property string $uuid
     * @property string $prefix
     */
    public function setDefaultConnection()
    {
        $tenant = $this->getActiveTenant();
        config()->set('database.connections.tenant.driver', $tenant->driver);
        config()->set('database.connections.tenant.host', $tenant->host);
        config()->set('database.connections.tenant.database', $tenant->database);
        config()->set('database.connections.tenant.username', $tenant->username);
        config()->set('database.connections.tenant.password', $tenant->password);
        config()->set('elimuswift.database.default', $tenant->uuid);
        config()->set('queue.failed.database', 'tenant');

        if (!empty($tenant->prefix)) {
            $tenant->prefix .= '_';
        }

        config()->set('database.connections.tenant.prefix', $tenant->prefix);
        if ($tenant->driver == 'mysql') {
            config()->set('database.connections.tenant.strict', config('database.connections.mysql.strict'));
        }

        config()->set('database.connections.tenant.charset', 'utf8');
        config()->set('database.connections.tenant.collation', 'utf8_unicode_ci');
        $this->app['db']->purge('tenant');
        $this->app['db']->setDefaultConnection('tenant');
    }

//end setDefaultConnection()

    public function resolveTenant()
    {
        // register artisan events
        $this->registerTenantConsoleArgument();

        $this->registerConsoleStartEvent();

        $this->registerConsoleTerminateEvent();

        // resolve by request type
        if ($this->app->runningInConsole()) {
            $this->resolveByConsole();
        } else {
            $this->resolveByHeader();
            $this->resolveByRequest();
        }
    }

//end resolveTenant()

    public function purgeTenantConnection()
    {
        $this->app['db']->setDefaultConnection(config('db-resolver.database.default'));
    }

//end purgeTenantConnection()

    public function reconnectTenantConnection()
    {
        $this->app['db']->setDefaultConnection('tenant');
    }

//end reconnectTenantConnection()

    private function resolveByRequest()
    {
        $this->request = $this->app->make('Illuminate\Http\Request');

        $domain = $this->request->getHost();

        $this->resolve($domain);
    }

    private function resolveByHeader()
    {
        $this->request = $this->app->make('Illuminate\Http\Request');

        $uuid = $this->request->headers->get(config('db-resolver.Tenant-Header-Name'));

        $this->resolve($uuid);
    }

//end resolveByRequest()

    private function resolveByConsole()
    {
        $domain = (new ArgvInput())->getParameterOption('--tenant', null);

        $this->resolve($domain);
    }

//end resolveByConsole()

    private function getConsolerDispatcher()
    {
        if (!$this->consoleDispatcher) {
            $this->consoleDispatcher = app('Symfony\Component\EventDispatcher\EventDispatcher');
        }

        return $this->consoleDispatcher;
    }

    /**
     * Resolve tenant connection.
     *
     * @param string|int $domaint
     **/
    protected function resolve($domain)
    {
        // find tenant by primary domain
        $model = new Tenant();
        $tenant = $model->where('domain', $domain)
                        ->orWhere('uuid', $domain)
                        ->orWhere('id', $domain)
                        ->first();
        if ($tenant instanceof Tenant) {
            $this->setActiveTenant($tenant);
            event(new TenantResolvedEvent($tenant));

            return;
        }

        // if were here the domain could not be found in the primary table
        $model = new Domain();
        $tenant = $model->where('domain', $domain)->first();
        if ($tenant instanceof Domain) {
            $returnModel = $tenant->tenant;
            $this->setActiveTenant($returnModel);
            event(new TenantResolvedEvent($returnModel));

            return;
        }

        // if were here we haven't found anything?
        event(new TenantNotResolvedEvent($this));

        return;
    }

//end getConsolerDispatcher()

    private function registerTenantConsoleArgument()
    {
        // register --tenant option for console
        $this->app['events']->listen(
            'Illuminate\Console\Events\ArtisanStarting',
            function ($event) {
                $definition = $event->artisan->getDefinition();
                $definition->addOption(new InputOption('--tenant', null, InputOption::VALUE_OPTIONAL, 'The tenant the command should be run for (id,uuid,domain).'));
                $event->artisan->setDefinition($definition);
                $event->artisan->setDispatcher($this->getConsolerDispatcher());
            }
        );
    }

//end registerTenantConsoleArgument()

    private function registerConsoleStartEvent()
    {
        // possibly disable the command
        $this->getConsolerDispatcher()->addListener(
            ConsoleEvents::COMMAND,
            function (ConsoleCommandEvent $event) {
                $tenant = $event->getInput()->getParameterOption('--tenant', null);
                if (!is_null($tenant)) {
                    if ($tenant == '*' || $tenant == 'all') {
                        $event->disableCommand();
                    } else {
                        if ($this->isResolved()) {
                            $event->getOutput()->writeln('<info>Running command for '.$this->getActiveTenant()->domain.'</info>');
                        } else {
                            $event->getOutput()->writeln('<error>Failed to resolve tenant</error>');
                            $event->disableCommand();
                        }
                    }
                }
            }
        );
    }

//end registerConsoleStartEvent()

    private function registerConsoleTerminateEvent()
    {
        // run command on the terminate event instead
        $this->getConsolerDispatcher()->addListener(
            ConsoleEvents::TERMINATE,
            function (ConsoleTerminateEvent $event) {
                $tenant = $event->getInput()->getParameterOption('--tenant', null);
                if (!is_null($tenant)) {
                    if ($tenant == '*' || $tenant == 'all') {
                        // run command for all
                        $command = $event->getCommand();
                        $input = $event->getInput();
                        $output = $event->getOutput();
                        $exitcode = $event->getExitCode();

                        $tenants = Tenant::all();
                        foreach ($tenants as $tenant) {
                            // set tenant
                            $this->setActiveTenant($tenant);
                            $event->getOutput()->writeln('<info>Running command for '.$this->getActiveTenant()->domain.'</info>');
                            try {
                                $command->run($input, $output);
                            } catch (\Exception $e) {
                                $event = new ConsoleExceptionEvent($command, $input, $output, $e, $e->getCode());
                                $this->getConsolerDispatcher()->dispatch(ConsoleEvents::EXCEPTION, $event);

                                $e = $event->getException();

                                throw $e;
                            }
                        }

                        $event->setExitCode($exitcode);
                    }//end if
                }//end if
            }
        );
    }

//end registerConsoleTerminateEvent()
}//end class
