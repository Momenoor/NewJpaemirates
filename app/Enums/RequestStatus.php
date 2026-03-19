<?php

namespace App\Enums;

enum RequestStatus: string implements \Filament\Support\Contracts\HasLabel, \Filament\Support\Contracts\HasColor
{
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PENDING = 'pending';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::APPROVED => __('Approved'),
            self::REJECTED => __('Rejected'),
            self::PENDING => __('Pending'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::PENDING => 'warning',
        };
    }
}
