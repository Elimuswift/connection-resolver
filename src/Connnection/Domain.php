<?php
namespace Elimuswift\Connection;


use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{

    protected $table = 'domains';

    protected $fillable = [
        'domain',
        'meta'
    ];

    protected $casts = [
        'domain' => 'string',
        'meta' => 'collection'
    ];

    public function __construct(array $attributes = []){
        $this->setConnection(config('tenantable.database.default'));
        parent::__construct($attributes);
    }

    public function tenant(){
        return $this->belongsTo(Tenant::class);
    }

}