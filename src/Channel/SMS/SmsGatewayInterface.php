<?php

declare(strict_types=1);

namespace App\Channel\SMS;

interface SmsGatewayInterface
{
    public function sendSms(string $phoneNumber, string $message): void;
}
