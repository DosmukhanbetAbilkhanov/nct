<?php

namespace App\Exceptions;

class ApiException extends NationalCatalogException
{
    public function __construct(public int $statusCode, string $message = '')
    {
        $defaultMessage = "National Catalog API error (HTTP {$statusCode})";
        parent::__construct($message ?: $defaultMessage);
    }
}
