<?php

namespace App\Filament\Resources\Matters\Tables;

use App\Enums\MatterCollectionStatus;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MattersTable
{
    private static function splitSearch(string $search): array
    {
        return array_values(array_filter(
            preg_split('/[\s\/\\\\\-]+/', trim($search)),
            fn($token) => strlen($token) > 0
        ));
    }

    private static function applyMultiWordSearch(Builder $query, string $search, array $columns): Builder
    {
        $tokens = static::splitSearch($search);
        foreach ($tokens as $token) {
            $query->where(function (Builder $q) use ($token, $columns) {
                foreach ($columns as $i => $column) {
                    $method = $i === 0 ? 'where' : 'orWhere';
                    if (str_contains($column, '.')) {
                        [$relation, $col] = explode('.', $column, 2);
                        $q->{$i === 0 ? 'whereHas' : 'orWhereHas'}($relation, fn($r) => $r->where($col, 'like', "%{$token}%"));
                    } else {
                        $q->{$method}($column, 'like', "%{$token}%");
                    }
                }
            });
        }
        return $query;
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->striped(false)
            ->extraAttributes(['class' => '[&_td]:py-1 [&_th]:py-1 [&_table]:text-sm [&_table]:table-fixed [&_table]:w-full'])
            ->columns([

                TextColumn::make('reference')
                    ->label('Matter')
                    ->getStateUsing(fn($record) => $record->year . '/' . $record->number)
                    ->weight(FontWeight::Bold)
                    ->description(fn($record) => collect([
                        $record->status?->getLabel(),
                        $record->collection_status?->getLabel(),
                    ])->filter()->join(' · '))
                    ->searchable(query: function (Builder $query, string $search) {
                        $tokens = static::splitSearch($search);
                        if (count($tokens) === 2 && is_numeric($tokens[0]) && is_numeric($tokens[1])) {
                            return $query->where('year', $tokens[0])
                                ->where('number', 'like', "%{$tokens[1]}%");
                        }
                        return static::applyMultiWordSearch($query, $search, ['year', 'number']);
                    })
                    ->grow(false)
                    ->width('7%'),

                TextColumn::make('court.name')
                    ->label('Court / Type')
                    ->description(fn($record) => $record->type?->name)
                    ->searchable(query: fn(Builder $query, string $search) =>
                    static::applyMultiWordSearch($query, $search, ['court.name', 'type.name'])
                    )
                    ->wrap()
                    ->grow(false)
                    ->width('12%'),

                TextColumn::make('level')
                    ->label('Level / Difficulty')
                    ->badge()
                    ->description(fn($record) => collect([
                        $record->difficulty?->getLabel(),
                        $record->commissioning ? 'Committee' : null,
                    ])->filter()->join(' · '))
                    ->searchable(query: fn(Builder $query, string $search) =>
                    static::applyMultiWordSearch($query, $search, ['level', 'difficulty', 'commissioning'])
                    )
                    ->sortable()
                    ->grow(false)
                    ->width('10%'),

                TextColumn::make('indexedParties')
                    ->label('Parties')
                    ->listWithLineBreaks()
                    ->getStateUsing(fn($record) => $record->indexedParties
                        ->map(fn($mp) => sprintf(
                            '%s #%d — %s',
                            ucfirst(str_replace('-', ' ', $mp->type ?? '')),
                            $mp->role_index,
                            $mp->party?->name ?? '—'
                        ))
                        ->toArray()
                    )
                    ->searchable(query: function (Builder $query, string $search) {
                        $tokens = static::splitSearch($search);
                        foreach ($tokens as $token) {
                            $query->whereHas('mainPartiesOnly.party', fn($q) =>
                            $q->where('name', 'like', "%{$token}%")
                            );
                        }
                        return $query;
                    })
                    ->wrap()
                    ->width('26%'),

                TextColumn::make('indexedExperts')
                    ->label('Experts')
                    ->listWithLineBreaks()
                    ->getStateUsing(fn($record) => $record->indexedExperts
                        ->map(fn($mp) => sprintf(
                            '%s #%d — %s',
                            ucfirst(str_replace('-', ' ', $mp->type ?? '')),
                            $mp->role_index,
                            $mp->party?->name ?? '—'
                        ))
                        ->toArray()
                    )
                    ->searchable(query: function (Builder $query, string $search) {
                        $tokens = static::splitSearch($search);
                        foreach ($tokens as $token) {
                            $query->whereHas('mainExpertsOnly.party', fn($q) =>
                            $q->where('name', 'like', "%{$token}%")
                            );
                        }
                        return $query;
                    })
                    ->wrap()
                    ->width('20%')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('fees_summary')
                    ->label('Fees / Collected')
                    ->getStateUsing(fn($record) => number_format($record->fees->sum('amount'), 2))
                    ->description(fn($record) => number_format(
                        $record->fees->sum(fn($fee) => $fee->allocations->sum('amount')), 2
                    ))
                    ->color(function ($record) {
                        $total     = (float) $record->fees->sum('amount');
                        $collected = (float) $record->fees->sum(fn($fee) => $fee->allocations->sum('amount'));
                        if ($total <= 0)          return 'gray';
                        if ($collected >= $total) return 'success';
                        if ($collected > 0)       return 'warning';
                        return 'danger';
                    })
                    ->searchable(false)
                    ->grow(false)
                    ->width('10%'),

                TextColumn::make('next_session_date')
                    ->label('Next Session')
                    ->date()
                    ->description(fn($record) => $record->last_action_date
                        ? 'Last: ' . Carbon::parse($record->last_action_date)->format('M d,Y')
                        : null
                    )
                    ->sortable()
                    ->width('10%'),

                TextColumn::make('received_date')
                    ->date()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reported_date')
                    ->date()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('submitted_date')
                    ->date()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('external_marketing_rate')
                    ->numeric()->sortable()
                    ->searchable(query: fn(Builder $query, string $search) =>
                    static::applyMultiWordSearch($query, $search, ['external_marketing_rate'])
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('claim_status')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

                SelectFilter::make('collection_status')
                    ->label('Collection Status')
                    ->options(MatterCollectionStatus::class)
                    ->multiple()
                    ->searchable()
                    ->preload(),

                SelectFilter::make('commissioning')
                    ->label('Commissioning')
                    ->options([
                        'individual' => 'Individual',
                        'committee'  => 'Committee',
                    ])
                    ->multiple(),

                Filter::make('received_date')
                    ->label('Received Date')
                    ->schema([
                        Fieldset::make('Received Date')->schema([
                            DatePicker::make('received_from')->label('From'),
                            DatePicker::make('received_until')->label('Until'),
                        ])->columns(2),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['received_from'],  fn($q, $v) => $q->whereDate('received_date', '>=', $v))
                            ->when($data['received_until'], fn($q, $v) => $q->whereDate('received_date', '<=', $v));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['received_from'])  $indicators[] = 'Received from: '  . $data['received_from'];
                        if ($data['received_until']) $indicators[] = 'Received until: ' . $data['received_until'];
                        return $indicators;
                    }),

                Filter::make('last_action_date')
                    ->label('Last Action Date')
                    ->schema([
                        Fieldset::make('Last Action Date')->schema([
                            DatePicker::make('last_action_from')->label('From'),
                            DatePicker::make('last_action_until')->label('Until'),
                        ])->columns(2),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['last_action_from'],  fn($q, $v) => $q->whereDate('last_action_date', '>=', $v))
                            ->when($data['last_action_until'], fn($q, $v) => $q->whereDate('last_action_date', '<=', $v));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['last_action_from'])  $indicators[] = 'Last action from: '  . $data['last_action_from'];
                        if ($data['last_action_until']) $indicators[] = 'Last action until: ' . $data['last_action_until'];
                        return $indicators;
                    }),

                Filter::make('next_session_date')
                    ->label('Next Session Date')
                    ->schema([
                        Fieldset::make('Next Session Date')->schema([
                            DatePicker::make('next_session_from')->label('From'),
                            DatePicker::make('next_session_until')->label('Until'),
                        ])->columns(2),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['next_session_from'],  fn($q, $v) => $q->whereDate('next_session_date', '>=', $v))
                            ->when($data['next_session_until'], fn($q, $v) => $q->whereDate('next_session_date', '<=', $v));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['next_session_from'])  $indicators[] = 'Next session from: '  . $data['next_session_from'];
                        if ($data['next_session_until']) $indicators[] = 'Next session until: ' . $data['next_session_until'];
                        return $indicators;
                    }),

                Filter::make('reported_date')
                    ->label('Reported Date')
                    ->schema([
                        Fieldset::make('Reported Date')->schema([
                            DatePicker::make('reported_from')->label('From'),
                            DatePicker::make('reported_until')->label('Until'),
                        ])->columns(2),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['reported_from'],  fn($q, $v) => $q->whereDate('reported_date', '>=', $v))
                            ->when($data['reported_until'], fn($q, $v) => $q->whereDate('reported_date', '<=', $v));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['reported_from'])  $indicators[] = 'Reported from: '  . $data['reported_from'];
                        if ($data['reported_until']) $indicators[] = 'Reported until: ' . $data['reported_until'];
                        return $indicators;
                    }),

                Filter::make('submitted_date')
                    ->label('Submitted Date')
                    ->schema([
                        Fieldset::make('Submitted Date')->schema([
                            DatePicker::make('submitted_from')->label('From'),
                            DatePicker::make('submitted_until')->label('Until'),
                        ])->columns(2),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['submitted_from'],  fn($q, $v) => $q->whereDate('submitted_date', '>=', $v))
                            ->when($data['submitted_until'], fn($q, $v) => $q->whereDate('submitted_date', '<=', $v));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['submitted_from'])  $indicators[] = 'Submitted from: '  . $data['submitted_from'];
                        if ($data['submitted_until']) $indicators[] = 'Submitted until: ' . $data['submitted_until'];
                        return $indicators;
                    }),

                Filter::make('fees_amount')
                    ->label('Fees Amount')
                    ->schema([
                        Fieldset::make('Fees Amount')->schema([
                            TextInput::make('fees_from')
                                ->label('Min Amount')
                                ->numeric()
                                ->prefix('$')
                                ->placeholder('0.00'),
                            TextInput::make('fees_until')
                                ->label('Max Amount')
                                ->numeric()
                                ->prefix('$')
                                ->placeholder('∞'),
                        ])->columns(2),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['fees_from'],  fn($q, $v) => $q->whereHas('fees', fn($f) =>
                            $f->havingRaw('SUM(amount) >= ?', [(float) $v])
                                ->groupBy('matter_id')
                                ->select('matter_id')
                            ))
                            ->when($data['fees_until'], fn($q, $v) => $q->whereHas('fees', fn($f) =>
                            $f->havingRaw('SUM(amount) <= ?', [(float) $v])
                                ->groupBy('matter_id')
                                ->select('matter_id')
                            ));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['fees_from'])  $indicators[] = 'Fees min: $' . number_format((float) $data['fees_from'], 2);
                        if ($data['fees_until']) $indicators[] = 'Fees max: $' . number_format((float) $data['fees_until'], 2);
                        return $indicators;
                    }),

            ])
            ->filtersFormColumns(2)
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormWidth(Width::FourExtraLarge )
            ->recordActions([
                ViewAction::make()->iconButton(),
                EditAction::make()->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
