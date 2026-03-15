<?php

namespace App\Filament\Resources\Matters\Pages;

use App\Filament\Resources\Matters\MatterResource;
use App\Enums\MatterStatus;
use App\Models\Matter;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ListMatters extends ListRecords
{
    protected static string $resource = MatterResource::class;

    public function getDefaultActiveTab(): string|int|null
    {
        return 'in_progress';
    }


    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->label(__('All')),

            'in_progress' => Tab::make('in progress')
                ->label(__('In Progress'))
                ->badge(Matter::whereNull('initial_report_at')->count())
                ->badgeColor(Color::Blue)
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNull('initial_report_at')),

            'initial_prepared' => Tab::make('Initial Prepared')
                ->label(__('Initial Prepared'))
                ->badge(Matter::whereNotNull('initial_report_at')->whereNull('final_report_at')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNotNull('initial_report_at')->whereNull('final_report_at')),

            'final_submitted' => Tab::make('Final Submitted')
                ->label(__('Final Submitted'))
                ->badge(Matter::whereNotNull('initial_report_at')->whereNotNull('final_report_at')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNotNull('initial_report_at')->whereNotNull('final_report_at')),

            'deleted' => Tab::make()
                ->label(__('Deleted'))
                ->badgeColor('danger')
                ->icon('heroicon-o-trash')
                ->modifyQueryUsing(fn(Builder $query) => $query
                    ->withoutGlobalScopes([SoftDeletingScope::class])
                    ->whereNotNull('matters.deleted_at')
                )
                ->visible(fn() => auth()->user()->can('ViewTrashed:Matter'))
                ->badge(fn() => Matter::onlyTrashed()->count()),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
