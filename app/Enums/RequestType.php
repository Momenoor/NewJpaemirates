<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RequestType: string implements HasLabel, HasColor
{
    case CHANGE_DIFFICULTY = 'change_difficulty';
    case REVIEW_INCENTIVE = 'review_incentive';
    case REVIEW_REPORT = 'review_report';
    case CHANGE_DISTRIBUTED_DATE = 'change_distributed_date';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CHANGE_DIFFICULTY => __('Change Difficulty'),
            self::REVIEW_INCENTIVE => __('Review Incentive'),
            self::REVIEW_REPORT => __('Review Report'),
            self::CHANGE_DISTRIBUTED_DATE => __('Change Distributed Date'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CHANGE_DIFFICULTY => 'info',
            self::REVIEW_INCENTIVE => 'warning',
            self::REVIEW_REPORT => 'success',
            self::CHANGE_DISTRIBUTED_DATE => 'primary',
            default => 'gray',
        };
    }
}
