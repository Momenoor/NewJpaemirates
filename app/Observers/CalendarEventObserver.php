<?php

namespace App\Observers;

use App\Models\CalendarEvent;

class CalendarEventObserver
{
    public function created(CalendarEvent $event): void
    {
        if ($event->imported_from_outlook) {
            return; // Imported from Outlook — do not re-sync
        }
        // Any other post-create logic here (notifications, logs, etc.)
    }

    public function updated(CalendarEvent $event): void
    {
        if ($event->imported_from_outlook) {
            return; // Imported from Outlook — do not re-sync
        }
        // Any other post-update logic here
    }
}
