<?php

namespace App\Filament\Resources\MatterRequests\Tables;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Filament\Actions\Request\ApproveRequestAction;
use App\Filament\Actions\Request\RejectRequestAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MatterRequestsTable
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
                    ->wrap()
                    ->searchable(),
                TextColumn::make('approvedBy.name')
                    ->numeric()
                    ->label(__('Handled By'))
                    ->sortable(),
                TextColumn::make('approved_at')
                    ->label(__('Handled At'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('approved_comment')
                    ->label(__('Handling Note'))
                    ->wrap()
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
                SelectFilter::make('status')
                    ->options(RequestStatus::class)
                    ->multiple()
                    ->label(__('Status'))
                    ->default([RequestStatus::PENDING, RequestStatus::DISPUTED]),
                SelectFilter::make('type')
                    ->options(RequestType::class)
                    ->multiple()
                    ->label(__('Type')),
            ])
            ->recordActions([
                ApproveRequestAction::make(),
                RejectRequestAction::make(),
            ])
            ->toolbarActions([

            ]);
    }
}
