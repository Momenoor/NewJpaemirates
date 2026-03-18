<?php

namespace App\Filament\Resources\Incentive\MatterTypeIncentiveConfigs\Pages;

use App\Filament\Resources\Incentive\MatterTypeIncentiveConfigs\MatterTypeIncentiveConfigResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMatterTypeIncentiveConfigs extends ListRecords
{
    protected static string $resource = MatterTypeIncentiveConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
