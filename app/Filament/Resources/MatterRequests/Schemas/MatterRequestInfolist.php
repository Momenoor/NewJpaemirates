<?php

namespace App\Filament\Resources\MatterRequests\Schemas;

use App\Enums\RequestStatus;

use App\Filament\Actions\Request\ApproveRequestAction;
use App\Filament\Actions\Request\RejectRequestAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

class MatterRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        // Left Column: Primary Request Details
                        Group::make()
                            ->schema([
                                Section::make(__('Request Information'))
                                    ->description(__('Core details regarding this submission.'))
                                    ->icon('heroicon-m-information-circle')
                                    ->schema([
                                        TextEntry::make('matter')
                                            ->label(__('Matter Reference'))
                                            ->weight(FontWeight::Bold)
                                            ->color('primary')
                                            ->icon('heroicon-m-hashtag')
                                            ->url(fn($record) => route('filament.admin.resources.matters.view', $record->matter_id))
                                            ->formatStateUsing(fn($state) => "{$state->number}/{$state->year}"),

                                        Grid::make(2)->schema([
                                            TextEntry::make('type')
                                                ->label(__('Request Type'))
                                                ->badge()
                                                ->color('gray')
                                                ->icon('heroicon-m-tag'),
                                            TextEntry::make('status')
                                                ->label(__('Status'))
                                                ->badge(),
                                        ]),

                                        TextEntry::make('comment')
                                            ->label(__('Request Description'))
                                            ->markdown()
                                            ->prose()
                                            ->columnSpanFull()
                                            ->placeholder(__('No comments provided.')),
                                    ])->columns(1),
                            ])->columnSpan(2),

                        // Right Column: Metadata & Audit Trail
                        Group::make()
                            ->schema([
                                Section::make(__('Approval Details'))
                                    ->icon('heroicon-m-check-badge')
                                    ->collapsed(fn($record) => $record->status !== RequestStatus::APPROVED) // Auto-expand if approved
                                    ->schema([
                                        TextEntry::make('approvedBy.name')
                                            ->label(__('Handled By'))
                                            ->icon('heroicon-m-user')
                                            ->placeholder('-'),
                                        TextEntry::make('approved_at')
                                            ->label(__('Handled At'))
                                            ->dateTime('M d, Y H:i')
                                            ->size(TextSize::Small)
                                            ->color('gray'),
                                        TextEntry::make('approved_comment')
                                            ->label(__('Handling Note'))
                                            ->color('gray')
                                            ->placeholder('-'),
                                    ]),

                                Section::make(__('Timestamps'))
                                    ->icon('heroicon-m-clock')
                                    ->compact()
                                    ->schema([
                                        TextEntry::make('created_at')
                                            ->label(__('Created At'))
                                            ->since()
                                            ->dateTimeTooltip(),
                                        TextEntry::make('updated_at')
                                            ->label(__('Last Activity'))
                                            ->since()
                                            ->dateTimeTooltip(),
                                    ]),
                            ])->columnSpan(1),
                    ]),
            ]);
    }

}
