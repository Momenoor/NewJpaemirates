<?php

namespace App\Filament\Actions\Calendar;

use App\Models\CalendarEvent;
use App\Services\OutlookCalendarService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ImportFromOutlookAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'importFromOutlook';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Import from Outlook'))
            ->modalHeading(__('Import Calendar Events from Outlook'))
            ->icon('heroicon-o-cloud-arrow-down')
            ->form([
                DatePicker::make('from_date')
                    ->label(__('Import events from date')),
            ])
            ->action(function (array $data, OutlookCalendarService $outlookService) {
                $userEmail = config('services.outlook.user_email');
                $fromDate = $data['from_date'] ? Carbon::parse($data['from_date']) : null;

                try {
                    $outlookEvents = $outlookService->importEvents($fromDate);
                    $importedCount = 0;

                    foreach ($outlookEvents as $event) {
                        $exists = CalendarEvent::where('outlook_event_id', $event['id'])->exists();
                        if ($exists) continue;

                        CalendarEvent::create([
                            'title' => $event['subject'],
                            'start_datetime' => Carbon::parse($event['start']['dateTime']),
                            'end_datetime' => Carbon::parse($event['end']['dateTime']),
                            'outlook_event_id' => $event['id'],
                            'imported_from_outlook' => true,
                            'synced_to_outlook' => true,
                            'description' => $event['body']['content'] ?? '',
                            'is_teams_meeting' => $event['isOnlineMeeting'] ?? false,
                            'online_meeting_url' => $event['onlineMeeting']['joinUrl'] ?? $event['webLink'] ?? null,
                            'type' => 'single',
                            'created_by' => Auth::id(),
                        ]);
                        $importedCount++;
                    }

                    Notification::make()
                        ->title(__('Import Completed'))
                        ->body(__("Imported :count new events from Outlook", ['count' => $importedCount]))
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title(__('Import Failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
