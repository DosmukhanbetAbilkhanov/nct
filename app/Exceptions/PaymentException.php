<?php

namespace App\Exceptions;

use Exception;

class PaymentException extends Exception
{
    public function __construct(string $message = 'Payment processing error')
    {
        parent::__construct($message);
    }
}
