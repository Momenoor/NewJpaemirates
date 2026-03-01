<?php

namespace App\Filament\Resources\Matters\Tables;

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
            ->columns([
                TextColumn::make('year'),
                TextColumn::make('number')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('commissioning')
                    ->searchable(),
                IconColumn::make('assign')
                    ->boolean(),
                TextColumn::make('received_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('next_session_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('reported_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('submitted_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('external_marketing_rate')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('expert.id')
                    ->searchable(),
                TextColumn::make('court.name')
                    ->searchable(),
                TextColumn::make('level_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('type.name')
                    ->searchable(),
                TextColumn::make('parent_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('parties.name')
                    ->listWithLineBreaks() // Nicer than bulleted for professional UIs
                    ->formatStateUsing(function ($state, $record) {
                        // $state is the 'name' of the party because of 'parties.name'
                        // We find the specific party in the loaded relationship to get pivot data
                        $party = $record->parties->firstWhere('name', $state);

                        return ($party->pivot->role ?? null). '-' .($party->pivot->type ?? null) . ' - ' . $state;
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
                    ->badge(),
                TextColumn::make('last_action_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('level')
                    ->searchable(),
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
