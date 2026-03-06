<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FeeStatus: string implements HasLabel, HasColor
{
    case UNPAID = 'unpaid';
    case PARTIAL = 'partial';
    case PAID = 'paid';
    case OVERPAID = 'overpaid';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::UNPAID => 'Unpaid',
            self::PARTIAL => 'Partial',
            self::PAID => 'Paid',
            self::OVERPAID => 'Overpaid',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::UNPAID => 'danger',
            self::PARTIAL => 'warning',
            self::PAID => 'success',
            self::OVERPAID => 'info',
        };
    }
}
