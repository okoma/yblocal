<?php

namespace App\Mail;

use App\Models\GuestBusinessDraft;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbandonedBusinessDraft extends Mailable
{
    use Queueable, SerializesModels;

    public GuestBusinessDraft $draft;
    public int $reminderNumber;

    /**
     * Create a new message instance.
     */
    public function __construct(GuestBusinessDraft $draft, int $reminderNumber = 1)
    {
        $this->draft = $draft;
        $this->reminderNumber = $reminderNumber;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->reminderNumber === 1 
            ? "Complete Your Business Listing - {$this->draft->getBusinessName()}"
            : "Don't Forget - Finish Your Business Listing!";

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.abandoned-business-draft',
            with: [
                'draft' => $this->draft,
                'businessName' => $this->draft->getBusinessName(),
                'completionPercentage' => $this->draft->getCompletionPercentage(),
                'resumeUrl' => $this->draft->getResumeUrl(),
                'reminderNumber' => $this->reminderNumber,
                'currentStep' => $this->draft->current_step,
            ],
        );
    }
}
