<?php

namespace App\Exceptions;

class RateLimitException extends NationalCatalogException
{
    public function __construct(public int $retryAfter = 60)
    {
        parent::__construct("Rate limit exceeded. Retry after {$retryAfter} seconds.");
    }
}
