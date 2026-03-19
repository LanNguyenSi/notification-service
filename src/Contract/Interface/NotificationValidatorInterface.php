<?php

declare(strict_types=1);

namespace App\Contract\Interface;

use App\Contract\DTO\NotificationRequestDTO;
use App\Contract\DTO\ValidatedRequest;
use App\Contract\Exception\ValidationException;

interface NotificationValidatorInterface
{
    public function validate(NotificationRequestDTO $request): ValidatedRequest;
}
