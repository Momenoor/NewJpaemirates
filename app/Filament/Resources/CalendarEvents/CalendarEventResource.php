<?php

namespace App\Filament\Resources\CalendarEvents;

use App\Filament\Resources\CalendarEvents\Pages\ListCalendarEvents;
use App\Filament\Actions\Calendar\CreateBulkCalendarEventAction;
use App\Filament\Actions\Calendar\CreateSingleCalendarEventAction;
use App\Filament\Actions\Calendar\ImportFromOutlookAction;
use App\Filament\Actions\Calendar\SyncToOutlookAction;
use App\Filament\Resources\CalendarEvents\Schemas\CalendarEventForm;
use App\Filament\Resources\CalendarEvents\Tables\CalendarEventsTable;
use App\Models\CalendarEvent;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CalendarEventResource extends Resource
{
    protected static ?string $model = CalendarEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CalendarDays;

    protected static ?string $recordTitleAttribute = 'title';
    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return __('Calendar Event');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Calendar Events');
    }
    public static function form(Schema $schema): Schema
    {
        return CalendarEventForm::configure($schema);

    }

    public static function table(Table $table): Table
    {
        return CalendarEventsTable::configure($table);

    }

    public static function getPages(): array
    {
        return [
            'index' => ListCalendarEvents::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
