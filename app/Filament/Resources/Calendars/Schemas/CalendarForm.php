<?php

namespace App\Filament\Resources\Calendars\Schemas;

use App\Models\Matter;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CalendarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('Title'))
                    ->required(),
                Select::make('matter_id')
                    ->label(__('Matter'))
                    ->options(Matter::pluck('number', 'id'))
                    ->searchable(),
                DateTimePicker::make('start_at')
                    ->label(__('Start At'))
                    ->required(),
                DateTimePicker::make('end_at')
                    ->label(__('End At'))
                    ->required(),
                TextInput::make('location')
                    ->label(__('Location')),
                Textarea::make('description')
                    ->label(__('Description')),
            ]);
    }
}
