<?php

namespace App\Filament\Resources\Matters\Schemas;

use App\Enums\MatterCommissiong;
use App\Filament\Resources\Matters\MatterResource;
use App\Enums\MatterDifficulty;
use App\Models\Allocation;
use App\Models\Note;
use App\Models\Request;
use App\Models\Attachment;
use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
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
use Illuminate\Support\Facades\Storage;
use Filament\Support\Enums\IconPosition;
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

                        Section::make(__('Identity'))
                            ->icon('heroicon-o-hashtag')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('year')->label(__('Year')),
                                TextEntry::make('number')->label(__('Number')),
                                TextEntry::make('status')->label(__('Status'))
                                    ->formatStateUsing(fn($state) => __($state))
                                    ->badge()->columnSpan(2),
                                TextEntry::make('collection_status')->label(__('Collection'))->badge(),
                                TextEntry::make('commissioning')->label(__('Commissioning'))
                                    ->formatStateUsing(fn($state) => __($state->getLabel())),
                                IconEntry::make('is_committee')
                                    ->label(__('Is Committee?'))
                                    ->getStateUsing(fn($record) => $record->commissioning === MatterCommissiong::COMMITTEE)
                                    ->boolean(),
                                IconEntry::make('assign')->label(__('Assigned'))->boolean(),
                                TextEntry::make('parent_id')->label(__('Parent Matter'))->numeric()->placeholder('—')->formatStateUsing(fn($state, $record) => $state ? __('Matter') . " #{$record->number}/{$record->year} [#{$state}]" : null)
                                    ->url(fn($state) => $state ? MatterResource::getUrl('view', ['record' => $state]) : null)
                                    ->icon('heroicon-o-link')
                                    ->color('primary')
                                    ->iconPosition(IconPosition::After)
                                    ->openUrlInNewTab(),
                            ]),

                        Section::make(__('Classification'))
                            ->icon('heroicon-o-tag')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('court.name')->label(__('Court'))->icon('heroicon-o-building-library'),
                                TextEntry::make('type.name')->label(__('Type'))->icon('heroicon-o-rectangle-stack'),
                                TextEntry::make('level')->label(__('Level'))->badge(),
                                TextEntry::make('difficulty')->label(__('Difficulty'))->badge(),
                                TextEntry::make('external_marketing_rate')->label(__('External Rate'))->numeric()->placeholder('—')->columnSpan(2),
                            ]),

                        Section::make(__('Key Dates'))
                            ->icon('heroicon-o-calendar-days')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('received_at')->label(__('Received'))->date()->icon('heroicon-o-arrow-down-tray')->placeholder('—'),
                                TextEntry::make('next_session_date')->label(__('Next Session'))->date()->icon('heroicon-o-calendar')->placeholder('—'),
                                TextEntry::make('initial_report_at')->label(__('Initial Report'))->date()->icon('heroicon-o-document-check')->placeholder('—'),
                                TextEntry::make('final_report_at')->label(__('Final Report'))->date()->icon('heroicon-o-paper-airplane')->placeholder('—'),
                                TextEntry::make('created_at')->label(__('Created'))->dateTime()->placeholder('—'),
                                TextEntry::make('updated_at')->label(__('Updated'))->dateTime()->placeholder('—'),
                            ]),
                        Section::make(__('Requests'))
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->headerActions([
                                Action::make('addRequest')
                                    ->label(__('Add Request'))
                                    ->icon('heroicon-o-plus')
                                    ->visible(fn($record) => auth()->user()->can('createRequest', $record))
                                    ->modalHeading(__('Submit New Request'))
                                    ->form([
                                        Select::make('type')
                                            ->label(__('Request Type'))
                                            ->options([
                                                'change_difficulty' => __('Change Difficulty'),
                                                'review_commission' => __('Review Commission'),
                                            ])
                                            ->required()
                                            ->live(),
                                        Select::make('new_difficulty')
                                            ->label(__('New Difficulty'))
                                            ->options(MatterDifficulty::class)
                                            ->visible(fn(Get $get) => $get('type') === 'change_difficulty')
                                            ->required(fn(Get $get) => $get('type') === 'change_difficulty'),
                                        Textarea::make('comment')
                                            ->label(__('Comment'))
                                            ->required()
                                            ->rows(3),
                                        Repeater::make('attachments')
                                            ->label(__('Attachments'))
                                            ->schema([
                                                FileUpload::make('path')
                                                    ->label(__('File'))
                                                    ->disk('public')
                                                    ->directory('requests-attachments')
                                                    ->required()
                                                    ->preserveFilenames(),
                                            ])
                                            ->collapsible()
                                            ->compact()
                                            ->defaultItems(0),
                                    ])
                                    ->action(function (array $data, $record, $component) {
                                        $comment = $data['comment'];
                                        $requestData = [
                                            'request_by' => auth()->id(),
                                            'type' => $data['type'],
                                            'status' => 'pending',
                                        ];

                                        if ($data['type'] === 'change_difficulty' && !empty($data['new_difficulty'])) {
                                            $difficultyLabel = $data['new_difficulty']->getLabel();
                                            $comment = __("New Difficulty Request") . ": {$difficultyLabel}. " . $comment;
                                            $requestData['extra'] = ['new_difficulty' => $data['new_difficulty']->value];
                                        }

                                        $requestData['comment'] = $comment;

                                        $request = $record->requests()->create($requestData);

                                        if (!empty($data['attachments'])) {
                                            foreach ($data['attachments'] as $item) {
                                                $path = $item['path'];
                                                $request->attachments()->create([
                                                    'name' => basename($path),
                                                    'path' => $path,
                                                    'size' => Storage::disk('public')->size($path),
                                                    'extension' => pathinfo($path, PATHINFO_EXTENSION),
                                                    'type' => 'matter-request',
                                                    'matter_id' => $record->id,
                                                    'user_id' => auth()->id(),
                                                ]);
                                            }
                                        }

                                        $record->refresh();
                                        $record->unsetRelation('requests');
                                        $component->getLivewire()->dispatch('$refresh');
                                    })
                                    ->successNotificationTitle(__('Request submitted successfully.')),
                            ])
                            ->schema([
                                RepeatableEntry::make('requests')
                                    ->hiddenLabel()
                                    ->schema([
                                        TextEntry::make('requestBy.name')
                                            ->label(__('Requester'))
                                            ->weight(FontWeight::SemiBold),
                                        TextEntry::make('type')
                                            ->label(__('Type'))
                                            ->badge()
                                            ->color('info')
                                            ->formatStateUsing(fn($state) => match ($state) {
                                                'change_difficulty' => __('Change Difficulty'),
                                                'review_commission' => __('Review Commission'),
                                                default => $state,
                                            }),
                                        TextEntry::make('status')
                                            ->label(__('Status'))
                                            ->badge()
                                            ->color(fn($state) => match ($state) {
                                                'pending' => 'warning',
                                                'approved' => 'success',
                                                'rejected' => 'danger',
                                                default => 'gray',
                                            })
                                            ->formatStateUsing(fn($state) => match ($state) {
                                                'pending' => __('Pending'),
                                                'approved' => __('Approved'),
                                                'rejected' => __('Rejected'),
                                                default => $state,
                                            }),
                                        TextEntry::make('comment')
                                            ->label(__('Comment'))
                                            ->columnSpanFull(),
                                        TextEntry::make('approvedBy.name')
                                            ->label(__('Reviewed By'))
                                            ->visible(fn($record) => $record->status !== 'pending'),
                                        TextEntry::make('approved_at')
                                            ->label(__('Date'))
                                            ->dateTime()
                                            ->visible(fn($record) => $record->status !== 'pending'),
                                        TextEntry::make('approved_comment')
                                            ->label(__('Reviewer Comment'))
                                            ->columnSpanFull()
                                            ->visible(fn($record) => !empty($record->approved_comment)),
                                        RepeatableEntry::make('attachments')
                                            ->label(__('Attachments'))
                                            ->columnSpanFull()
                                            ->visible(fn($record) => $record->attachments->isNotEmpty())
                                            ->schema([
                                                TextEntry::make('name')
                                                    ->hiddenLabel()
                                                    ->icon('heroicon-o-paper-clip')
                                                    ->url(fn($record) => Storage::disk('public')->url($record->path))
                                                    ->openUrlInNewTab()
                                                    ->color('primary'),
                                            ]),
                                        Actions::make([
                                            Action::make('approve')
                                                ->label(__('Approve'))
                                                ->icon('heroicon-o-check-circle')
                                                ->color('success')
                                                ->requiresConfirmation()
                                                ->visible(fn($record) => $record->status === 'pending' && auth()->user()->can('approveRequest', $record->matter))
                                                ->schema([
                                                    Textarea::make('approved_comment')
                                                        ->label(__('Reviewer Comment'))
                                                        ->rows(2),
                                                ])
                                                ->action(function ($record, array $data, $component) {
                                                    $record->update([
                                                        'status' => 'approved',
                                                        'approved_by' => auth()->id(),
                                                        'approved_at' => now(),
                                                        'approved_comment' => $data['approved_comment'] ?? null,
                                                    ]);

                                                    // If it was a change difficulty request, update the matter
                                                    if ($record->type === 'change_difficulty' && !empty($record->extra['new_difficulty'])) {
                                                        $record->matter->update(['difficulty' => $record->extra['new_difficulty']]);
                                                    }

                                                    $component->getLivewire()->getRecord()->refresh();
                                                    $component->getLivewire()->getRecord()->unsetRelation('requests');
                                                    $component->getLivewire()->dispatch('$refresh');
                                                }),
                                            Action::make('reject')
                                                ->label(__('Reject'))
                                                ->icon('heroicon-o-x-circle')
                                                ->color('danger')
                                                ->requiresConfirmation()
                                                ->visible(fn($record) => $record->status === 'pending' && auth()->user()->can('rejectRequest', $record->matter))
                                                ->form([
                                                    Textarea::make('approved_comment')
                                                        ->label(__('Reviewer Comment'))
                                                        ->required()
                                                        ->rows(2),
                                                ])
                                                ->action(function ($record, array $data, $component) {
                                                    $record->update([
                                                        'status' => 'rejected',
                                                        'approved_by' => auth()->id(),
                                                        'approved_at' => now(),
                                                        'approved_comment' => $data['approved_comment'],
                                                    ]);

                                                    $component->getLivewire()->getRecord()->refresh();
                                                    $component->getLivewire()->getRecord()->unsetRelation('requests');
                                                    $component->getLivewire()->dispatch('$refresh');
                                                }),
                                        ])->alignEnd(),
                                    ])
                                    ->columns(3)
                                    ->columnSpanFull(),
                            ]),
                        Section::make(__('Notes'))
                            ->icon('heroicon-o-chat-bubble-bottom-center-text')
                            ->headerActions([
                                Action::make('addNote')
                                    ->label(__('Add Note'))
                                    ->icon('heroicon-o-plus')
                                    ->visible(fn($record) => auth()->user()->can('createNote', $record))
                                    ->modalHeading(__('Add New Note'))
                                    ->form([
                                        Textarea::make('text')
                                            ->label(__('Content'))
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
                                    ->successNotificationTitle(__('Note added successfully.')),
                            ])
                            ->schema([
                                RepeatableEntry::make('notes')
                                    ->hiddenLabel()
                                    ->schema([
                                        TextEntry::make('text')
                                            ->label(__('Note'))
                                            ->columnSpanFull(),
                                        TextEntry::make('user.name')
                                            //->label('By')
                                            ->label(__('By'))
                                            ->icon('heroicon-o-user')
                                            ->size(TextSize::ExtraSmall),
                                        TextEntry::make('datetime')
                                            //->label('At')
                                            ->label(__('Date'))
                                            ->dateTime()
                                            ->icon('heroicon-o-clock')
                                            ->size(TextSize::ExtraSmall),
                                        Actions::make([
                                            Action::make('editNote')
                                                ->label(__('Edit'))
                                                ->iconButton()
                                                ->icon('heroicon-o-pencil')
                                                ->visible(fn($record) => auth()->user()->can('updateNote', $record->matter))
                                                ->modalHeading(__('Edit Note'))
                                                ->form([
                                                    Textarea::make('text')
                                                        ->label(__('Content'))
                                                        ->required()
                                                        ->rows(3),
                                                ])
                                                ->fillForm(fn($record) => [
                                                    'text' => $record->text,
                                                ])
                                                ->action(function (array $data, $record, $component) {
                                                    $record->update([
                                                        'text' => $data['text'],
                                                    ]);

                                                    $record->refresh();
                                                    $component->getLivewire()->dispatch('$refresh');
                                                }),
                                            Action::make('deleteNote')
                                                ->label(__('Delete'))
                                                ->iconButton()
                                                ->icon('heroicon-o-trash')
                                                ->color('danger')
                                                ->visible(fn($record) => auth()->user()->can('deleteNote', $record->matter))
                                                ->requiresConfirmation()
                                                ->action(function ($record, $component) {
                                                    $record->delete();
                                                    $component->getLivewire()->dispatch('$refresh');
                                                }),
                                        ])->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->visible(fn($record) => $record?->notes?->isNotEmpty()),
                            ]),
                    ]),

                // ── RIGHT COLUMN: Parties, Experts, Fees ─────────────────────
                Grid::make(1)
                    ->columnSpan(2)
                    ->schema([

                        // Experts
                        Section::make(__('Experts'))
                            ->icon('heroicon-o-academic-cap')
                            ->description(__('Certified experts, assistants, and external appointments'))
                            ->schema([
                                RepeatableEntry::make('indexedExperts')
                                    ->hiddenLabel()
                                    ->schema([
                                        TextEntry::make('type')
                                            ->label(__('Role'))
                                            ->formatStateUsing(fn($state) => __($state ? ucfirst(str_replace('-', ' ', $state)) : ''))
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
                                            ->label(__('Name'))
                                            ->icon('heroicon-o-user-circle')
                                            ->weight(FontWeight::SemiBold)
                                            ->columnSpan(2),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull(),
                            ]),

                        // Parties
                        Section::make(__('Parties'))
                            ->icon('heroicon-o-scale')
                            ->description(__('Plaintiffs, defendants, and litigants with their representatives'))
                            ->schema([
                                RepeatableEntry::make('indexedParties')
                                    ->hiddenLabel()
                                    ->schema([
                                        TextEntry::make('type')
                                            ->label(__('Role'))
                                            ->formatStateUsing(fn($state) => __($state ? ucfirst(str_replace('-', ' ', $state)) : ''))
                                            ->badge()
                                            ->color(fn($state) => match ($state) {
                                                'plaintiff' => 'success',
                                                'defendant' => 'danger',
                                                'implicate-litigant' => 'warning',
                                                default => 'gray',
                                            }),
                                        TextEntry::make('role_index')->label('#')->badge()->color('gray'),
                                        TextEntry::make('party.name')
                                            ->label(__('Name'))
                                            ->icon('heroicon-o-user-circle')
                                            ->weight(FontWeight::SemiBold)
                                            ->columnSpan(2),
                                        RepeatableEntry::make('representatives')
                                            ->label(__('Representatives'))
                                            ->schema([
                                                TextEntry::make('rep_index')->label('#')->badge()->color('gray'),
                                                TextEntry::make('party.name')->label(__('Name'))->icon('heroicon-o-user')->columnSpan(3),
                                            ])
                                            ->columns(4)
                                            ->visible(fn($record) => $record?->representatives?->isNotEmpty())
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull(),
                            ]),

                        // Fees + Collection Actions
                        Section::make(__('Fees & Collections'))
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                RepeatableEntry::make('fees')
                                    ->schema([
                                        // Fee amount
                                        TextEntry::make('amount')
                                            ->label(__('Fee Amount'))
                                            ->numeric()
                                            ->money('AED')
                                            ->weight(FontWeight::SemiBold)
                                            ->icon('heroicon-o-banknotes'),

                                        // How much has been collected so far for this fee
                                        TextEntry::make('collected_amount')
                                            ->label(__('Collected'))
                                            ->numeric()
                                            ->money('AED')
                                            ->icon('heroicon-o-check-circle')
                                            ->color(fn($state, $record) => (float)$state >= (float)($record?->amount ?? 0)
                                                ? 'success'
                                                : ((float)$state > 0 ? 'warning' : 'danger')
                                            )
                                            ->getStateUsing(fn($record) => $record?->allocations?->sum('amount') ?? 0),

                                        // Description
                                        TextEntry::make('description')
                                            ->label(__('Description'))
                                            ->placeholder('—')
                                            ->columnSpan(2),

                                        // Date
                                        TextEntry::make('created_at')
                                            ->label(__('Date'))
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
                                                    if ($collected > $amount) return __('Overpaid');
                                                    if ($collected === $amount) return __('Fully Paid');
                                                    return __('Collect Fee');
                                                })
                                                ->visible(fn($record) => auth()->user()->can('collectFee', $record->matter))
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
                                                    if ($collected > $amount) return __('Overpaid by') . ' ' . number_format($collected - $amount, 2);
                                                    if ($collected === $amount) return __('This fee is fully paid');
                                                    return __('Remaining balance') . ': ' . number_format($amount - $collected, 2);
                                                })
                                                ->modalHeading(fn($record) => __('Collect Payment — Fee') . ': ' . number_format($record?->amount, 2))
                                                ->modalDescription(function ($record) {
                                                    $collected = (float)($record?->allocations?->sum('amount') ?? 0);
                                                    $balance = (float)($record?->amount ?? 0) - $collected;
                                                    return __('Collected so far') . ': ' . number_format($collected, 2)
                                                        . ' · ' . __('Remaining balance') . ': ' . number_format($balance, 2);
                                                })
                                                ->modalWidth('md')
                                                ->form(function ($record) {
                                                    $collected = (float)($record?->allocations?->sum('amount') ?? 0);
                                                    $balance = (float)($record?->amount ?? 0) - $collected;

                                                    return [
                                                        TextInput::make('amount')
                                                            ->label(__('Amount to Collect'))
                                                            ->numeric()
                                                            ->prefix('AED')
                                                            ->minValue(0.01)
                                                            ->maxValue($balance)
                                                            ->default($balance)
                                                            ->required()
                                                            ->prefix('AED')
                                                            ->helperText(__('Max allowed') . ': ' . number_format($balance, 2)),

                                                        DatePicker::make('date')
                                                            ->label(__('Payment Date'))
                                                            ->default(now())
                                                            ->required(),

                                                        Textarea::make('description')
                                                            ->label(__('Notes / Reference'))
                                                            ->rows(2)
                                                            ->placeholder(__('Cheque number, bank transfer ref, etc.')),
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
                                                    $component->getLivewire()->getRecord()->refresh();

                                                    // Get the Livewire component via the infolist
                                                    // component reference — works in Filament v3 & v4
                                                    $component->getLivewire()->dispatch('$refresh');
                                                })
                                                ->successNotificationTitle(__('Payment recorded successfully.')),
                                            Action::make('editFee')
                                                ->label(__('Edit'))
                                                ->iconButton()
                                                ->icon('heroicon-o-pencil')
                                                ->visible(fn($record) => auth()->user()->can('updateFee', $record->matter))
                                                ->modalHeading(__('Edit Fee'))
                                                ->schema([
                                                    TextInput::make('amount')
                                                        ->label(__('Fee Amount'))
                                                        ->numeric()
                                                        ->required()
                                                        ->prefix('AED'),
                                                    TextInput::make('description')
                                                        ->label(__('Description'))
                                                        ->required(),
                                                ])
                                                ->fillForm(fn($record) => [
                                                    'amount' => $record->amount,
                                                    'description' => $record->description,
                                                ])
                                                ->action(function (array $data, $record, $component) {
                                                    $record->update([
                                                        'amount' => $data['amount'],
                                                        'description' => $data['description'],
                                                    ]);

                                                    $record->refresh();
                                                    $component->getLivewire()->getRecord()->refresh();
                                                    $component->getLivewire()->dispatch('$refresh');
                                                }),
                                            Action::make('deleteFee')
                                                ->label(__('Delete'))
                                                ->iconButton()
                                                ->icon('heroicon-o-trash')
                                                ->color('danger')
                                                ->visible(fn($record) => auth()->user()->can('deleteFee', $record->matter))
                                                ->requiresConfirmation()
                                                ->action(function ($record, $component) {
                                                    $record->delete();
                                                    $component->getLivewire()->getRecord()->refresh();
                                                    $component->getLivewire()->dispatch('$refresh');
                                                }),
                                        ]),

                                        // Allocations history nested under each fee
                                        RepeatableEntry::make('allocations')
                                            ->label(__('Payment History'))
                                            ->schema([
                                                TextEntry::make('amount')
                                                    ->label(__('Amount'))
                                                    ->numeric()
                                                    ->money('AED')
                                                    ->weight(FontWeight::SemiBold)
                                                    ->color('success'),
                                                TextEntry::make('date')
                                                    ->label(__('Date'))
                                                    ->date(),
                                                TextEntry::make('description')
                                                    ->label(__('Notes'))
                                                    ->placeholder('—')
                                                    ->columnSpan(2),
                                                Actions::make([
                                                    Action::make('editAllocation')
                                                        ->label(__('Edit'))
                                                        ->iconButton()
                                                        ->icon('heroicon-o-pencil')
                                                        ->visible(fn($record) => auth()->user()->can('updateAllocation', $record->matter))
                                                        ->modalHeading(__('Edit Payment'))
                                                        ->form([
                                                            TextInput::make('amount')
                                                                ->label(__('Amount'))
                                                                ->numeric()
                                                                ->required()
                                                                ->prefix('AED'),
                                                            DatePicker::make('date')
                                                                ->label(__('Payment Date'))
                                                                ->required(),
                                                            Textarea::make('description')
                                                                ->label(__('Notes / Reference'))
                                                                ->rows(2),
                                                        ])
                                                        ->fillForm(fn($record) => [
                                                            'amount' => $record->amount,
                                                            'date' => $record->date,
                                                            'description' => $record->description,
                                                        ])
                                                        ->action(function (array $data, $record, $component) {
                                                            $record->update([
                                                                'amount' => $data['amount'],
                                                                'date' => $data['date'],
                                                                'description' => $data['description'],
                                                            ]);

                                                            // The Allocation model updated hook will call fee->updateStatus()
                                                            // but we might need to refresh the view
                                                            $record->refresh();
                                                            $component->getLivewire()->getRecord()->refresh();
                                                            $component->getLivewire()->dispatch('$refresh');
                                                        }),
                                                    Action::make('deleteAllocation')
                                                        ->label(__('Delete'))
                                                        ->iconButton()
                                                        ->icon('heroicon-o-trash')
                                                        ->color('danger')
                                                        ->visible(fn($record) => auth()->user()->can('deleteAllocation', $record->matter))
                                                        ->requiresConfirmation()
                                                        ->action(function ($record, $component) {
                                                            $record->delete();
                                                            $component->getLivewire()->getRecord()->refresh();
                                                            $component->getLivewire()->dispatch('$refresh');
                                                        }),
                                                ])->columnSpanFull(),
                                            ])
                                            ->columns(4)
                                            ->visible(fn($record) => $record?->allocations?->isNotEmpty())
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(6)
                                    ->columnSpanFull(),
                            ]),

                        // Attachments
                        Section::make(__('Attachments'))
                            ->icon('heroicon-o-paper-clip')
                            ->headerActions([
                                Action::make('addAttachments')
                                    ->label(__('Add Attachments'))
                                    ->icon('heroicon-o-plus')
                                    ->visible(fn($record) => auth()->user()->can('createAttachment', $record))
                                    ->modalHeading(__('Add New Attachments'))
                                    ->form([
                                        Repeater::make('attachments_data')
                                            ->label(__('Files'))
                                            ->schema([
                                                FileUpload::make('file')
                                                    ->label(__('Attachment'))
                                                    ->disk('public')
                                                    ->directory('matter-attachments')
                                                    ->visibility('public')
                                                    ->required(),
                                                Select::make('type')
                                                    ->label(__('Type'))
                                                    ->options([
                                                        'initial_report' => __('Initial Report'),
                                                        'final_report' => __('Final Report'),
                                                        'supporting_document' => __('Supporting Document'),
                                                        'correspondence' => __('Correspondence'),
                                                        'other' => __('Other'),
                                                    ])
                                                    ->required(),
                                            ])
                                            ->columns(2)
                                            ->addActionLabel(__('Add Another File'))
                                    ])
                                    ->action(function (array $data, $record, $component) {
                                        $disk = Storage::disk('public');

                                        foreach ($data['attachments_data'] as $item) {
                                            $path = $item['file'];

                                            $record->attachments()->create([
                                                'user_id' => auth()->id(),
                                                'type' => $item['type'],
                                                'path' => $path,
                                                'name' => basename($path),
                                                'size' => $disk->exists($path) ? $disk->size($path) : 0,
                                                'extension' => pathinfo($path, PATHINFO_EXTENSION) ?? '',
                                            ]);
                                        }

                                        $record->refresh();
                                        $record->unsetRelation('attachments');
                                        $component->getLivewire()->dispatch('$refresh');
                                    })
                                    ->successNotificationTitle(__('Attachments added successfully.')),
                            ])
                            ->schema([
                                RepeatableEntry::make('attachments')
                                    ->hiddenLabel()
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label(__('Name'))
                                            ->weight(FontWeight::SemiBold)
                                            ->columnSpanFull()
                                            ->icon('heroicon-o-document-text')
                                            ->url(fn($record) => Storage::disk('public')->url($record->path))
                                            ->openUrlInNewTab(),
                                        TextEntry::make('type')
                                            ->label(__('Type'))
                                            ->badge()
                                            ->color('info')
                                            ->formatStateUsing(fn($state) => __($state ? ucfirst(str_replace('_', ' ', __($state))) : '')),
                                        TextEntry::make('extension')
                                            ->label(__('Extension'))
                                            ->badge()
                                            ->color('gray'),
                                        TextEntry::make('size')
                                            ->label(__('Size'))
                                            ->formatStateUsing(fn($state) => number_format($state / 1024, 2) . ' KB'),
                                        TextEntry::make('created_at')
                                            ->label(__('Date'))
                                            ->dateTime()
                                            ->icon('heroicon-o-calendar'),
                                        Actions::make([
                                            Action::make('deleteAttachment')
                                                ->label(__('Delete'))
                                                ->iconButton()
                                                ->icon('heroicon-o-trash')
                                                ->color('danger')
                                                ->visible(fn($record) => auth()->user()->can('deleteAttachment', $record->matter))
                                                ->requiresConfirmation()
                                                ->action(function ($record, $component) {
                                                    Storage::disk('public')->delete($record->path);
                                                    $record->delete();

                                                    $component->getLivewire()->getRecord()->refresh();
                                                    $component->getLivewire()->getRecord()->unsetRelation('attachments');
                                                    $component->getLivewire()->dispatch('$refresh');
                                                }),
                                        ])->alignEnd(),
                                    ])
                                    ->columns(5)
                                    ->columnSpanFull()
                                    ->visible(fn($record) => $record?->attachments?->isNotEmpty()),
                            ]),


                    ]),
            ]);
    }
}
