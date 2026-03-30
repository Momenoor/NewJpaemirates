<?php

namespace App\Filament\Actions\Request;

use App\Enums\MatterDifficulty;
use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Filament\Resources\Matters\MatterResource;
use App\Models\MatterRequest;
use App\Services\Requests\BaseRequestService;
use App\Services\Requests\RequestServiceFactory;
use Filament\Actions\Action;
use Filament\Actions\View\ActionsIconAlias;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RejectRequestAction extends Action
{

    public static function getDefaultName(): ?string
    {
        return 'reject_request';
    }

    public function setUp(): void
    {
        parent::setUp();
        $this
            ->label(__('Reject'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn($record) =>
                auth()->user()->hasAnyRole('super-admin', 'super_admin')
                ||
                ($record->type == RequestType::CHANGE_DISTRIBUTED_DATE && $record->status === RequestStatus::PENDING && auth()->id() === $record->request_by)
                ||
                (
                    (auth()->user()->can('EditRequest:MatterRequest') || auth()->user()->can('RejectRequest:Matter'))

                    &&

                    // 2. Check Business Logic / Status
                    (
                        $record->status === RequestStatus::DISPUTED ||
                        ($record->status === RequestStatus::PENDING && $record->type !== RequestType::CHANGE_DISTRIBUTED_DATE) ||
                        ($record->status === RequestStatus::PENDING && auth()->id() === $record->request_by)
                    )
                )
            )
            ->modalHeading(__('Reject Request'))
            ->successNotificationTitle(__('Request rejected successfully.'))
            ->action(fn($record, array $data, $component) => RequestServiceFactory::make($record)->reject(data: $data, component: $component));
    }

    public function getSchema(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('approved_comment')->label(__('Reviewer Comment'))->required()->rows(2),
        ]);
    }

}
