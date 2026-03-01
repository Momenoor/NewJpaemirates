<?php

namespace App\Filament\Resources\Matters\Pages;

use App\Filament\Resources\Matters\MatterResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMatter extends ViewRecord
{
    protected static string $resource = MatterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
