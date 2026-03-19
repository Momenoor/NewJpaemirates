<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FeeType: string implements HasLabel, HasColor
{
    case EXPERT_FEE = 'expert fee';

    case MARKETING = 'marketing';
    case VAT = 'VAT';
    case COURT_PENALITY = 'court penalty';

    case OFFICE_SHARER = 'office sharer';
    case OTHER = 'other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::EXPERT_FEE => __('Expert Fee'),
            self::MARKETING => __('Marketing'),
            self::VAT => __('VAT'),
            self::COURT_PENALITY => __('Court Penalty'),
            self::OFFICE_SHARER => __('Office Sharer'),
            self::OTHER => __('Other'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::EXPERT_FEE => 'success',
            self::MARKETING => 'warning',
            self::VAT => 'info',
            self::COURT_PENALITY, self::OFFICE_SHARER => 'danger',
            self::OTHER => 'gray',
        };
    }

    public function isNegative(): bool
    {
        return match ($this) {
            self::COURT_PENALITY, self::OFFICE_SHARER => true,
            default              => false,
        };
    }


}
