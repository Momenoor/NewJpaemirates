<?php

namespace App\Services\Requests;

use App\Enums\RequestType;
use Illuminate\Database\Eloquent\Model;

class RequestServiceFactory
{
    public static function make(Model $request): BaseRequestService
    {
        return match ($request->type) {
            RequestType::CHANGE_DIFFICULTY => new ChangeDifficultyRequestService($request),
            RequestType::REVIEW_INCENTIVE => new ReviewIncentiveRequestService($request),
            RequestType::REVIEW_REPORT => new ReviewReportRequestService($request),
            RequestType::CHANGE_DISTRIBUTED_DATE => new ChangeDistributedAtRequestService($request),
            RequestType::CONFIRM_REPORT => new ConfirmReportRequestService($request),
            default => throw new \InvalidArgumentException("Unsupported request type: {$request->type->value}"),
        };
    }
}
