<?php

namespace App\Filament\Resources\Courts\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CourtInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')->label('Name'),
                TextEntry::make('phone')->label('Phone'),
                TextEntry::make('email')->label('Email'),
                TextEntry::make('address')->label('Address'),
            ]);
    }
}
