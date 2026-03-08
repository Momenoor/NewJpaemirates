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
            ->extraAttributes(['class' => '[&_td]:py-1 [&_th]:py-1'])
            ->columns([
                TextColumn::make('year')->grow(false),
                TextColumn::make('number')
                    ->grow(false)
                    ->searchable(),
                IconColumn::make('difficulty')
                    ->grow(false)
                    ->icon(fn($state) => $state?->getIcon())
                    ->searchable()
                    ->tooltip(fn($state) => $state?->getLabel()),
                TextColumn::make('commissioning')
                    ->grow(false)
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
                    ->grow(false)
                    ->searchable(),
                TextColumn::make('level')
                    ->grow(false)
                    ->badge()
                    ->sortable(),
                TextColumn::make('type.name')
                    ->grow(false)
                    ->searchable(),
                TextColumn::make('parent_id')
                    ->numeric()
                    ->grow(false)
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
