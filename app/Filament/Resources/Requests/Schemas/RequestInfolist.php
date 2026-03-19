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
                TextEntry::make('matter.id')
                    ->label('Matter'),
                TextEntry::make('request_by')
                    ->numeric(),
                TextEntry::make('status'),
                TextEntry::make('comment')
                    ->placeholder('-'),
                TextEntry::make('approved_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('approved_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('approved_comment')
                    ->placeholder('-'),
                TextEntry::make('type'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
