<?php
namespace Elimuswift\Connection;

/**
 * Create a configuration file for supervisor
 *
 * @package supervisor-config
 * @author The Weezqyd
 **/
use Exception;

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
        return $this->save($configs);
    
    }
    /**
     * Set tenant object
     *
     * @return void
     * @param object $tenant 
     **/
    private function setTenant(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    /**
     * Get tenant from storage
     *
     * @return void
     * @param mixed $tenant 
     **/
    public function getTenant($tenant)
    {
        $model = new Tenant();

        $instance = $model->whereId($tenant)->orWhere('uuid',$tenant)->orWhere('domain', $tenant)->first();
        if(is_null($instance))
            throw new Exceptions\TenantNotResolvedException("Tenant not resolved or does not exist");
            
        $this->setTenant($instance);
    }
    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    protected function save($conf)
    {
        $file = base_path('supervisor/elimuswift-'.$this->tenant->uuid.'.conf');
        file_put_contents($file, $conf);
        return $file;
    }
} // END class SupervisorConfiguration
