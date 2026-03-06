<?php

namespace App\Filament\Resources\Matters\Tables;

use App\Enums\MatterStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MattersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped(false)
            ->recordClasses(fn($record) => match ($record->status?->value ?? $record->status) {
                'current' => 'bg-info-50 dark:bg-info-900',
                'reported' => 'bg-success-50 dark:bg-success-900',
                'submitted' => 'bg-warning-50 dark:bg-warning-900',
                default => null,
            })
            ->columns([
                TextColumn::make('year'),
                TextColumn::make('number')
                    ->searchable(),
                IconColumn::make('difficulty')
                    ->icon(fn($state) => $state?->getIcon())
                    ->searchable()
                    ->tooltip(fn($state) => $state?->getLabel()),
                TextColumn::make('commissioning')
                    ->searchable(),
                TextColumn::make('received_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('next_session_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reported_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('submitted_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('external_marketing_rate')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('court.name')
                    ->searchable(),
                TextColumn::make('level')
                    ->badge()
                    ->sortable(),
                TextColumn::make('type.name')
                    ->searchable(),
                TextColumn::make('parent_id')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('parties.name')
                    ->listWithLineBreaks() // Nicer than bulleted for professional UIs
                    ->formatStateUsing(function ($state, $record) {
                        // $state is the 'name' of the party because of 'parties.name'
                        // We find the specific party in the loaded relationship to get pivot data
                        $party = $record->parties->firstWhere('name', $state);
                        if (!$party || !$party->pivot) return $state;

                        $role = $party->pivot->role ?? '';
                        $type = $party->pivot->type ?? '';

                        return "{$role} ({$type}) - {$state}";
                    })
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('claim_status')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('collection_status')
                    ->badge(),
                TextColumn::make('last_action_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
