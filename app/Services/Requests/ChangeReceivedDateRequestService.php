<?php

namespace App\Services\Requests;

use App\Services\Requests\BaseRequestService;

class ChangeReceivedDateRequestService extends BaseRequestService
{

    public function approve(array $data = [], $component = null): void
    {
        $this->markApproved($data);

        // Apply the proposed received date to the matter
        $proposedDate = $this->request->extra['proposed_received_at'] ?? null;

        if ($proposedDate) {
            $this->request->matter->update([
                'received_at' => $proposedDate,
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
