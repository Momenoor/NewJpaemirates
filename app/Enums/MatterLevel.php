<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MatterLevel: string implements HasLabel, HasColor
{
    case FIRST_INSTANCE = 'first_instance';
    case APPEAL = 'appeal';
    case CONGESTION = 'congestion';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FIRST_INSTANCE => 'First Instance',
            self::APPEAL => 'Appeal',
            self::CONGESTION => 'Congestion',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::FIRST_INSTANCE => 'info',
            self::APPEAL => 'success',
            self::CONGESTION => 'warning',
        };
    }
}
