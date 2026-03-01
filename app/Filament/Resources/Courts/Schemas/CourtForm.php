<?php

namespace App\Filament\Resources\Courts\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CourtForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required(),
                TextInput::make('phone'),
                TextInput::make('email'),
                Textarea::make('address'),
            ]);
    }
}
