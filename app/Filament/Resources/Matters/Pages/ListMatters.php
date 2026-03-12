<?php

namespace App\Filament\Resources\Matters\Pages;

use App\Filament\Resources\Matters\MatterResource;
use App\Enums\MatterStatus;
use App\Models\Matter;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ListMatters extends ListRecords
{
    protected static string $resource = MatterResource::class;

    public function getDefaultActiveTab(): string | int | null
    {
        return 'current';
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()->label(__('All')),
            'current' => Tab::make()
                ->label(__('Current'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', MatterStatus::CURRENT)),
            'reported' => Tab::make()
                ->label(__('Reported'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', MatterStatus::REPORTED)),
            'submitted' => Tab::make()
                ->label(__('Submitted'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', MatterStatus::SUBMITTED)),
            'deleted' => Tab::make()
                ->label(__('Deleted'))
                ->icon('heroicon-o-trash')
                ->modifyQueryUsing(fn(Builder $query) => $query
                    ->withoutGlobalScopes([SoftDeletingScope::class])
                    ->whereNotNull('matters.deleted_at')
                )
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
