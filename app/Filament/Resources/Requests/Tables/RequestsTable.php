<?php

namespace App\Filament\Resources\Requests\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('matter')
                    ->formatStateUsing(fn($record) => $record->matter->number . '/' . $record->matter->year)
                    ->url(fn($record) => route('filament.admin.resources.matters.view', $record->matter_id))
                    ->label(__('Matter'))
                    ->searchable(),
                TextColumn::make('requestBy.name')
                    ->label(__('Request By'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->label(__('Status'))
                    ->searchable(),
                TextColumn::make('comment')
                    ->label(__('Comment'))
                    ->searchable(),
                TextColumn::make('approvedBy.name')
                    ->numeric()
                    ->label(__('Approved By'))
                    ->sortable(),
                TextColumn::make('approved_at')
                    ->label(__('Approved At'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('approved_comment')
                    ->label(__('Approved Comment'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('Updated'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([

            ])
            ->toolbarActions([

            ]);
    }
}
