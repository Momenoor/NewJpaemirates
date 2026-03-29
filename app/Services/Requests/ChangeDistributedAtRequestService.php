<?php

namespace App\Services\Requests;

use App\Services\Requests\BaseRequestService;

class ChangeDistributedAtRequestService extends BaseRequestService
{

    public function approve(array $data = [], $component = null): void
    {
        $this->markApproved($data);

        // Apply the proposed received date to the matter
        $proposedDate = $this->request->extra['proposed_distributed_at'] ?? null;
        if ($proposedDate) {
            $this->request->matter->update([
                'distributed_at' => $proposedDate,
            ]);
        }

        $this->onApproveNotify();
        $this->refresh($component);
    }

    public function reject(array $data = [], $component = null): void
    {
        $this->markRejected($data);

        // Notify the assistant of rejection with reason
        $this->onRejectNotify();

        $this->refresh($component);
    }
}
