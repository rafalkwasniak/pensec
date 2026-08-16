<?php

namespace Tests\Feature\Panel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $password = 'correct-horse-battery'): User
    {
        return User::factory()->create([
            'name' => 'Rafał Kwaśniak',
            'email' => 'admin@pensec.top',
            'password' => $password,
        ]);
    }

    public function test_a_guest_cannot_reach_the_account_page(): void
    {
        $this->get('/panel/account')->assertRedirect('/panel/login');
    }

    public function test_it_shows_the_current_account_details(): void
    {
        $this->actingAs($this->admin())
            ->get('/panel/account')
            ->assertOk()
            ->assertSee('admin@pensec.top')
            ->assertSee('Rafał Kwaśniak');
    }

    public function test_an_administrator_can_change_their_address(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->post('/panel/account', ['name' => 'Rafał Kwaśniak', 'email' => 'nowy@pensec.top'])
            ->assertRedirect('/panel/account')
            ->assertSessionHas('status');

        $this->assertSame('nowy@pensec.top', $user->fresh()->email);
    }

    public function test_an_address_belonging_to_someone_else_is_rejected(): void
    {
        $user = $this->admin();
        User::factory()->create(['email' => 'zajety@pensec.top']);

        $this->actingAs($user)
            ->from('/panel/account')
            ->post('/panel/account', ['name' => 'Rafał', 'email' => 'zajety@pensec.top'])
            ->assertSessionHasErrors('email');

        $this->assertSame('admin@pensec.top', $user->fresh()->email);
    }

    public function test_keeping_your_own_address_is_not_treated_as_taken(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->post('/panel/account', ['name' => 'Nowe Imię', 'email' => 'admin@pensec.top'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Nowe Imię', $user->fresh()->name);
    }

    public function test_an_administrator_can_change_their_password(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->post('/panel/account/password', [
                'password' => 'nowe-dlugie-haslo-2026',
                'password_confirmation' => 'nowe-dlugie-haslo-2026',
            ])
            ->assertRedirect('/panel/account')
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('nowe-dlugie-haslo-2026', $user->fresh()->password));
    }

    public function test_the_old_password_is_not_needed_to_set_a_new_one(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->post('/panel/account/password', [
                'current_password' => 'cokolwiek-nieprawidlowego',
                'password' => 'nowe-dlugie-haslo-2026',
                'password_confirmation' => 'nowe-dlugie-haslo-2026',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('nowe-dlugie-haslo-2026', $user->fresh()->password));
    }

    public function test_only_a_signed_in_administrator_can_set_a_password(): void
    {
        $user = $this->admin();

        $this->post('/panel/account/password', [
            'password' => 'nowe-dlugie-haslo-2026',
            'password_confirmation' => 'nowe-dlugie-haslo-2026',
        ])->assertRedirect('/panel/login');

        $this->assertTrue(Hash::check('correct-horse-battery', $user->fresh()->password));
    }

    public function test_the_new_password_works_for_signing_in(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->post('/panel/account/password', [
            'password' => 'nowe-dlugie-haslo-2026',
            'password_confirmation' => 'nowe-dlugie-haslo-2026',
        ]);

        $this->post('/panel/logout');

        $this->post('/panel/login', ['email' => 'admin@pensec.top', 'password' => 'nowe-dlugie-haslo-2026'])
            ->assertRedirect('/panel/devices');

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_short_password_is_rejected(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->from('/panel/account')
            ->post('/panel/account/password', [
                'password' => 'krotkie',
                'password_confirmation' => 'krotkie',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('correct-horse-battery', $user->fresh()->password));
    }

    public function test_a_mistyped_confirmation_is_rejected(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->from('/panel/account')
            ->post('/panel/account/password', [
                'password' => 'nowe-dlugie-haslo-2026',
                'password_confirmation' => 'zupelnie-inne-haslo-2026',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('correct-horse-battery', $user->fresh()->password));
    }
}
