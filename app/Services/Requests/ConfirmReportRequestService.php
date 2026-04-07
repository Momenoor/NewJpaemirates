<?php

namespace App\Services\Requests;

use App\Services\Requests\BaseRequestService;

class ConfirmReportRequestService extends BaseRequestService
{

    public function approve(array $data = [], $component = null): void
    {
        $this->markApproved($data);

        // Apply the proposed received date to the matter
        $this->request->matter->update([
            'final_report_memo_date' => now(),
        ]);

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
