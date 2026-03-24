<?php

namespace App\Observers;

use App\Models\CalendarEvent;
use App\Services\OutlookCalendarService;
use Illuminate\Http\Client\ConnectionException;

class CalendarEventObserver
{
    public function created(CalendarEvent $event): void
    {
        if ($event->imported_from_outlook) {
            return; // Imported from Outlook — do not re-sync
        }
    }

    public function updated(CalendarEvent $event): void
    {
        if ($event->imported_from_outlook) {
            return; // Imported from Outlook — do not re-sync
        }
    }

    /**
     * @throws ConnectionException
     */
    public function deleted(CalendarEvent $event): void
    {
        $outlookService = app(OutlookCalendarService::class);
        $outlookService->deleteEvent($event->outlook_event_id);
    }
}
