<?php

namespace Tests\Feature\Panel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_login_page_is_reachable(): void
    {
        $this->get('/panel/login')
            ->assertOk()
            ->assertSee('Panel administracyjny');
    }

    public function test_a_guest_is_sent_to_the_login_page(): void
    {
        $this->get('/panel/devices')->assertRedirect('/panel/login');
        $this->get('/panel/reports')->assertRedirect('/panel/login');
    }

    public function test_an_administrator_can_sign_in(): void
    {
        $user = User::factory()->create(['password' => 'correct-horse-battery']);

        $this->post('/panel/login', [
            'email' => $user->email,
            'password' => 'correct-horse-battery',
        ])->assertRedirect('/panel/devices');

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'correct-horse-battery']);

        $this->from('/panel/login')
            ->post('/panel/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertRedirect('/panel/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_repeated_failures_are_throttled(): void
    {
        $user = User::factory()->create(['password' => 'correct-horse-battery']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/panel/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        // The correct password must not get through while the throttle holds.
        $this->post('/panel/login', ['email' => $user->email, 'password' => 'correct-horse-battery'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();

        RateLimiter::clear(mb_strtolower($user->email).'|127.0.0.1');
    }

    public function test_an_administrator_can_sign_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/panel/logout')
            ->assertRedirect('/panel/login');

        $this->assertGuest();
    }
}
