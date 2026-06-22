<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Events\UserRegistered;
use App\Listeners\SendRegistrationEmail;
use App\Listeners\LogRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EventsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test UserRegistered event is dispatched
     */
    public function test_user_registered_event_is_dispatched(): void
    {
        Event::fake();

        $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        Event::assertDispatched(UserRegistered::class);
    }

    /**
     * Test LogRegistration listener logs user data
     */
    public function test_registration_is_logged(): void
    {
        Log::spy();

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);

        event(new UserRegistered($user));

        Log::shouldHaveReceived('info')->with(
            'New user registered',
            \Mockery::subset([
                'user_id' => $user->id,
                'email' => 'john@example.com',
            ])
        );
    }
}