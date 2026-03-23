<?php

namespace App\Filament\Actions;

use App\Models\Matter;
use App\Services\OutlookCalendarService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SyncToOutlookAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'sync_to_outlook';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Sync to Outlook'))
            ->icon('heroicon-o-calendar')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading(__('Sync to Outlook Calendar'))
            ->modalDescription(__('This will create or update the session event in the shared Outlook calendar.'))
            ->modalIcon('heroicon-o-calendar')
            ->visible(fn($record) =>
                $record instanceof Matter
                && $record->next_session_date !== null
                && auth()->user()->can('Update:Matter')
            )
            ->action(function ($record) {
                try {
                    $service = app(OutlookCalendarService::class);
                    $eventId = $service->upsertSessionEvent($record);

                    if ($eventId && $eventId !== $record->outlook_event_id) {
                        Matter::withoutEvents(fn() =>
                        $record->updateQuietly(['outlook_event_id' => $eventId])
                        );
                    }

                    Notification::make()
                        ->title(__('Synced to Outlook'))
                        ->body(__('Session event has been synced to the shared calendar.'))
                        ->success()
                        ->send();

                } catch (\Exception $e) {
                    Log::error('[SyncToOutlook] ' . $e->getMessage());
                    Notification::make()
                        ->title(__('Sync Failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
