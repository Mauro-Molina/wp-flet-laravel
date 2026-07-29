<?php

namespace App\Enums;

enum CommandStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Expired = 'expired';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Pending => false,
            self::Completed, self::Failed, self::Expired => true,
        };
    }
}
