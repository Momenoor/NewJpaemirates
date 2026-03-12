<?php

namespace App\Filament\Resources\Parties\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class PartyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('phone')
                            ->label(__('Phone'))
                            ->tel(),
                        TextInput::make('email')
                            ->label(__('Email address'))
                            ->email(),
                        TextInput::make('fax')
                            ->label(__('Fax')),
                        CheckboxList::make('role.role')
                            ->label(__('Role'))
                            ->options([
                                'party' => __('Party'),
                                'expert' => __('Expert'),
                                'representative' => __('Representative'),
                            ])
                            ->default(['party'])
                            ->required()
                            ->columns(3)
                            ->columnSpanFull()
                            ->live(),
                        CheckboxList::make('role.type')
                            ->label(__('Expert Type'))
                            ->options([
                                'certified' => __('Certified Expert'),
                                'assistant' => __('Assistant Expert'),
                                'external' => __('External Expert'),
                                'external-assistant' => __('External Assistant'),
                            ])->required(fn($get) => in_array('expert', $get('role.role')))
                            ->visible(fn($get) => in_array('expert', $get('role.role') ?? []))
                            ->columns(2)
                            ->columnSpanFull(),
                        Select::make('role.field')
                            ->options([
                                'accounting' => __('Accounting'),
                                'finance' => __('Finance'),
                                'technology' => __('Technology'),
                                'engineering' => __('Engineering'),
                                'architecture' => __('Architecture'),
                                'civil' => __('Civil'),
                                'it' => __('IT'),
                                'banking' => __('Banking'),
                            ])
                            ->label(__('Expertise Area'))
                            ->visible(fn($get) => in_array('expert', $get('role.role') ?? []))
                            ->columnSpanFull(),
                        Toggle::make('black_list')
                            ->label(__('Black List'))
                            ->default(false)
                            ->dehydrateStateUsing(fn($state) => (bool) $state ? 1 : 0)
                            ->required(),
                        Textarea::make('address')
                            ->label(__('Address'))
                            ->columnSpanFull(),
                        Textarea::make('extra')
                            ->label(__('Extra'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
