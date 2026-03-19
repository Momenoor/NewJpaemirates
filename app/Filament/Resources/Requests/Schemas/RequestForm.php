<?php

namespace App\Filament\Resources\Requests\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('matter_id')
                    ->relationship('matter', 'id')
                    ->required(),
                TextInput::make('request_by')
                    ->required()
                    ->numeric(),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('comment'),
                TextInput::make('approved_by')
                    ->numeric(),
                DateTimePicker::make('approved_at'),
                TextInput::make('approved_comment'),
                TextInput::make('extra'),
                TextInput::make('type')
                    ->required(),
            ]);
    }
}
