<?php

declare(strict_types=1);

namespace App\Channel\Email;

interface SmtpClientInterface
{
    public function sendEmail(string $to, string $subject, string $body): void;
}
