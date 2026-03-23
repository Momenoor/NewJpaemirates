<?php

namespace App\Filament\Resources\Calendars;

use App\Filament\Resources\Calendars\Pages\CreateCalendar;
use App\Filament\Resources\Calendars\Pages\EditCalendar;
use App\Filament\Resources\Calendars\Pages\ListCalendars;
use App\Filament\Resources\Calendars\Pages\ViewCalendar;
use App\Filament\Resources\Calendars\Schemas\CalendarForm;
use App\Filament\Resources\Calendars\Schemas\CalendarInfolist;
use App\Filament\Resources\Calendars\Tables\CalendarsTable;
use App\Models\Calendar;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CalendarResource extends Resource
{
    protected static ?string $model = Calendar::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CalendarDays;

    protected static ?string $recordTitleAttribute = 'title';
    protected static ?int $navigationSort = 4;

    public static function getPluralModelLabel(): string
    {
        return __('Calendars');
    }

    public static function getModelLabel(): string
    {
        return __('Calendar');
    }

    public static function form(Schema $schema): Schema
    {
        return CalendarForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CalendarInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CalendarsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCalendars::route('/'),
            'create' => CreateCalendar::route('/create'),
            'view' => ViewCalendar::route('/{record}'),
            'edit' => EditCalendar::route('/{record}/edit'),
        ];
    }
}
