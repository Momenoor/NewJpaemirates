<?php

namespace App\Filament\Resources\Requests\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('matter')
                    ->url(fn($record) => route('filament.admin.resources.matters.view', $record->matter_id))
                    ->formatStateUsing(fn($state) => $state->number . '/' . $state->year)
                    ->label(__('Matter')),
                TextEntry::make('requestBy.name')
                    ->label(__('Request By'))
                    ->numeric(),
                TextEntry::make('status')
                ->badge()
                ->label(__('Status')),
                TextEntry::make('comment')
                    ->label(__('Comment'))
                    ->placeholder('-'),
                TextEntry::make('approvedBy.name')
                    ->numeric()
                    ->label(__('Approved By'))
                    ->placeholder('-'),
                TextEntry::make('approved_at')
                    ->label(__('Approved At'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('approved_comment')
                    ->label(__('Approved Comment'))
                    ->placeholder('-'),
                TextEntry::make('type')
                ->badge()
                ->label(__('Type')),
                TextEntry::make('created_at')
                    ->label(__('Created'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label(__('Updated'))
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
