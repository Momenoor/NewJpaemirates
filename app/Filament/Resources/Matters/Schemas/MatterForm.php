<?php

namespace App\Filament\Resources\Matters\Schemas;

use App\Enums\MatterDifficulty;
use App\Enums\MatterLevel;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

class MatterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Group::make()
                            ->schema([
                                Section::make('Basic Data')->schema([
                                    TextInput::make('year')
                                        ->required(),
                                    TextInput::make('number')
                                        ->required(),
                                    DatePicker::make('received_date'),
                                    DatePicker::make('next_session_date'),
                                ])->columns(2),
                                Section::make('Court Data')->schema([
                                    Select::make('court_id')
                                        ->relationship('court', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->columnSpanFull(),
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
                                        ->inline(false)
                                        ->required(),
                                ]),
                            ])
                            ->columns(1)
                            ->columnSpan(1),

                        Group::make()
                            ->schema([
                                Section::make('Parties & Experts')
                                    ->schema([
                                        Repeater::make('mainExpertsOnly')
                                            ->columns(3)
                                            ->table([
                                                Repeater\TableColumn::make('type')->width(250),
                                                Repeater\TableColumn::make('name'),
                                            ])
                                            ->compact()
                                            ->relationship('mainExpertsOnly')
                                            ->label('Experts')
                                            ->schema([
                                                Select::make('type')
                                                    ->label('Type')
                                                    ->options(function (Get $get) {
                                                        $isCommittee = $get('../../../commissioning');
                                                        $options = [
                                                            'certified' => 'Certified Expert',
                                                            'assistant' => 'Assistant Expert',
                                                        ];
                                                        if ($isCommittee) {
                                                            $options['external'] = 'External Expert';
                                                            $options['external-assistant'] = 'External Assistant';
                                                        }
                                                        return $options;
                                                    })
                                                    ->required()
                                                    ->live()
                                                    ->columnSpan(1)
                                                    ->afterStateUpdated(function (Set $set) {
                                                        $set('role', 'expert');
                                                        $set('party_id', null);
                                                    }),

                                                Select::make('party_id')
                                                    ->label('Expert Name')
                                                    ->relationship('party', 'name', function ($query, Get $get) {
                                                        $type = $get('type');
                                                        if ($type) {
                                                            $query->where('role', 'like', '%"' . $type . '"%');
                                                        }

                                                        $selectedPartyIds = collect($get('../../mainExpertsOnly'))
                                                            ->pluck('party_id')
                                                            ->filter()
                                                            ->forget($get('id')) // Ignore self if editing
                                                            ->all();

                                                        if ($selectedPartyIds) {
                                                            $query->whereNotIn('id', $selectedPartyIds);
                                                        }
                                                    })
                                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                                    ->columnSpan(2)
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->live(),

                                                Hidden::make('matter_id'),
                                                Hidden::make('role')
                                                    ->default('expert'),
                                                Hidden::make('id'),
                                            ])
                                            ->columns(3)
                                            ->addActionLabel('Add Expert')
                                            ->itemLabel(function (array $state): ?string {
                                                $type = $state['type'] ?? '';
                                                $role = $state['role'] ?? '';
                                                return $type ? "{$type} ({$role})" : null;
                                            })
                                            ->addAction(
                                                fn(Action $action) => $action
                                                    ->color('warning')
                                                    ->icon('heroicon-m-plus-circle')
                                            )
                                            ->addActionAlignment(Alignment::Start),
                                        Repeater::make('mainPartiesOnly')
                                            ->relationship('mainPartiesOnly')
                                            ->label('Parties')
                                            ->schema([
                                                Select::make('type')
                                                    ->label('Type')
                                                    ->options([
                                                        'plaintiff' => 'Plaintiff',
                                                        'defendant' => 'Defendant',
                                                        'implicate-litigant' => 'Implicate Litigant',
                                                    ])
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set) {
                                                        $set('role', 'party');
                                                        $set('party_id', null);
                                                        $set('representatives', []);
                                                    }),

                                                Select::make('party_id')
                                                    ->label('Party Name')
                                                    ->relationship('party', 'name', function ($query, Get $get) {
                                                        $query->where('role', 'like', '%"party"%');

                                                        $selectedPartyIds = collect($get('../../mainPartiesOnly'))
                                                            ->pluck('party_id')
                                                            ->filter()
                                                            ->all();

                                                        if ($selectedPartyIds) {
                                                            $query->whereNotIn('id', $selectedPartyIds);
                                                        }
                                                    })
                                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                                    ->searchable()
                                                    ->preload()
                                                    ->columnSpan(2)
                                                    ->required()
                                                    ->live(),

                                                Hidden::make('matter_id'),
                                                Hidden::make('role')
                                                    ->default('party'),
                                                Hidden::make('id'),

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
                                                            ->relationship('party', 'name', function ($query, Get $get) {
                                                                $query->where('role', 'like', '%"representative"%');

                                                                $selectedPartyIds = collect($get('../../representatives'))
                                                                    ->pluck('party_id')
                                                                    ->filter()
                                                                    ->all();

                                                                if ($selectedPartyIds) {
                                                                    $query->whereNotIn('id', $selectedPartyIds);
                                                                }
                                                            })
                                                            ->searchable()
                                                            ->preload()
                                                            ->required()
                                                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                                            ->columnSpanFull(),

                                                        Hidden::make('role')
                                                            ->default('representative'),

                                                        Hidden::make('type')
                                                            ->dehydrateStateUsing(fn(Get $get) => $get('../../type')),

                                                        Hidden::make('parent_id')
                                                            ->dehydrateStateUsing(fn(Get $get) => $get('../../id')),

                                                        Hidden::make('matter_id')
                                                            ->dehydrateStateUsing(fn(Get $get) => $get('../../matter_id')),
                                                    ])
                                                    ->addAction(
                                                        fn(Action $action) => $action
                                                            ->color('warning')
                                                            ->icon('heroicon-m-plus-circle')
                                                    )
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
                                            ->addActionAlignment(Alignment::Start)
                                            ->addAction(
                                                fn(Action $action) => $action
                                                    ->color('warning')
                                                    ->icon('heroicon-m-plus-circle')
                                            ),
                                    ]),

                                Section::make('Fees')
                                    ->schema([
                                        Repeater::make('fees')
                                            ->relationship('fees')
                                            ->table([
                                                Repeater\TableColumn::make('Type'),
                                                Repeater\TableColumn::make('Amount'),
                                                Repeater\TableColumn::make('Including Vat')
                                                    ->alignment(Alignment::Center),
                                            ])
                                            ->compact()
                                            ->schema([
                                                Select::make('type')
                                                    ->options([
                                                        'expert' => 'Expert Fee',
                                                        'marketing' => 'Marketing',
                                                        'other' => 'Other',
                                                    ])
                                                    ->required(),

                                                TextInput::make('amount')
                                                    ->numeric()
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                        // Only recalculate if this row has including_vat ON
                                                        if (!$get('including_vat')) return;

                                                        $amount = (float)$state;
                                                        if ($amount <= 0) return;

                                                        $rowId = $get('row_id');
                                                        $fees = $get('../../fees') ?? [];

                                                        // Find current row key and position
                                                        $currentKey = null;
                                                        foreach ($fees as $key => $fee) {
                                                            if (($fee['row_id'] ?? null) === $rowId) {
                                                                $currentKey = $key;
                                                                break;
                                                            }
                                                        }

                                                        if ($currentKey === null) return;

                                                        $keys = array_keys($fees);
                                                        $currentPos = array_search($currentKey, $keys, true);
                                                        $nextKey = $keys[$currentPos + 1] ?? null;

                                                        // Check the next row is the linked VAT row
                                                        if (!$nextKey || ($fees[$nextKey]['type'] ?? '') !== 'vat') return;

                                                        $basic = round($amount / 1.05, 2);
                                                        $vat = round($amount - $basic, 2);

                                                        // Update base row amount
                                                        $set('amount', $basic);
                                                        $fees[$currentKey]['amount'] = $basic;

                                                        // Update linked VAT row amount
                                                        $fees[$nextKey]['amount'] = $vat;

                                                        $set('../../fees', $fees);
                                                    }),

                                                Toggle::make('including_vat')
                                                    ->live()
                                                    ->inline(false)
                                                    ->extraAttributes(['style' => 'margin: 0 1rem;'])
                                                    ->disabled(fn(Get $get) => $get('type') === 'vat')
                                                    ->afterStateUpdated(function (bool $state, Set $set, Get $get) {
                                                        $amount = (float)$get('amount');
                                                        $rowId = $get('row_id');
                                                        $fees = $get('../../fees') ?? [];

                                                        // Find current row key and position
                                                        $currentKey = null;
                                                        foreach ($fees as $key => $fee) {
                                                            if (($fee['row_id'] ?? null) === $rowId) {
                                                                $currentKey = $key;
                                                                break;
                                                            }
                                                        }

                                                        if ($currentKey === null) return;

                                                        $keys = array_keys($fees);
                                                        $currentPos = array_search($currentKey, $keys, true);

                                                        if ($state) {
                                                            // ── Toggle ON ─────────────────────────────────────
                                                            if ($amount <= 0) return;

                                                            $basic = round($amount / 1.05, 2);
                                                            $vat = round($amount - $basic, 2);

                                                            $set('amount', $basic);
                                                            $fees[$currentKey]['amount'] = $basic;
                                                            $fees[$currentKey]['including_vat'] = true;

                                                            $vatRow = [
                                                                'row_id' => (string)\Illuminate\Support\Str::uuid(),
                                                                'type' => 'vat',
                                                                'amount' => $vat,
                                                                'including_vat' => false,
                                                            ];

                                                            // Insert VAT row immediately after current row
                                                            $newFees = [];
                                                            foreach ($fees as $k => $fee) {
                                                                $newFees[$k] = $fee;
                                                                if ($k === $currentKey) {
                                                                    $newFees[(string)\Illuminate\Support\Str::uuid()] = $vatRow;
                                                                }
                                                            }

                                                            $set('../../fees', $newFees);

                                                        } else {
                                                            // ── Toggle OFF ────────────────────────────────────
                                                            $nextKey = $keys[$currentPos + 1] ?? null;

                                                            if ($nextKey && ($fees[$nextKey]['type'] ?? '') === 'vat') {
                                                                $vatAmount = (float)$fees[$nextKey]['amount'];
                                                                $restored = round($amount + $vatAmount, 2);

                                                                $set('amount', $restored);
                                                                $fees[$currentKey]['amount'] = $restored;
                                                                $fees[$currentKey]['including_vat'] = false;

                                                                unset($fees[$nextKey]);
                                                                $set('../../fees', $fees);
                                                            }
                                                        }
                                                    }),

                                                Hidden::make('row_id')
                                                    ->default(fn() => (string)\Illuminate\Support\Str::uuid()),
                                            ])
                                            ->columns(1)
                                            ->addActionLabel('Add Fee'),
                                    ]),
                            ])
                            ->columnSpan(2),
                    ])->columnSpanFull(),
            ]);
    }
}
