<?php

namespace Elimuswift\Connection\Events;

use Illuminate\Queue\SerializesModels;
use Elimuswift\Connection\Tenant;

abstract class TenantableEvent
{
    use SerializesModels;

    public $tenant;

    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }
}
