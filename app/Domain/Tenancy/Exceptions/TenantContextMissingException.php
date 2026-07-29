<?php

namespace App\Domain\Tenancy\Exceptions;

use RuntimeException;

class TenantContextMissingException extends RuntimeException
{
    public function __construct(string $message = 'Tenant context is required for this operation.')
    {
        parent::__construct($message);
    }
}
