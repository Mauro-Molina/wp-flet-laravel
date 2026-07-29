<?php

namespace App\Enums;

enum CommandType: string
{
    case UpdateCore = 'update.core';
    case UpdatePlugins = 'update.plugins';
    case UpdateThemes = 'update.themes';
    case UpdateBatch = 'update.batch';
    case BackupCreate = 'backup.create';
    case BackupRestore = 'backup.restore';

    /**
     * @return list<string>
     */
    public static function updateTypes(): array
    {
        return [
            self::UpdateCore->value,
            self::UpdatePlugins->value,
            self::UpdateThemes->value,
            self::UpdateBatch->value,
        ];
    }

    /**
     * @return list<string>
     */
    public static function backupTypes(): array
    {
        return [
            self::BackupCreate->value,
            self::BackupRestore->value,
        ];
    }
}
