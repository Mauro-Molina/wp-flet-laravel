<?php

namespace App\Enums;

enum LicenseStatus: string
{
    case Active = 'active';
    case Grace = 'grace';
    case Suspended = 'suspended';

    public function allowsCommands(): bool
    {
        return match ($this) {
            self::Active, self::Grace => true,
            self::Suspended => false,
        };
    }

    public function allowsContent(): bool
    {
        return match ($this) {
            self::Active, self::Grace => true,
            self::Suspended => false,
        };
    }
}
