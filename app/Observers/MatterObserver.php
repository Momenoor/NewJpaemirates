<?php

namespace App\Observers;

use App\Enums\MatterCollectionStatus;
use App\Models\Matter;
use App\Services\NewMatterNotification;

class MatterObserver
{


    public function creating(Matter $matter): void
    {
        $matter->collection_status ??= MatterCollectionStatus::NO_FEES;
    }
    public function created(Matter $matter): void
    {
        // Dispatch the notification check after the response to ensure relations are saved (e.g. assistants)
        dispatch(function () use ($matter) {
            app(NewMatterNotification::class)->sendToAssistants($matter);
        })->afterResponse();
    }

    public function saved(Matter $matter): void
    {
        $matter->updateCollectionStatus();

    }

    public function deleted(Matter $matter): void
    {
        $matter->children()->delete();
    }

    public function restored(Matter $matter): void
    {
        $matter->children()->onlyTrashed()->restore();
    }

    public function forceDeleting(Matter $matter): void
    {
        $matter->children()->withTrashed()->each(
            fn(Matter $child) => $child->forceDelete()
        );
        $matter->matterParties()->delete();
        $matter->fees()->each(function ($fee) {
            $fee->allocations()->delete();
            $fee->delete();
        });
        $matter->allocations()->delete();
        $matter->notes()->delete();
        $matter->attachments()->delete();
        $matter->requests()->delete();
    }

}
