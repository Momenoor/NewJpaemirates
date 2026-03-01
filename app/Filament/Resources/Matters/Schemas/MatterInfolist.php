<?php

namespace App\Filament\Resources\Matters\Schemas;

use App\Models\Matter;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MatterInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('year'),
                TextEntry::make('number'),
                TextEntry::make('status'),
                TextEntry::make('commissioning'),
                IconEntry::make('assign')
                    ->boolean(),
                TextEntry::make('received_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('next_session_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('reported_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('submitted_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('external_marketing_rate')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('user_id')
                    ->numeric(),
                TextEntry::make('expert.id')
                    ->label('Expert'),
                TextEntry::make('court.name')
                    ->label('Court'),
                TextEntry::make('level_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('type.name')
                    ->label('Type'),
                TextEntry::make('parent_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('claim_status')
                    ->badge(),
                TextEntry::make('last_action_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('level')
                    ->placeholder('-'),
            ]);
    }
}
