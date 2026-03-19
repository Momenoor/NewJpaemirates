<?php

namespace App\Services\Requests;

use App\Enums\RequestStatus;
use App\Models\Request;
use App\Services\Requests\BaseRequestService;
use Illuminate\Database\Eloquent\Model;

class ReviewReportRequestService extends BaseRequestService
{

    public function approve(array $data = [], $component = null): void
    {

        $this->markApproved($data);

        $this->request->matter->update([
            'initial_report_at' => now(),
            'has_substantive_changes' => $data['has_substantive_changes'] ?? false,
        ]);

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
