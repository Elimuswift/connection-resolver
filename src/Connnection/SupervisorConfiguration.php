<?php
namespace Elimuswift\Connection;

/**
 * Create a configuration file for supervisor
 *
 * @package supervisor-config
 * @author The Weezqyd
 **/
class SupervisorConfiguration
{
    /**
     * Tenant model instance
     *
     * @var mixed
     **/
    protected $tenant;

    /**
     * config options
     *
     * @var string
     **/
    protected $configs = '[program:elimuswift-{UUID}] 
process_name = %(program_name)s_%(process_num)02d 
command = php {PATH}/artisan queue:work --sleep=3 --tries=3 --tenant={UUID} 
autostart = true 
autorestart = true 
user = weez 
numprocs = 4 
redirect_stderr = true 
stdout_logfile = {PATH}/storage/logs/worker.log';

     

    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    /**
     * Set the config values
     *
     * @return void
     * @param $configs array
     **/

    private function set()
    {
        $conf = $this->configs;
        $path = str_replace('{PATH}', base_path(), $conf);
        $config = str_replace('{UUID}', $this->tenant->uuid, $path);
        
        return $config;
    }

    /**
     * Create Configuration and sace to file
     *
     * @return void
     * @author
     **/
    public function create()
    {
        $configs = $this->set();
        $this->save($configs);
        return $configs;
    }

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    protected function save($conf)
    {
        return file_put_contents(base_path('supervisor/elimuswift-'.$this->tenant->uuid.'.conf'), $conf);
    }
} // END class SupervisorConfiguration
