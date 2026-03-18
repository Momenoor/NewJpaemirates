<?php

namespace App\Filament\Resources\Incentive\IncentiveExtraRules\Pages;

use App\Filament\Resources\Incentive\IncentiveExtraRules\IncentiveExtraRulesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIncentiveExtraRules extends ListRecords
{
    protected static string $resource = IncentiveExtraRulesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
