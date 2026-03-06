<?php

namespace App\Filament\Resources\Matters\Schemas;

use App\Enums\MatterDifficulty;
use App\Enums\MatterLevel;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

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
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('level')
                        ->options(MatterLevel::class)
                        ->required(),
                    Select::make('type_id')
                        ->relationship('type', 'name', modifyQueryUsing: function ($query) {
                            $query->where('active', true);
                        })
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('difficulty')
                        ->options(MatterDifficulty::class)
                        ->required(),
                    Toggle::make('commissioning')
                        ->label('Is Committee?')
                        ->live()
                        ->required(),
                ])
                    ->label('Basic Data')
                    ->columns(4)
                    ->columnSpanFull(),

                Section::make('Parties')
                    ->schema([
                        Repeater::make('matterParties')

                            // mainParties → HasMany scoped to whereNull('parent_id')
                            // so representative rows never appear as top-level items.
                            ->relationship('mainParties')
                            ->schema([

                                // 1. Type — drives role and filters the party select below
                                Select::make('type')
                                    ->label('Type')
                                    ->options(function (Get $get) {
                                        $isCommittee = $get('../../commissioning');
                                        $options = [
                                            'plaintiff'          => 'Plaintiff',
                                            'defendant'          => 'Defendant',
                                            'implicate-litigant' => 'Implicate Litigant',
                                            'certified'          => 'Certified Expert',
                                            'assistant'          => 'Assistant Expert',
                                        ];
                                        if ($isCommittee) {
                                            $options['external']           = 'External Expert';
                                            $options['external-assistant'] = 'External Assistant';
                                        }
                                        return $options;
                                    })
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, $state) {
                                        if (in_array($state, ['plaintiff', 'defendant', 'implicate-litigant'])) {
                                            $set('role', 'party');
                                        } elseif (in_array($state, ['external', 'external-assistant', 'certified', 'assistant'])) {
                                            $set('role', 'expert');
                                        }
                                        // Reset downstream fields on type change
                                        $set('party_id', null);
                                        $set('representatives', []);
                                    }),

                                // 2. Party — filtered by role/type via LIKE on JSON role column
                                Select::make('party_id')
                                    ->label('Party Name')
                                    ->relationship('party', 'name', function ($query, Get $get) {
                                        $role = $get('role');
                                        $type = $get('type');
                                        if ($role === 'party') {
                                            $query->where('role', 'like', '%"party"%');
                                        } elseif ($type) {
                                            $query->where('role', 'like', '%"' . $type . '"%');
                                        }
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(2)
                                    ->required()
                                    ->live(),

                                // 3. Hidden matter_id and role
                                Hidden::make('matter_id'),
                                Hidden::make('role'),
                                Hidden::make('id'),

                                // 4. Representatives — only shown when role = 'party'
                                //
                                // How parent_id works here:
                                //   parent_id → matter_party.id (the matter party record being represented)
                                //
                                //   matter_party row (plaintiff):   id=1, party_id=10, parent_id=NULL
                                //   matter_party row (rep of #10):  id=2, party_id=22, parent_id=1
                                //
                                Repeater::make('representatives')
                                    ->table([
                                        Repeater\TableColumn::make('name'),
                                    ])
                                    ->compact()
                                    ->relationship('representatives')
                                    ->label('Representatives / Lawyers')
                                    ->schema([

                                        Select::make('party_id')
                                            ->label('Representative')
                                            ->relationship('party', 'name', function ($query) {
                                                $query->where('role', 'like', '%"representative"%');
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->columnSpanFull(),

                                        // role is always 'representative' for these rows
                                        Hidden::make('role')
                                            ->default('representative'),

                                        // type mirrors the parent party's type
                                        Hidden::make('type')
                                            ->dehydrateStateUsing(fn(Get $get) => $get('../../type')),

                                        // KEY: parent_id = the parent MatterParty record ID
                                        Hidden::make('parent_id')
                                            ->dehydrateStateUsing(fn(Get $get) => $get('../../id')),

                                        // matter_id mirrors the parent party's matter_id
                                        Hidden::make('matter_id')
                                            ->dehydrateStateUsing(fn(Get $get) => $get('../../matter_id')),
                                    ])
                                    ->visible(fn(Get $get) => $get('role') === 'party')
                                    ->addActionLabel('Add Representative')
                                    ->compact()
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->addActionLabel('Add Party')
                            ->itemLabel(function (array $state): ?string {
                                $type = $state['type'] ?? '';
                                $role = $state['role'] ?? '';
                                return $type ? "{$type} ({$role})" : null;
                            })
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
