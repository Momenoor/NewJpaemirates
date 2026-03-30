<?php

namespace App\Filament\Actions\Request;

use App\Enums\MatterDifficulty;
use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Filament\Resources\Matters\MatterResource;
use App\Services\Requests\BaseRequestService;
use App\Services\Requests\RequestServiceFactory;
use Filament\Actions\Action;
use Filament\Actions\View\ActionsIconAlias;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ApproveRequestAction extends Action
{

    public static function getDefaultName(): ?string
    {
        return 'approve_request';
    }

    public function setUp(): void
    {
        parent::setUp();
        $this
            ->label(__('Approve'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn($record): bool => // 1. Check Permissions Firt
                auth()->user()->hasAnyRole('super-admin', 'super_admin')
                ||
                ($record->type == RequestType::CHANGE_DISTRIBUTED_DATE && $record->status === RequestStatus::PENDING && auth()->id() === $record->request_by)
                ||
                (
                    (auth()->user()->can('EditRequest:MatterRequest') || auth()->user()->can('ApproveRequest:Matter'))

                    &&

                    // 2. Check Business Logic / Status
                    (
                        $record->status === RequestStatus::DISPUTED ||
                        ($record->status === RequestStatus::PENDING && $record->type !== RequestType::CHANGE_DISTRIBUTED_DATE) ||
                        ($record->status === RequestStatus::PENDING && auth()->id() === $record->request_by)
                    )
                )
            )
            ->modalHeading(__('Approve Request'))
            ->successNotificationTitle(__('Request approved successfully.'))
            ->action(
                fn($record, array $data, $component) => RequestServiceFactory::make($record)->approve(data: $data, component: $component));
    }

    public function getSchema(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('approved_comment')
                ->label(__('Reviewer Comment'))
                ->rows(2),
            Toggle::make('has_substantive_changes')
                ->label(__('Has Substantive Changes'))
                ->visible(fn($record) => $record->type === RequestType::REVIEW_REPORT)
                ->default(false),
        ]);
    }

}
