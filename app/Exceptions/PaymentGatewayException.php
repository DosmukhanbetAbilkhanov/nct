<?php

namespace App\Exceptions;

use Exception;

class PaymentGatewayException extends Exception
{
    public function __construct(public int $statusCode, string $message = '')
    {
        $defaultMessage = "Payment gateway error (HTTP {$statusCode})";
        parent::__construct($message ?: $defaultMessage);
    }
}
