<?php

namespace App\Mail;

use App\Models\Matter;
use App\Models\MatterRequest;
use App\Models\Party;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class NewRequestNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Matter $matter, public MatterRequest $matterRequest)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('New Request Submitted')
            . ' — ' . $this->matter->year . '/' . $this->matter->number . ($this->matter->type ? ' — ' . $this->matter->type->name : ''),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.new-request-notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return $this->matterRequest->attachments
            ->map(function ($attachment) {
                // Check existence directly on the disk
                if (!Storage::disk('public')->exists($attachment->path)) {
                    return null;
                }

                // Use fromStorageDisk for better compatibility with Laravel's filesystem
                return Attachment::fromStorageDisk('public', $attachment->path)
                    ->as($attachment->name)
                    ->withMime(
                        Storage::disk('public')->mimeType($attachment->path) ?? 'application/octet-stream'
                    );
            })
            ->filter() // Removes the 'null' entries where files didn't exist
            ->values()
            ->toArray();
    }
}
