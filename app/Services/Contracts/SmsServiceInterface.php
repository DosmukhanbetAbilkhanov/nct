<?php

namespace App\Services\Contracts;

interface SmsServiceInterface
{
    public function send(string $phoneNumber, string $message): bool;

    public function getBalance(): ?float;
}
