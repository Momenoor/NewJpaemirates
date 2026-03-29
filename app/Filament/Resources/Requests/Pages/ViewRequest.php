<?php

namespace App\Filament\Resources\Requests\Pages;

use App\Filament\Actions\Request\ApproveRequestAction;
use App\Filament\Actions\Request\RejectRequestAction;
use App\Filament\Resources\Requests\RequestResource;
use Filament\Resources\Pages\ViewRecord;

class ViewRequest extends ViewRecord
{
    protected static string $resource = RequestResource::class;

    public function getHeaderActions(): array
    {
        return [
            ApproveRequestAction::make(),
            RejectRequestAction::make(),
        ];
    }

}
