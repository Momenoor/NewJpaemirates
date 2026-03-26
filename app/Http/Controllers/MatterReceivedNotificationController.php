<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\Matter;
use App\Models\MatterRequest as MatterRequest;
use Illuminate\Http\Request;

class MatterReceivedNotificationController
{
    public function accept(Request $request, Matter $matter, MatterRequest $matterRequest)
    {

        // 'signed' middleware already verified the URL — just check not already used
        if ($matterRequest->status !== RequestStatus::PENDING) {

            return $this->alreadyUsed($matterRequest);
        }

        $matterRequest->update([
            'status' => RequestStatus::APPROVED->value,
            'approved_at' => now(),
            'approved_comment' => __('Assistant accepted the received date via email.'),
            'email_action' => 'accept',
        ]);

        return view('mails.received-date-response', [
            'type' => 'accepted',
            'matter' => $matterRequest->matter,
            'message' => __('Thank you. You have confirmed the received date.'),
        ]);
    }

    public function disputeForm(Request $httpRequest, Matter $matter, MatterRequest $matterRequest)
    {
        if ($matterRequest->status !== RequestStatus::PENDING
        ) {
            return $this->alreadyUsed($matterRequest);
        }

        return view('mails.received-date-dispute-form', [
            'matterRequest' => $matterRequest,
            'matter' => $matter,
        ]);
    }

    public function disputeSubmit(Request $httpRequest, Matter $matter, MatterRequest $matterRequest)
    {
        $httpRequest->validate([
            'proposed_received_at' => 'required|date|before_or_equal:today',
            'comment' => 'required|string|min:10|max:500',
        ]);

        $matterRequest->update([
            'comment' => __('New Receive Date Request to') . ':' . $httpRequest->input('proposed_received_at') . ' - ' . $httpRequest->input('comment'),
            'status' => RequestStatus::DISPUTED,
            'email_action' => 'dispute',
            'extra' => array_merge($matterRequest->extra ?? [], [
                'proposed_received_at' => $httpRequest->input('proposed_received_at'),
                'dispute_comment' => $httpRequest->input('comment'),
            ]),
        ]);

        // Notify admins...

        return view('mails.received-date-response', [
            'type' => 'disputed',
            'matter' => $matterRequest->matter,
            'message' => __('Your dispute has been submitted successfully.'),
        ]);
    }

    private function alreadyUsed(MatterRequest $matterRequest)
    {
        return view('mails.received-date-response', [
            'type' => 'error',
            'matter' => $matterRequest->matter,
            'message' => __('This link has already been used. The request status is: ')
                . $matterRequest->status->getLabel(),
        ]);
    }
}
