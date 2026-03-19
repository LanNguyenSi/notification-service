<?php

declare(strict_types=1);

namespace App\Contract\Enum;

enum NotificationStatus: string
{
    case QUEUED = 'queued';
    case PARTIAL = 'partial';
    case FAILED = 'failed';
    case SENT = 'sent';
}
