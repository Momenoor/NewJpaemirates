<?php

namespace App\Filament\Resources\MatterRequests\Pages;

use App\Filament\Resources\MatterRequests\MatterRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRequest extends EditRecord
{
    protected static string $resource = MatterRequestResource::class;

}
