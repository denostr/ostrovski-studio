<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Notification to the site owner about a new enquiry from the booking
 * form. No acknowledgement is sent to the visitor — the owner replies
 * personally. Sent synchronously (no queue on this project).
 */
class EnquiryReceived extends Mailable
{
    /**
     * @param  array{service: string, name: string, phone: ?string, email: string, message: string}  $enquiry
     */
    public function __construct(public array $enquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.enquiry.subject', [
                'service' => __('services.'.$this->enquiry['service'].'.title'),
            ]),
            replyTo: [$this->enquiry['email']],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.enquiry');
    }
}
