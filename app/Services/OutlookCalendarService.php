<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OutlookCalendarService
{

    private function calendarEmail(): string
    {
        return config('services.microsoft.calendar_email', 'info@jpaemirates.com');
    }

    private function getAccessToken(): string
    {
        return Cache::remember('outlook_access_token', 3500, function () {
            $response = Http::asForm()->post(
                "https://login.microsoftonline.com/" . config('services.microsoft.tenant_id') . "/oauth2/v2.0/token",
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => config('services.microsoft.client_id'),
                    'client_secret' => config('services.microsoft.client_secret'),
                    'scope' => 'https://graph.microsoft.com/.default',
                ]
            );

            if ($response->failed()) {
                throw new \RuntimeException('Failed to get Outlook access token: ' . $response->body());
            }

            return $response->json('access_token');
        });
    }

    /**
     * @throws ConnectionException
     */
    private function graphRequest(string $method, string $endpoint, array $body = [], array $header = []): \Illuminate\Http\Client\Response
    {
        $request = Http::withToken($this->getAccessToken())
            ->withHeaders([
                    'Content-Type' => 'application/json'
                ] + $header);

        return $method === 'DELETE'
            ? $request->delete("https://graph.microsoft.com/v1.0{$endpoint}")
            : $request->{strtolower($method)}("https://graph.microsoft.com/v1.0{$endpoint}", $body);
    }

    /**
     * @throws ConnectionException
     */
    public function upsertSessionEvent(\App\Models\Matter $matter): ?string
    {
        if (!$matter->next_session_date) return null;

        $body = $this->buildEventBody($matter);

        // Update if already synced
        if ($matter->outlook_event_id) {
            $response = $this->graphRequest(
                'PATCH',
                "/users/{$this->calendarEmail()}/events/{$matter->outlook_event_id}",
                $body
            );

            if ($response->successful()) {
                //Log::info("[Outlook] Updated event for matter #{$matter->number}");
                return $matter->outlook_event_id;
            }

            // Event may have been deleted manually — fall through to create
            //Log::warning("[Outlook] Update failed ({$response->status()}), creating new event");
        }

        // Create new
        $response = $this->graphRequest(
            'POST',
            "/users/{$this->calendarEmail()}/events",
            $body
        );

        if ($response->failed()) {
            //::error("[Outlook] Failed to create event: " . $response->body());
            throw new \RuntimeException('Outlook event creation failed: ' . $response->json('error.message'));
        }

        $eventId = $response->json('id');
        //Log::info("[Outlook] Created event for matter #{$matter->number} → {$eventId}");

        return $eventId;
    }

    /**
     * @throws ConnectionException
     */
    public function createEvent(array $data): string
    {
        $response = $this->graphRequest(
            'POST',
            "/users/{$this->calendarEmail()}/events",
            $this->formatEventData($data)
        );

        if ($response->failed()) {
            throw new \RuntimeException('Outlook event creation failed: ' . $response->json('error.message'));
        }

        return $response->json('id');
    }

    /**
     * @throws ConnectionException
     */
    public function updateEvent(string $eventId, array $data): void
    {
        $response = $this->graphRequest(
            'PATCH',
            "/users/{$this->calendarEmail()}/events/{$eventId}",
            $this->formatEventData($data)
        );

        if ($response->failed()) {
            throw new \RuntimeException('Outlook event update failed: ' . $response->json('error.message'));
        }
    }

    /**
     * @throws ConnectionException
     */
    public function deleteEvent(string $eventId): void
    {
        $response = $this->graphRequest(
            'DELETE',
            "/users/{$this->calendarEmail()}/events/{$eventId}"
        );

        if ($response->failed() && $response->status() !== 404) {
            throw new \RuntimeException('Outlook event deletion failed: ' . $response->json('error.message'));
        }
    }

    /**
     * @throws ConnectionException
     */
    public function importEventsToDatabase(): int
    {
        $events = $this->getEvents();
        $importedCount = 0;

        foreach ($events as $event) {
            $matterId = $this->findMatterIdFromText($event['title']);

            \App\Models\Calendar::updateOrCreate(
                ['outlook_event_id' => $event['id']],
                [
                    'matter_id' => $matterId,
                    'title' => $event['title'],
                    'start_at' => Carbon::parse($event['starts_at']),
                    'end_at' => Carbon::parse($event['ends_at']),
                    'location' => $event['location'],
                    'description' => $event['description'],
                ]
            );
            $importedCount++;
        }

        return $importedCount;
    }

    private function findMatterIdFromText(string $text): ?int
    {
        // Matches patterns like "2024/123" or "123/2024"
        // Adjusting to find 4-digit year and 1-digit or more number
        if (preg_match('/(\d{4})\/(\d+)/', $text, $matches)) {
            $year = $matches[1];
            $number = $matches[2];
        } elseif (preg_match('/(\d+)\/(\d{4})/', $text, $matches)) {
            $number = $matches[1];
            $year = $matches[2];
        } else {
            return null;
        }

        return \App\Models\Matter::where('year', $year)
            ->where('number', $number)
            ->first()
            ?->id;
    }

    private function formatEventData(array $data): array
    {
        $timezone = 'Asia/Dubai';

        return [
            'subject' => $data['title'] ?? $data['name'] ?? '',
            'start' => [
                'dateTime' => Carbon::parse($data['starts_at'])->format('Y-m-d\TH:i:s'),
                'timeZone' => $timezone,
            ],
            'end' => [
                'dateTime' => Carbon::parse($data['ends_at'])->format('Y-m-d\TH:i:s'),
                'timeZone' => $timezone,
            ],
            'location' => [
                'displayName' => $data['location'] ?? '',
            ],
            'body' => [
                'contentType' => 'text',
                'content' => $data['description'] ?? '',
            ],
        ];
    }

    public function buildEventBody(\App\Models\Matter|int $matter): array
    {
        if (is_int($matter)) $matter = \App\Models\Matter::find($matter);
        $date = Carbon::parse($matter->next_session_date)->format('Y-m-d');
        $time = Carbon::parse($matter->next_session_date)->format('H:i');
        $endTime = Carbon::parse($matter->next_session_date)->addHours(1)->format('H:i');
        $timezone = 'Asia/Dubai';

        // Party names for description
        $plaintiffs = $matter->mainPartiesOnly
            ->where('type', 'plaintiff')
            ->map(fn($mp) => $mp->party?->name)
            ->filter()->join(', ');

        $defendants = $matter->mainPartiesOnly
            ->where('type', 'defendant')
            ->map(fn($mp) => $mp->party?->name)
            ->filter()->join(', ');

        $experts = $matter->mainExpertsOnly
            ->map(fn($mp) => $mp->party?->name)
            ->filter()->join(', ');

        $description = collect([
            __('Matter') . ': ' . $matter->year . '/' . $matter->number,
            __('Court') . ': ' . ($matter->court?->name ?? '—'),
            __('Type') . ': ' . ($matter->type?->name ?? '—'),
            $plaintiffs ? __('Plaintiffs') . ': ' . $plaintiffs : null,
            $defendants ? __('Defendants') . ': ' . $defendants : null,
            $experts ? __('Experts') . ': ' . $experts : null,
        ])->filter()->join("\n");

        return [
            'subject' => $matter->year . '/' . $matter->number
                . ' — ' . ($matter->court?->name ?? '')
                . ' — ' . ($matter->type?->name ?? ''),

            'start' => [
                'dateTime' => $date . $time,
                'timeZone' => $timezone,
            ],
            'end' => [
                'dateTime' => $date . 'T10:00:00',
                'timeZone' => $timezone,
            ],

            'isAllDay' => false,

            'body' => [
                'contentType' => 'text',
                'content' => $description,
            ],

            'categories' => ['eExpert'],

            'location' => [
                'displayName' => $matter->court?->name ?? '',
            ],
        ];
    }

    /**
     * @throws ConnectionException
     */
    public function getEvents(): array
    {
        $start = now()->subDays(7)->utc()->format('Y-m-d\TH:i:s\Z');
        $end = now()->addDays(60)->utc()->format('Y-m-d\TH:i:s\Z');

        $url = "https://graph.microsoft.com/v1.0/users/{$this->calendarEmail()}/calendarView"
            . '?startDateTime=' . $start
            . '&endDateTime=' . $end
            . '&$select=id,subject,start,end,isAllDay,location,bodyPreview,categories'
            . '&$top=100'
            . '&$orderby=start/dateTime';

        $response = Http::withToken($this->getAccessToken())
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Prefer' => 'outlook.timezone="Asia/Dubai"',
            ])
            ->get($url);


        if ($response->failed()) {
            Log::error('[Outlook] Failed to fetch events: ' . $response->body());
            return [];
        }
        return collect($response->json('value', []))
            ->map(fn($e) => [
                'id' => $e['id'],
                'starts_at' => $e['start']['dateTime'],
                'ends_at' => $e['end']['dateTime'],
                'title' => $e['subject'],
                'isAllDay' => $e['isAllDay'],
                'location' => $e['location']['displayName'] ?? '',
                'description' => $e['bodyPreview'],
                'categories' => $e['categories'],
            ])
            ->toArray();

    }

}
