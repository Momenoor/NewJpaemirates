<?php

namespace App\Filament\Resources\Incentive\MatterTypeIncentiveConfigs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MatterTypeIncentiveConfigsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('matterType.name')->label(__('Matter Type'))->sortable()->searchable(),
                TextColumn::make('calculation_type')->label(__('Type'))->badge()
                    ->color(fn($state) => match($state) {
                        'fixed' => 'success', 'tiered' => 'info', 'committee' => 'warning', default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match($state) {
                        'fixed' => __('Fixed'), 'tiered' => __('Tiered'), 'committee' => __('Committee'), default => $state,
                    }),
                TextColumn::make('fixed_percentage')->label(__('Fixed %'))->suffix('%')->placeholder('—'),
                TextColumn::make('committee_source')->label(__('Committee'))
                    ->formatStateUsing(fn($state) => match($state) {
                        'office' => __('Office (+2%)'), 'external' => __('External (−2%)'), default => '—',
                    })->placeholder('—'),
                TextColumn::make('assistant_rate')->label(__('Assistant Rate'))->suffix('%'),
                TextColumn::make('tiers_count')->counts('tiers')->label(__('Tiers')),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()->iconButton(),
                EditAction::make()->iconButton(),
            ])
            ->toolbarActions([

            ]);
    }
}
