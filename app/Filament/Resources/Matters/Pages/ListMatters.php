<?php

namespace App\Filament\Resources\Matters\Pages;

use App\Filament\Resources\Matters\MatterResource;
use App\Enums\MatterStatus;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

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
            'all' => Tab::make(),
            'current' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', MatterStatus::CURRENT)),
            'reported' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', MatterStatus::REPORTED)),
            'submitted' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', MatterStatus::SUBMITTED)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
