<?php

namespace App\Filament\Resources\Calendars\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CalendarInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title')
                    ->label(__('Title')),
                TextEntry::make('matter.number')
                    ->label(__('Matter')),
                TextEntry::make('start_at')
                    ->label(__('Start At'))
                    ->dateTime(),
                TextEntry::make('end_at')
                    ->label(__('End At'))
                    ->dateTime(),
                TextEntry::make('location')
                    ->label(__('Location')),
                TextEntry::make('description')
                    ->label(__('Description'))
                    ->html(),
            ]);
    }
}
