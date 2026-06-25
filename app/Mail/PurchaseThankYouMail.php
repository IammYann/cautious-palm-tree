<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseThankYouMail extends Mailable
{
    use Queueable, SerializesModels;

    public $buyer;
    public $product;
    public $order;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 5;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = 5;

    /**
     * Create a new message instance.
     */
    public function __construct($buyer, $product, $order)
    {
        $this->buyer = $buyer;
        $this->product = $product;
        $this->order = $order;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thank you for your purchase!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Add a 2-second sleep to prevent Mailtrap "Too many emails per second" error
        // when queue worker processes multiple mail jobs in a row.
        sleep(2);

        return new Content(
            view: 'emails.purchase-thank-you',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
