<?php

namespace App\Filament\Resources\Matters\Tables;

use App\Enums\MatterCollectionStatus;
use App\Filament\Resources\Matters\MatterResource;
use App\Models\Matter;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Filters\TrashedFilter;
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
            $query->where(function (Builder $query) use ($token, $columns) {
                foreach ($columns as $i => $column) {
                    $method = $i === 0 ? 'where' : 'orWhere';
                    if (str_contains($column, '.')) {
                        [$relation, $col] = explode('.', $column, 2);
                        $query->{$i === 0 ? 'whereHas' : 'orWhereHas'}($relation, fn($r) => $r->where($col, 'like', "%{$token}%"));
                    } else {
                        $query->{$method}($column, 'like', "%{$token}%");
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

                // ── Reference ─────────────────────────────────────────────────
                TextColumn::make('reference')
                    ->label(__('Matter'))
                    ->getStateUsing(fn($record) => $record->year . '/' . $record->number)
                    ->weight(FontWeight::Bold)
                    ->description(fn($record) => collect([
                        __($record->status),
                        $record->collection_status?->getLabel(),
                    ])->filter()->join(' · '))
                    ->prefix(fn($record) => $record->parent_id ? (app()->getLocale() === 'en' ? '↳ ' : ' ↲') : '')
                    ->color(fn($record) => $record->parent_id ? 'primary' : null)
                    ->extraAttributes(fn($record) => $record->parent_id
                        ? ['class' => 'pl-6 opacity-90']
                        : []
                    )
                    ->searchable(query: function (Builder $query, string $search) {
                        $tokens = static::splitSearch($search);
                        if (count($tokens) === 2 && is_numeric($tokens[0]) && is_numeric($tokens[1])) {
                            return $query->where(function ($q) use ($tokens) {
                                foreach ($tokens as $token) {
                                    $q->where(function ($inner) use ($token) {
                                        $inner->orWhere('year', 'like', "%{$token}%")
                                            ->orWhere('number', 'like', "%{$token}%");
                                    });
                                }
                            });
                        }
                        return static::applyMultiWordSearch($query, $search, ['year', 'number']);
                    })
                    ->grow(false)
                    ->width('7%'),

                // ── Court / Type ───────────────────────────────────────────────
                TextColumn::make('court.name')
                    ->label(__('Court / Type'))
                    ->description(fn($record) => $record->type?->name)
                    ->searchable(query: fn(Builder $query, string $search) => static::applyMultiWordSearch($query, $search, ['court.name', 'type.name'])
                    )
                    ->wrap()
                    ->grow(false)
                    ->width('12%'),

                // ── Level ──────────────────────────────────────────────────────
                TextColumn::make('level')
                    ->label(__('Level / Difficulty'))
                    ->badge()
                    ->description(fn($record) => collect([
                        $record->difficulty?->getLabel(),
                        $record->commissioning->getLabel(),
                    ])->filter()->join(' · '))
                    ->searchable(query: fn(Builder $query, string $search) => static::applyMultiWordSearch($query, $search, ['level', 'difficulty', 'commissioning'])
                    )
                    ->sortable()
                    ->grow(false)
                    ->width('10%'),

                // ── Parties — hidden for child rows ────────────────────────────
                TextColumn::make('indexedParties')
                    ->label(__('Parties'))
                    ->listWithLineBreaks()
                    ->getStateUsing(fn($record) => $record->parent_id
                        ? ['—']  // child row: don't repeat parties
                        : $record->indexedParties
                            ->map(fn($mp) => sprintf(
                                '%s #%d — %s',
                                __($mp->type ? ucfirst(str_replace('-', ' ', $mp->type)) : ''),
                                $mp->role_index,
                                $mp->party?->name ?? '—'
                            ))
                            ->toArray()
                    )
                    ->color(fn($record) => $record->parent_id ? 'gray' : null)
                    ->searchable(query: function (Builder $query, string $search) {
                        $tokens = static::splitSearch($search);
                        foreach ($tokens as $token) {
                            $query->whereHas('mainPartiesOnly.party', fn($q) => $q->where('name', 'like', "%{$token}%")
                            );
                        }
                        return $query;
                    })
                    ->wrap()
                    ->width('26%')
                    ->toggleable(),

                // ── Experts ────────────────────────────────────────────────────
                TextColumn::make('indexedExperts')
                    ->label(__('Experts'))
                    ->listWithLineBreaks()
                    ->getStateUsing(fn($record) => $record->indexedExperts
                        ->map(fn($mp) => sprintf(
                            '%s #%d — %s',
                            __($mp->type ? ucfirst(str_replace('-', ' ', $mp->type)) : ''),
                            $mp->role_index,
                            $mp->party?->name ?? '—'
                        ))
                        ->toArray()
                    )
                    ->searchable(query: function (Builder $query, string $search) {
                        $tokens = static::splitSearch($search);
                        foreach ($tokens as $token) {
                            $query->whereHas('mainExpertsOnly.party', fn($q) => $q->where('name', 'like', "%{$token}%")
                            );
                        }
                        return $query;
                    })
                    ->wrap()
                    ->width('20%')
                    ->toggleable(),

                // ── Fees ───────────────────────────────────────────────────────
                TextColumn::make('fees_summary')
                    ->label(__('Fees / Collected'))
                    ->getStateUsing(fn($record) => number_format($record->fees->sum('amount'), 2))
                    ->description(fn($record) => number_format(
                        $record->fees->sum(fn($fee) => $fee->allocations->sum('amount')), 2
                    ))
                    ->color(function ($record) {
                        $total = (float)$record->fees->sum('amount');
                        $collected = (float)$record->fees->sum(fn($fee) => $fee->allocations->sum('amount'));
                        if ($total <= 0) return 'gray';
                        if ($collected >= $total) return 'success';
                        if ($collected > 0) return 'warning';
                        return 'danger';
                    })
                    ->searchable(false)
                    ->grow(false)
                    ->width('10%')
                    ->toggleable(),

                // ── Next Session ───────────────────────────────────────────────
                TextColumn::make('next_session_date')
                    ->label(__('Next Session'))
                    ->date()
                    ->description(fn($record) => $record->last_action_date
                        ? __('Last') . ': ' . Carbon::parse($record->last_action_date)->format('M d, Y')
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

                TextColumn::make('created_at')
                    ->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

                SelectFilter::make('collection_status')
                    ->label(__('Collection Status'))
                    ->options(MatterCollectionStatus::class)
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->columnSpan(2),

                SelectFilter::make('commissioning')
                    ->label(__('Commissioning'))
                    ->options([
                        'individual' => __('Individual'),
                        'committee' => __('Committee'),
                    ])
                    ->multiple()
                    ->columnSpan(2),

                SelectFilter::make('assistant_expert')
                    ->label(__('Assistant Expert'))
                    ->options(function () {
                        return \App\Models\Party::query()
                            ->whereExists(function ($query) {
                                $query->select('party_id')
                                    ->from('matter_party')
                                    ->whereColumn('matter_party.party_id', 'parties.id')
                                    ->whereIn('matter_party.type', ['assistant', 'external-assistant']);
                            })
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        return $query->whereHas('matterParties', function ($q) use ($data) {
                            $q->whereIn('party_id', $data['values'])
                                ->whereIn('type', ['assistant', 'external-assistant']);
                        });
                    })
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->columnSpan(2),

                Filter::make('received_date')
                    ->label(__('Received Date'))
                    ->schema([
                        Fieldset::make(__('Received Date'))->schema([
                            DatePicker::make('received_from')->label(__('From')),
                            DatePicker::make('received_until')->label(__('Until')),
                        ])->columns(2),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['received_from'], fn($q, $v) => $q->whereDate('received_date', '>=', $v))
                            ->when($data['received_until'], fn($q, $v) => $q->whereDate('received_date', '<=', $v));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['received_from']) $indicators[] = __('Received from') . ': ' . $data['received_from'];
                        if ($data['received_until']) $indicators[] = __('Received until') . ': ' . $data['received_until'];
                        return $indicators;
                    })
                    ->columnSpan(3),

                Filter::make('last_action_date')
                    ->label(__('Last Action Date'))
                    ->schema([
                        Fieldset::make(__('Last Action Date'))->schema([
                            DatePicker::make('last_action_from')->label(__('From')),
                            DatePicker::make('last_action_until')->label(__('Until')),
                        ])->columns(2),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['last_action_from'], fn($q, $v) => $q->whereDate('last_action_date', '>=', $v))
                            ->when($data['last_action_until'], fn($q, $v) => $q->whereDate('last_action_date', '<=', $v));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['last_action_from']) $indicators[] = __('Last action from') . ': ' . $data['last_action_from'];
                        if ($data['last_action_until']) $indicators[] = __('Last action until') . ': ' . $data['last_action_until'];
                        return $indicators;
                    })
                    ->columnSpan(3),

                Filter::make('next_session_date')
                    ->label(__('Next Session Date'))
                    ->schema([
                        Fieldset::make(__('Next Session Date'))->schema([
                            DatePicker::make('next_session_from')->label(__('From')),
                            DatePicker::make('next_session_until')->label(__('Until')),
                        ])->columns(2),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['next_session_from'], fn($q, $v) => $q->whereDate('next_session_date', '>=', $v))
                            ->when($data['next_session_until'], fn($q, $v) => $q->whereDate('next_session_date', '<=', $v));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['next_session_from']) $indicators[] = __('Next session from') . ': ' . $data['next_session_from'];
                        if ($data['next_session_until']) $indicators[] = __('Next session until') . ': ' . $data['next_session_until'];
                        return $indicators;
                    })
                    ->columnSpan(3),

                Filter::make('reported_date')
                    ->label(__('Reported Date'))
                    ->schema([
                        Fieldset::make(__('Reported Date'))->schema([
                            DatePicker::make('reported_from')->label(__('From')),
                            DatePicker::make('reported_until')->label(__('Until')),
                        ])->columns(2),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['reported_from'], fn($q, $v) => $q->whereDate('reported_date', '>=', $v))
                            ->when($data['reported_until'], fn($q, $v) => $q->whereDate('reported_date', '<=', $v));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['reported_from']) $indicators[] = __('Reported from') . ': ' . $data['reported_from'];
                        if ($data['reported_until']) $indicators[] = __('Reported until') . ': ' . $data['reported_until'];
                        return $indicators;
                    })
                    ->columnSpan(3),

                Filter::make('submitted_date')
                    ->label(__('Submitted Date'))
                    ->schema([
                        Fieldset::make(__('Submitted Date'))->schema([
                            DatePicker::make('submitted_from')->label(__('From')),
                            DatePicker::make('submitted_until')->label(__('Until')),
                        ])->columns(2),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['submitted_from'], fn($q, $v) => $q->whereDate('submitted_date', '>=', $v))
                            ->when($data['submitted_until'], fn($q, $v) => $q->whereDate('submitted_date', '<=', $v));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['submitted_from']) $indicators[] = __('Submitted from') . ': ' . $data['submitted_from'];
                        if ($data['submitted_until']) $indicators[] = __('Submitted until') . ': ' . $data['submitted_until'];
                        return $indicators;
                    })
                    ->columnSpan(3),

                Filter::make('fees_amount')
                    ->label(__('Fees Amount'))
                    ->schema([
                        Fieldset::make(__('Fees Amount'))->schema([
                            TextInput::make('fees_from')
                                ->label(__('Min Amount'))
                                ->numeric()
                                ->prefix('$')
                                ->placeholder('0.00'),
                            TextInput::make('fees_until')
                                ->label(__('Max Amount'))
                                ->numeric()
                                ->prefix('$')
                                ->placeholder('∞'),
                        ])->columns(2),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['fees_from'], fn($q, $v) => $q->whereHas('fees', fn($f) => $f->havingRaw('SUM(amount) >= ?', [(float)$v])
                                ->groupBy('matter_id')
                                ->select('matter_id')
                            ))
                            ->when($data['fees_until'], fn($q, $v) => $q->whereHas('fees', fn($f) => $f->havingRaw('SUM(amount) <= ?', [(float)$v])
                                ->groupBy('matter_id')
                                ->select('matter_id')
                            ));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['fees_from']) $indicators[] = __('Fees min') . ': $' . number_format((float)$data['fees_from'], 2);
                        if ($data['fees_until']) $indicators[] = __('Fees max') . ': $' . number_format((float)$data['fees_until'], 2);
                        return $indicators;
                    })
                    ->columnSpan(3),

            ])
            ->filtersFormColumns(6)
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormWidth(Width::FourExtraLarge)
            ->recordActions([
                ViewAction::make()->iconButton()->visible(fn($record) => auth()->user()->can('view', $record)),
                EditAction::make()->iconButton()->visible(fn($record) => auth()->user()->can('update', $record)),
                DeleteAction::make()->iconButton()->visible(fn($record) => auth()->user()->can('delete', $record)),
                RestoreAction::make()->iconButton()
                    ->visible(fn($record) => $record->trashed() && auth()->user()->can('restore', $record)),
                ForceDeleteAction::make()->iconButton()
                    ->visible(fn($record) => $record->trashed() && auth()->user()->can('forceDelete', $record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn() => auth()->user()->can('deleteAny', Matter::class)),
                    RestoreBulkAction::make()->visible(fn() => auth()->user()->can('restoreAny', Matter::class)),
                    ForceDeleteBulkAction::make()->visible(fn() => auth()->user()->can('forceDeleteAny', Matter::class)),
                ]),
            ]);
    }
}
