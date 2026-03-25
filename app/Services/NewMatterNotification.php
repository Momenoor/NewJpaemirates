<?php

namespace App\Services;

use App\Mail\NewMatterNotificationMail;
use App\Models\Matter;
use Illuminate\Support\Facades\Mail;

class NewMatterNotification
{
    public function sendToAssistants(Matter $matter): void
    {
        if (!$matter->received_at) return;

        $matter->load(['assistantsOnly.party', 'court', 'type']);

        foreach ($matter->assistantsOnly->filter(fn($mp) => $mp->party?->email) as $mp) {
            $party = $mp->party;

            // Create pending request — no token needed
            $matterRequest = \App\Models\Request::create([
                'matter_id'  => $matter->id,
                'request_by' => $party->user_id ?? null,
                'type'       => \App\Enums\RequestType::CHANGE_RECEIVED_DATE->value,
                'status'     => \App\Enums\RequestStatus::PENDING->value,
                'comment'    => __('Auto-generated: awaiting assistant confirmation of received date.'),
                'extra'      => [
                    'party_id'            => $party->id,
                    'party_name'          => $party->name,
                    'current_received_at' => $matter->received_at->format('Y-m-d'),
                ],
            ]);

            Mail::to($party->email)
                ->queue(new NewMatterNotificationMail($matter, $party, $matterRequest));
        }
    }
}
