<?php

namespace Elimuswift\Connection;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class Tenant extends Model
{
    /**
     * Connection to use.
     *
     * @var string
     */
    protected $connection = 'tenant';
    /**
     * Database table.
     *
     * @var string
     */
    protected $table = 'tenants';
    /**
     * Fillable fields.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'domain',
        'driver',
        'host',
        'database',
        'username',
        'password',
        'prefix',
        'meta',
    ];
    /**
     * Type casting.
     *
     * @var array
     */
    protected $casts = [
        'uuid' => 'string',
        'domain' => 'string',
        'driver' => 'string',
        'host' => 'string',
        'database' => 'string',
        'username' => 'string',
        'password' => 'string',
        'prefix' => 'string',
        'meta' => 'collection',
    ];

    public function __construct(array $attributes = [])
    {
        $this->setConnection(config('db-resolver.database.default'));
        parent::__construct($attributes);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tenant) {
            $uuids = app('db')->connection(config('db-resolver.database.default'))->table('tenants')->pluck('uuid')->toarray();
            $uuid = $tenant->generateUuid();
            while (in_array($uuid, $uuids)) {
                $uuid = $tenant->generateUuid();
            }
            $tenant->persistUuid($uuid);
        });
    }

    public function generateUuid()
    {
        return strtolower(substr(str_shuffle(preg_replace('/[^A-Za-z0-9]/', '', bcrypt(time() . $this->toJson() . microtime()))), 0, 8));
    }

    public function persistUuid($value)
    {
        $this->attributes['uuid'] = $value;
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    public function getHostAttribute($value)
    {
        return $this->decryptAttribute($value);
    }

    public function getDatabaseAttribute($value)
    {
        return $this->decryptAttribute($value);
    }

    public function getUsernameAttribute($value)
    {
        return $this->decryptAttribute($value);
    }

    public function getPasswordAttribute($value)
    {
        return $this->decryptAttribute($value);
    }

    public function setHostAttribute($value)
    {
        $this->encryptAttribute('host', $value);
    }

    public function setDatabaseAttribute($value)
    {
        $this->encryptAttribute('database', $value);
    }

    public function setUsernameAttribute($value)
    {
        $this->encryptAttribute('username', $value);
    }

    public function setPasswordAttribute($value)
    {
        $this->encryptAttribute('password', $value);
    }

    private function encryptAttribute($attribute, $value)
    {
        if ($value == '') {
            $this->attributes[$attribute] = '';

            return;
        }
        $this->attributes[$attribute] = Crypt::encrypt($value);
    }

    private function decryptAttribute($value)
    {
        if ($value != '') {
            return Crypt::decrypt($value);
        }

        return '';
    }

    public function setUuidAttribute($value)
    {
        //set nothing here!
    }
}
