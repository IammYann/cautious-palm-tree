<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedisThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_requests_are_throttled_after_repeated_failures(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')
                ->post('/login', [
                    'email' => 'user@example.com',
                    'password' => 'wrong-password',
                ]);
        }

        $response = $this->from('/login')
            ->post('/login', [
                'email' => 'user@example.com',
                'password' => 'wrong-password',
            ]);

        $response->assertStatus(429);
    }
}
