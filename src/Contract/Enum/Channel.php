<?php

declare(strict_types=1);

namespace App\Contract\Enum;

enum Channel: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
    case PUSH = 'push';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $channel): string => $channel->value, self::cases());
    }
}
