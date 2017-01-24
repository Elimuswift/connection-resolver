<?php

namespace Elimuswift\Connection\Commands;

use Illuminate\Console\Command;
use Elimuswift\Connection\SupervisorConfiguration;

class HandleResolver extends Command
{
    /**
     * The supervisor config repository
     *
     * @var object
     **/
    protected $supervisor;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db-resolver:make-worker {tenant : The tenant\'s id or uuid or domain}';

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

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->supervisor->getTenant($this->argument('tenant'));
        $file = $this->supervisor->create();
        $this->info("Config file $file created");

    }
}
