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

    case OFFICE_SHARE = 'office share';
    case OTHER = 'other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::EXPERT_FEE => __('Expert Fee'),
            self::MARKETING => __('Marketing'),
            self::VAT => __('VAT'),
            self::COURT_PENALITY => __('Court Penalty'),
            self::OFFICE_SHARE => __('Office Sharer'),
            self::OTHER => __('Other'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::EXPERT_FEE => 'success',
            self::MARKETING => 'warning',
            self::VAT => 'info',
            self::COURT_PENALITY, self::OFFICE_SHARE => 'danger',
            self::OTHER => 'gray',
        };
    }

    public function isNegative(): bool
    {
        return match ($this) {
            self::COURT_PENALITY, self::OFFICE_SHARE => true,
            default              => false,
        };
    }


}
