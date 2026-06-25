<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class SendRegistrationEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 5;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = 5;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserRegistered $event): void
    {
        // Add a 2-second sleep to prevent Mailtrap "Too many emails per second" error
        // when queue worker processes multiple welcome emails in a row.
        sleep(2);

        Mail::send('emails.welcome', ['user' => $event->user], function (Message $message) use ($event) {
            $message->to($event->user->email)
                    ->subject('Welcome to Our Shop!');
        });
    }
}
