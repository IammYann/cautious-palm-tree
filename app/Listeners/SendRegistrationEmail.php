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
        Mail::send('emails.welcome', ['user' => $event->user], function (Message $message) use ($event) {
            $message->to($event->user->email)
                    ->subject('Welcome to Our Shop!');
        });
    }
}
class SendWelcomeEmail
{
    public function handle(UserRegistered $event)
    {
        Log::info('Listener is working!', [
            'user' => $event->user->email
        ]);
    }
}
