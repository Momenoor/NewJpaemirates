<?php

namespace App\Filament\Resources\CalendarEvents\Tables;

use App\Filament\Actions\Calendar\CreateBulkCalendarEventAction;
use App\Filament\Actions\Calendar\CreateSingleCalendarEventAction;
use App\Filament\Actions\Calendar\ImportFromOutlookAction;
use App\Filament\Actions\Calendar\SyncToOutlookAction;
use App\Filament\Resources\CalendarEvents\Schemas\CalendarEventInfolist;
use App\Models\CalendarEvent;
use App\Services\OutlookCalendarService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
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

class CalendarEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold)
                    ->description(fn($record) => $record->location ?: null),

                TextColumn::make('matter')
                    ->label(__('Matter'))
                    ->getStateUsing(fn($record) => $record->matter
                        ? $record->matter->year . '/' . $record->matter->number
                        : '—'
                    )
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'single' => 'primary',
                        'bulk' => 'warning',
                    }),
                TextColumn::make('matter.number')
                    ->label(__('Matter'))
                    ->getStateUsing(fn($record) => $record->matter
                        ? $record->matter->year . '/' . $record->matter->number
                        : '—'
                    )
                    ->searchable()
                    ->sortable()
                    ->visible(fn($record) => $record?->type === 'single'),
                TextColumn::make('start_datetime')
                    ->label(__('Start At'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('end_datetime')
                    ->label(__('End At'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                IconColumn::make('synced_to_outlook')
                    ->boolean()
                    ->label(__('Synced')),
                IconColumn::make('is_teams_meeting')
                    ->boolean()
                    ->label(__('Teams')),
                TextColumn::make('online_meeting_url')
                    ->label(__('Link'))
                    ->icon(Heroicon::VideoCamera)
                    ->formatStateUsing(fn($state) => $state ? __('Join') : '—')
                    ->url(fn($record) => $record->online_meeting_url)
                    ->color('info')
                    ->openUrlInNewTab(),

                TextColumn::make('createdBy.name')
                    ->label(__('Created By'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('start_datetime')
            ->filters([
                Filter::make('upcoming')
                    ->label(__('Upcoming'))
                    ->query(fn(Builder $query) => $query->where('start_datetime', '>=', now()))
                    ->default(),
                SelectFilter::make('type')
                    ->options([
                        'single' => __('Single'),
                        'bulk' => __('Bulk'),
                    ]),
                TernaryFilter::make('synced_to_outlook'),
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateSingleCalendarEventAction::make('createSingle')
                    ->visible(fn() => auth()->user()->can('CreateSingle:CalendarEvent')),
                CreateBulkCalendarEventAction::make('createBulk')
                    ->visible(fn() => auth()->user()->can('CreateBulk:CalendarEvent')),
                ImportFromOutlookAction::make('import')
                    ->visible(fn() => auth()->user()->can('ImportFromOutlook:CalendarEvent')),
            ])
            ->recordActions([
                SyncToOutlookAction::make()
                    ->visible(fn($record) => $record instanceof CalendarEvent && auth()->user()->can('SyncToOutlook:CalendarEvent') && !$record->synced_to_outlook),
                ViewAction::make()->schema(fn(Schema $schema) => CalendarEventInfolist::configure($schema))->iconButton(),
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
