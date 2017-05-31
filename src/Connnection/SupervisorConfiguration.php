<?php

namespace Elimuswift\Connection;

/**
 * Create a configuration file for supervisor.
 *
 * @author The Weezqyd <wizqydy@gmail.com>
 **/
class SupervisorConfiguration
{
    /**
     * Tenant model instance.
     *
     * @var mixed
     **/
    protected $tenant;

    /**
     * config options.
     *
     * @var string
     **/
    protected $configs = '[program:elimuswift-{UUID}] 
process_name = %(program_name)s_%(process_num)02d 
command = php {PATH}/artisan queue:work --sleep=3 --tries=3 --tenant={UUID} 
autostart = true 
autorestart = true 
user = {USER} 
numprocs = 4 
redirect_stderr = true 
stdout_logfile = {PATH}/storage/logs/worker.log';

    /**
     * Set the config values.
     *
     * @param $configs array
     **/
    protected function set($basPath, $user)
    {
        return str_replace(['{UUID}', '{USER}', '{PATH}'], [$this->tenant->uuid, $user, $basPath], $this->configs);
    }

//end set()

    /**
     * Create Configuration and sace to file.
     *
     * @param string $path Storage location for the config file
     **/
    public function create($path, $basePath, $user)
    {
        $configs = $this->set($basePath, $user);

        return $this->save($configs, $path);
    }

//end create()

    /**
     * Set tenant object.
     *
     * @param Tenant $tenant
     **/
    protected function setTenant(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

//end setTenant()

    /**
     * Get tenant from storage.
     *
     * @param string $tenant UUID ID or domain for the tenant
     *
     * @throws TenantNotResolvedException
     *
     * @return Tenant
     **/
    public function getTenant($tenant)
    {
        $model = new Tenant();

        $instance = $model->whereId($tenant)->orWhere('uuid', $tenant)->orWhere('domain', $tenant)->first();
        if (is_null($instance)) {
            throw new Exceptions\TenantNotResolvedException('Tenant not resolved or does not exist');
        }

        $this->setTenant($instance);
    }

//end getTenant()

    /**
     * undocumented function.
     *
     * @return string $file The location where the fle was created
     **/
    protected function save($conf, $path)
    {
        $file = $path.'/elimuswift-'.$this->tenant->uuid.'.conf';
        if (!file_put_contents($file, $conf)) {
            throw new Exceptions\ErrorException('Error while creating the config file. Please check if the location is writable');
        }

        return $file;
    }

//end save()
}//end class
 // END class SupervisorConfiguration
