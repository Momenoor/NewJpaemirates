<?php

namespace App\Filament\Resources\Matters\Schemas;

use App\Models\Allocation;
use App\Models\Note;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

class MatterInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([

                // ── LEFT COLUMN: Matter Data ──────────────────────────────────
                Grid::make(1)
                    ->columnSpan(1)
                    ->schema([

                        Section::make('Identity')
                            ->icon('heroicon-o-hashtag')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('year')->label('Year'),
                                TextEntry::make('number')->label('Number'),
                                TextEntry::make('status')->label('Status')->badge()->columnSpan(2),
                                TextEntry::make('collection_status')->label('Collection')->badge(),
                                TextEntry::make('commissioning')->label('Commissioning')->columnSpan(2),
                                IconEntry::make('assign')->label('Assigned')->boolean(),
                                TextEntry::make('parent_id')->label('Parent Matter')->numeric()->placeholder('—'),
                            ]),

                        Section::make('Classification')
                            ->icon('heroicon-o-tag')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('court.name')->label('Court')->icon('heroicon-o-building-library'),
                                TextEntry::make('type.name')->label('Type')->icon('heroicon-o-rectangle-stack'),
                                TextEntry::make('level')->label('Level')->badge(),
                                TextEntry::make('difficulty')->label('Difficulty')->badge(),
                                TextEntry::make('external_marketing_rate')->label('External Rate')->numeric()->placeholder('—')->columnSpan(2),
                            ]),

                        Section::make('Key Dates')
                            ->icon('heroicon-o-calendar-days')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('received_date')->label('Received')->date()->icon('heroicon-o-arrow-down-tray')->placeholder('—'),
                                TextEntry::make('next_session_date')->label('Next Session')->date()->icon('heroicon-o-calendar')->placeholder('—'),
                                TextEntry::make('reported_date')->label('Reported')->date()->icon('heroicon-o-document-check')->placeholder('—'),
                                TextEntry::make('submitted_date')->label('Submitted')->date()->icon('heroicon-o-paper-airplane')->placeholder('—'),
                                TextEntry::make('last_action_date')->label('Last Action')->date()->icon('heroicon-o-clock')->placeholder('—'),
                                TextEntry::make('created_at')->label('Created')->dateTime()->placeholder('—'),
                                TextEntry::make('updated_at')->label('Updated')->dateTime()->placeholder('—'),
                            ]),
                    ]),

                // ── RIGHT COLUMN: Parties, Experts, Fees ─────────────────────
                Grid::make(1)
                    ->columnSpan(2)
                    ->schema([

                        // Parties
                        Section::make('Parties')
                            ->icon('heroicon-o-scale')
                            ->description('Plaintiffs, defendants, and litigants with their representatives')
                            ->schema([
                                RepeatableEntry::make('indexedParties')
                                    ->hiddenLabel()
                                    ->schema([
                                        TextEntry::make('type')
                                            ->label('Role')
                                            ->formatStateUsing(fn($state) => ucfirst(str_replace('-', ' ', $state ?? '')))
                                            ->badge()
                                            ->color(fn($state) => match ($state) {
                                                'plaintiff' => 'success',
                                                'defendant' => 'danger',
                                                'implicate-litigant' => 'warning',
                                                default => 'gray',
                                            }),
                                        TextEntry::make('role_index')->label('#')->badge()->color('gray'),
                                        TextEntry::make('party.name')
                                            ->label('Name')
                                            ->icon('heroicon-o-user-circle')
                                            ->weight(FontWeight::SemiBold)
                                            ->columnSpan(2),
                                        RepeatableEntry::make('representatives')
                                            ->label('Representatives')
                                            ->schema([
                                                TextEntry::make('rep_index')->label('#')->badge()->color('gray'),
                                                TextEntry::make('party.name')->label('Name')->icon('heroicon-o-user')->columnSpan(3),
                                            ])
                                            ->columns(4)
                                            ->visible(fn($record) => $record?->representatives?->isNotEmpty())
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull(),
                            ]),

                        // Experts
                        Section::make('Experts')
                            ->icon('heroicon-o-academic-cap')
                            ->description('Certified experts, assistants, and external appointments')
                            ->schema([
                                RepeatableEntry::make('indexedExperts')
                                    ->hiddenLabel()
                                    ->schema([
                                        TextEntry::make('type')
                                            ->label('Role')
                                            ->formatStateUsing(fn($state) => ucfirst(str_replace('-', ' ', $state ?? '')))
                                            ->badge()
                                            ->color(fn($state) => match ($state) {
                                                'certified' => 'success',
                                                'assistant' => 'info',
                                                'external' => 'warning',
                                                'external-assistant' => 'gray',
                                                default => 'gray',
                                            }),
                                        TextEntry::make('role_index')->label('#')->badge()->color('gray'),
                                        TextEntry::make('party.name')
                                            ->label('Name')
                                            ->icon('heroicon-o-user-circle')
                                            ->weight(FontWeight::SemiBold)
                                            ->columnSpan(2),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull(),
                            ]),

                        // Fees + Collection Actions
                        Section::make('Fees & Collections')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                RepeatableEntry::make('fees')
                                    ->hiddenLabel()
                                    ->schema([

                                        // Fee amount
                                        TextEntry::make('amount')
                                            ->label('Fee Amount')
                                            ->numeric()
                                            ->money()
                                            ->weight(FontWeight::SemiBold)
                                            ->icon('heroicon-o-banknotes'),

                                        // How much has been collected so far for this fee
                                        TextEntry::make('collected_amount')
                                            ->label('Collected')
                                            ->numeric()
                                            ->money()
                                            ->icon('heroicon-o-check-circle')
                                            ->color(fn($state, $record) => (float)$state >= (float)($record?->amount ?? 0)
                                                ? 'success'
                                                : ((float)$state > 0 ? 'warning' : 'danger')
                                            )
                                            ->getStateUsing(fn($record) => $record?->allocations?->sum('amount') ?? 0),

                                        // Description
                                        TextEntry::make('description')
                                            ->label('Description')
                                            ->placeholder('—')
                                            ->columnSpan(2),

                                        // Date
                                        TextEntry::make('created_at')
                                            ->label('Date')
                                            ->date()
                                            ->icon('heroicon-o-calendar'),

                                        // ── Collect Payment Action ─────────────────
                                        // Opens a modal form with amount, date, description.
                                        // On submit creates an Allocation row linked to this fee
                                        // and triggers Matter::updateCollectionStatus().
                                        Actions::make([
                                            Action::make('collect')
                                                ->label(function ($record) {
                                                    $collected = (float)($record?->allocations?->sum('amount') ?? 0);
                                                    $amount = (float)($record?->amount ?? 0);
                                                    if ($collected > $amount) return 'Overpaid';
                                                    if ($collected === $amount) return 'Fully Paid';
                                                    return 'Collect Payment';
                                                })
                                                ->icon(fn($record) => (float)($record?->allocations?->sum('amount') ?? 0) >= (float)($record?->amount ?? 0)
                                                    ? 'heroicon-o-check-badge'
                                                    : 'heroicon-o-plus-circle'
                                                )
                                                ->color(fn($record) => (float)($record?->allocations?->sum('amount') ?? 0) > (float)($record?->amount ?? 0)
                                                    ? 'warning'
                                                    : 'success'
                                                )
                                                // Disable when fully paid or overpaid
                                                ->disabled(fn($record) => (float)($record?->allocations?->sum('amount') ?? 0)
                                                    >= (float)($record?->amount ?? 0)
                                                )
                                                ->tooltip(function ($record) {
                                                    $collected = (float)($record?->allocations?->sum('amount') ?? 0);
                                                    $amount = (float)($record?->amount ?? 0);
                                                    if ($collected > $amount) return 'Overpaid by ' . number_format($collected - $amount, 2);
                                                    if ($collected === $amount) return 'This fee is fully paid';
                                                    return 'Remaining balance: ' . number_format($amount - $collected, 2);
                                                })
                                                ->modalHeading(fn($record) => 'Collect Payment — Fee: ' . number_format($record?->amount, 2))
                                                ->modalDescription(function ($record) {
                                                    $collected = (float)($record?->allocations?->sum('amount') ?? 0);
                                                    $balance = (float)($record?->amount ?? 0) - $collected;
                                                    return 'Collected so far: ' . number_format($collected, 2)
                                                        . ' · Remaining balance: ' . number_format($balance, 2);
                                                })
                                                ->modalWidth('md')
                                                ->form(function ($record) {
                                                    $collected = (float)($record?->allocations?->sum('amount') ?? 0);
                                                    $balance = (float)($record?->amount ?? 0) - $collected;

                                                    return [
                                                        TextInput::make('amount')
                                                            ->label('Amount to Collect')
                                                            ->numeric()
                                                            ->minValue(0.01)
                                                            ->maxValue($balance)
                                                            ->default($balance)
                                                            ->required()
                                                            ->prefix('AED')
                                                            ->helperText('Max allowed: ' . number_format($balance, 2)),

                                                        DatePicker::make('date')
                                                            ->label('Payment Date')
                                                            ->default(now())
                                                            ->required(),

                                                        Textarea::make('description')
                                                            ->label('Notes / Reference')
                                                            ->rows(2)
                                                            ->placeholder('Cheque number, bank transfer ref, etc.'),
                                                    ];
                                                })
                                                ->action(function (array $data, $record, $component) {
                                                    Allocation::create([
                                                        'matter_id' => $record->matter_id,
                                                        'fee_id' => $record->id,
                                                        'amount' => $data['amount'],
                                                        'date' => $data['date'],
                                                        'description' => $data['description'] ?? null,
                                                    ]);

                                                    $record->matter->updateCollectionStatus();

                                                    // Force refresh the record and its allocations relation to ensure the infolist shows latest data
                                                    $record->refresh();
                                                    $record->unsetRelation('allocations');

                                                    // Force reload the Matter to ensure the collection status badges are also updated
                                                    $record->matter->refresh();

                                                    // Get the Livewire component via the infolist
                                                    // component reference — works in Filament v3 & v4
                                                    $component->getLivewire()->dispatch('$refresh');
                                                })
                                                ->successNotificationTitle('Payment recorded successfully'),
                                        ]),

                                        // Allocations history nested under each fee
                                        RepeatableEntry::make('allocations')
                                            ->label('Payment History')
                                            ->schema([
                                                TextEntry::make('amount')
                                                    ->label('Amount')
                                                    ->numeric()
                                                    ->money()
                                                    ->weight(FontWeight::SemiBold)
                                                    ->color('success'),
                                                TextEntry::make('date')
                                                    ->label('Date')
                                                    ->date(),
                                                TextEntry::make('description')
                                                    ->label('Notes')
                                                    ->placeholder('—')
                                                    ->columnSpan(2),
                                            ])
                                            ->columns(4)
                                            ->visible(fn($record) => $record?->allocations?->isNotEmpty())
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(6)
                                    ->columnSpanFull(),
                            ]),

                        // Notes
                        Section::make('Notes')
                            ->icon('heroicon-o-chat-bubble-bottom-center-text')
                            ->headerActions([
                                Action::make('addNote')
                                    ->label('Add Note')
                                    ->icon('heroicon-o-plus')
                                    ->modalHeading('Add New Note')
                                    ->form([
                                        Textarea::make('text')
                                            ->label('Note Content')
                                            ->required()
                                            ->rows(3),
                                    ])
                                    ->action(function (array $data, $record, $component) {
                                        $record->notes()->create([
                                            'text' => $data['text'],
                                            'user_id' => auth()->id(),
                                            'datetime' => now(),
                                        ]);

                                        $record->refresh();
                                        $record->unsetRelation('notes');

                                        $component->getLivewire()->dispatch('$refresh');
                                    })
                                    ->successNotificationTitle('Note added successfully'),
                            ])
                            ->schema([
                                RepeatableEntry::make('notes')
                                    ->hiddenLabel()
                                    ->schema([
                                        TextEntry::make('text')
                                            ->label('Note')
                                            ->columnSpanFull(),
                                        TextEntry::make('user.name')
                                            //->label('By')
                                            ->label('-------------')
                                            ->icon('heroicon-o-user')
                                            ->size(TextSize::ExtraSmall),
                                        TextEntry::make('datetime')
                                            //->label('At')
                                            ->label('----------------------')
                                            ->dateTime()
                                            ->icon('heroicon-o-clock')
                                            ->size(TextSize::ExtraSmall),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->visible(fn($record) => $record?->notes?->isNotEmpty()),
                            ]),
                    ]),
            ]);
    }
}
