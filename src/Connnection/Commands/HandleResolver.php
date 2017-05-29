<?php

namespace Elimuswift\Connection\Commands;

use Illuminate\Console\Command;
use Elimuswift\Connection\SupervisorConfiguration;

class HandleResolver extends Command
{
    /**
     * The supervisor config repository.
     *
     * @var object
     **/
    protected $supervisor;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db-resolver:make-worker {tenant : The tenant\'s id or uuid or domain} {--path= : The absolute path to create file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a supervisor config file for the specified tenant';

    /**
     * Create a new command instance.
     */
    public function __construct(SupervisorConfiguration $config)
    {
        $this->supervisor = $config;
        parent::__construct();
    }

//end __construct()

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->supervisor->getTenant($this->argument('tenant'));

        $path = $this->option('path') ? $this->option('path') : config('db-resolver.configurationPath');
        $file = $this->supervisor->create($path, config('db-resolver.basePath'), config('db-resolver.user'));
        $this->info("Config file $file created");
    }

//end handle()
}//end class
