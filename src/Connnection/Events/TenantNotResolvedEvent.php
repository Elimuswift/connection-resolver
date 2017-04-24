<?php

namespace Elimuswift\Connection\Events;

use Elimuswift\Connection\Resolver;

class TenantNotResolvedEvent
{
    public $resolver;

    public function __construct(Resolver $resolver)
    {
        $this->resolver = $resolver;
    }
}
