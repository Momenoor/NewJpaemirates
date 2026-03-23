<?php

namespace App\Filament\Resources\Calendars\Tables;

use App\Services\OutlookCalendarService;
use Filament\Notifications\Notification;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Tables\Table;

class CalendarsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('start_at')
                    ->label(__('Start At'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label(__('End At'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('location')
                    ->label(__('Location'))
                    ->searchable(),
                TextColumn::make('matter.number')
                    ->label(__('Matter'))
                    ->searchable(),
            ])
            ->headerActions([
                Action::make('importFromOutlook')
                    ->label(__('Import from Outlook'))
                    ->action(function () {
                        try {
                            $count = app(OutlookCalendarService::class)->importEventsToDatabase();
                            Notification::make()
                                ->title(__('Import successful'))
                                ->body(__(':count events imported/updated.', ['count' => $count]))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('Import failed'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->color('info')
                    ->icon('heroicon-o-cloud-arrow-down'),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
