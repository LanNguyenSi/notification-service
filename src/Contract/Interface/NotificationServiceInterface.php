<?php

declare(strict_types=1);

namespace App\Contract\Interface;

use App\Contract\DTO\NotificationRequestDTO;
use App\Contract\DTO\NotificationResultDTO;

interface NotificationServiceInterface
{
    public function send(NotificationRequestDTO $request): NotificationResultDTO;
}
