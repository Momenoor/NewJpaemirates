<?php

namespace App\Filament\Resources\Types\Schemas;

use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required(),
                Toggle::make('active')
                    ->label(__('Active'))
                    ->default(true)
                    ->required(),
            ]);
    }
}
