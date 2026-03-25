<?php

namespace App\Services\Requests;

use App\Enums\RequestStatus;
use App\Models\MatterRequest;
use App\Services\Requests\BaseRequestService;
use Illuminate\Database\Eloquent\Model;

class ChangeDifficultyRequestService extends BaseRequestService
{

    public function approve(array $data = [], $component = null): void
    {
        $this->markApproved($data);

        $this->request->matter->update([
            'difficulty' => $this->request->extra['new_difficulty']
        ]);

        $this->onApproveNotify();
        $this->onApproveNotify();
        $this->refresh($component);
    }

    public function reject(array $data = [], $component = null): void
    {
        $this->markRejected($data);
        $this->onRejectNotify();
        $this->refresh($component);
    }
}
