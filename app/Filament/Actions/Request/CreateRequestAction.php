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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CreateRequestAction extends Action
{

    public static function getDefaultName(): ?string
    {
        return 'add_request';
    }

    public function setUp(): void
    {
        parent::setUp();
        $this
            ->label(__('Add Request'))
            ->icon('heroicon-o-plus')
            ->visible(fn($record) => auth()->user()->can('CreateRequest:Request') || auth()->user()->can('CreateRequest:Matter'))
            ->modalHeading(__('Submit New Request'))
            ->successNotificationTitle(__('Request submitted successfully.'))
            ->action(BaseRequestService::getDefaultCreateAction());
    }

    public function getSchema(Schema $schema): Schema
    {

        return $schema->components([
            Select::make('type')
                ->label(__('Request Type'))
                ->options(RequestType::class)
                ->required()
                ->disableOptionWhen(fn(string $value, $record): bool => $record->requests()->where('type', $value)->whereNot('status', RequestStatus::REJECTED)->exists())
                ->live(),
            Select::make('new_difficulty')
                ->label(__('New Difficulty'))
                ->options(MatterDifficulty::class)
                ->disableOptionWhen(fn(string $value, $record): bool => $value === $record->difficulty->value)
                ->visible(fn(Get $get) => $get('type') === RequestType::CHANGE_DIFFICULTY)
                ->required(fn(Get $get) => $get('type') === RequestType::CHANGE_DIFFICULTY),
            Textarea::make('comment')->label(__('Comment'))->required()->rows(3),
            Repeater::make('attachments')
                ->label(__('Attachments'))
                ->schema([
                    FileUpload::make('path')
                        ->label(__('File'))
                        ->disk('public')
                        ->directory('requests-attachments')
                        ->required()
                        ->preserveFilenames(),
                ])
                ->lazy()
                ->defaultItems(fn(Get $get) => $get('type') === RequestType::REVIEW_REPORT ? 1 : 0)
                ->required(fn(Get $get) => $get('type') === RequestType::REVIEW_REPORT)
                ->collapsible(),
        ]);
    }
}
