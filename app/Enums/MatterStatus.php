<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MatterStatus: string implements HasLabel, HasColor
{
    case CURRENT = 'current';
    case REPORTED = 'reported';
    case SUBMITTED = 'submitted';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CURRENT => 'Current',
            self::REPORTED => 'Reported',
            self::SUBMITTED => 'Submitted',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CURRENT => 'info',
            self::REPORTED => 'success',
            self::SUBMITTED => 'warning',
        };
    }
}
