<?php

namespace App\Filament\Resources\Matters\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class MatterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    TextInput::make('year')
                        ->required(),
                    TextInput::make('number')
                        ->required(),
                    DatePicker::make('received_date'),
                    DatePicker::make('next_session_date'),
                    Select::make('court_id')
                        ->relationship('court', 'name')
                        ->required(),
                    Select::make('level_id')
                        ->options([
                            'first_instance' => 'First Instance',
                            'appeal' => 'Appeal',
                            'congestion' => 'Congestion',
                        ])
                        ->required(),
                    Select::make('type_id')
                        ->relationship('type', 'name')
                        ->required(),
                    Select::make('difficulty')
                        ->options([
                            'simple' => 'Simple',
                            'medium' => 'Medium',
                            'exceptional' => 'Exceptional',
                        ])
                        ->required(),
                    Checkbox::make('commissioning')
                        ->label('Is Committee?')
                        ->required(),
                ])->label('Basic Data')
                    ->columns(4)
                    ->columnSpanFull()
                    ->grow(false),
                Section::make([
                    Tabs::make('Management')
                        ->tabs([
                            Tabs\Tab::make('Parties')
                                ->schema([
                                    Repeater::make('matterParties')
                                        ->relationship('matterParties') // Defined in your Matter model
                                        ->schema([
                                            // 1. Select the Party (Person/Company)
                                            Select::make('party_id')
                                                ->label('Party Name')
                                                ->relationship('party', 'name')
                                                ->searchable()
                                                ->preload()
                                                ->required(),

                                            // 2. Select their Role
                                            Select::make('type')
                                                ->label('Role')
                                                ->options([
                                                    'plaintiff' => 'Plaintiff',
                                                    'defendant' => 'Defendant',
                                                    'lawyer' => 'Lawyer',
                                                    'expert' => 'Expert',
                                                ])
                                                ->required()
                                                ->live(), // Essential for Filament 5 reactivity

                                            // 3. Conditional "Represents" Field
                                            Select::make('parent_id')
                                                ->label('Representing')
                                                ->placeholder('Select the party they represent')
                                                // Only show if the role is 'Lawyer'
                                                ->visible(fn(Get $get) => $get('type') === 'lawyer')
                                                ->options(function (Get $get, $livewire) {
                                                    // Logic to fetch other parties in the SAME matter
                                                    // You can pull from the database if editing, or from current state
                                                    return $livewire->getOwnerRecord()
                                                        ?->parties()
                                                        ->wherePivotIn('type', ['plaintiff', 'defendant'])
                                                        ->pluck('name', 'parties.id') ?? [];
                                                })
                                                ->searchable(),
                                        ])
                                        ->columns(3)
                                        ->itemLabel(fn(array $state): ?string => $state['type'] ?? 'New Party'),
                                ]),
                            Tabs\Tab::make('Claims/Fees')
                                ->schema([
                                    Repeater::make('claims')
                                        ->relationship('claims')
                                        ->schema([
                                            TextInput::make('amount')->numeric()->prefix('$'),
                                            TextInput::make('type'),
                                        ])->columns(2),
                                ]),
                        ]),
                ])
            ]);
    }
}
