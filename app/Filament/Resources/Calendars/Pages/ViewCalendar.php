<?php

namespace App\Filament\Resources\Calendars\Pages;

use App\Filament\Resources\Calendars\CalendarResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCalendar extends ViewRecord
{
    protected static string $resource = CalendarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
