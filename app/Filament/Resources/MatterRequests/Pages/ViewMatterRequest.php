<?php

namespace App\Filament\Resources\MatterRequests\Pages;

use App\Filament\Actions\Request\ApproveRequestAction;
use App\Filament\Actions\Request\RejectRequestAction;
use App\Filament\Resources\MatterRequests\MatterRequestResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMatterRequest extends ViewRecord
{
    protected static string $resource = MatterRequestResource::class;

    public function getHeaderActions(): array
    {
        return [
            ApproveRequestAction::make(),
            RejectRequestAction::make(),
        ];
    }

}
