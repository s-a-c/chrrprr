<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatus: string
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';
    case AWAY = 'away';
    case BUSY = 'busy';

    public function label(): string
    {
        return match ($this) {
            self::ONLINE => 'Online',
            self::OFFLINE => 'Offline',
            self::AWAY => 'Away',
            self::BUSY => 'Busy',
        };
    }
}
