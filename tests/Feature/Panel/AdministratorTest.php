<?php

namespace Tests\Feature\Panel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdministratorTest extends TestCase
{
    use RefreshDatabase;

    private User $me;

    protected function setUp(): void
    {
        parent::setUp();

        $this->me = User::factory()->create(['name' => 'Rafał Kwaśniak', 'email' => 'rafal@pensec.top']);

        $this->actingAs($this->me);
    }

    /**
     * @return array<string, string>
     */
    private function details(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nowy Administrator',
            'email' => 'nowy@pensec.top',
            'password' => 'dlugie-haslo-startowe',
            'password_confirmation' => 'dlugie-haslo-startowe',
        ], $overrides);
    }

    public function test_a_guest_cannot_reach_the_list(): void
    {
        $this->app['auth']->logout();

        $this->get('/panel/administrators')->assertRedirect('/panel/login');
        $this->get('/panel/administrators/new')->assertRedirect('/panel/login');
    }

    public function test_it_lists_every_administrator(): void
    {
        User::factory()->create(['name' => 'Anna Nowak', 'email' => 'anna@pensec.top']);

        $this->get('/panel/administrators')
            ->assertOk()
            ->assertSee('Rafał Kwaśniak')
            ->assertSee('Anna Nowak')
            ->assertSee('anna@pensec.top')
            ->assertSee('to Ty');
    }

    public function test_it_creates_an_account_that_works_immediately(): void
    {
        $this->post('/panel/administrators', $this->details())
            ->assertRedirect('/panel/administrators')
            ->assertSessionHas('status');

        $created = User::where('email', 'nowy@pensec.top')->sole();

        $this->assertSame('Nowy Administrator', $created->name);
        $this->assertTrue(Hash::check('dlugie-haslo-startowe', $created->password));

        // No activation step stands between creation and signing in.
        $this->post('/panel/logout');
        $this->post('/panel/login', ['email' => 'nowy@pensec.top', 'password' => 'dlugie-haslo-startowe'])
            ->assertRedirect('/panel/devices');

        $this->assertAuthenticatedAs($created);
    }

    public function test_the_password_is_never_stored_as_written(): void
    {
        $this->post('/panel/administrators', $this->details());

        $this->assertDatabaseMissing('users', ['password' => 'dlugie-haslo-startowe']);
    }

    public function test_a_taken_address_is_rejected(): void
    {
        $this->from('/panel/administrators/new')
            ->post('/panel/administrators', $this->details(['email' => 'rafal@pensec.top']))
            ->assertSessionHasErrors('email');

        $this->assertSame(1, User::count());
    }

    public function test_a_short_password_is_rejected(): void
    {
        $this->from('/panel/administrators/new')
            ->post('/panel/administrators', $this->details([
                'password' => 'krotkie',
                'password_confirmation' => 'krotkie',
            ]))
            ->assertSessionHasErrors('password');

        $this->assertSame(1, User::count());
    }

    public function test_a_mistyped_confirmation_is_rejected(): void
    {
        $this->from('/panel/administrators/new')
            ->post('/panel/administrators', $this->details(['password_confirmation' => 'zupelnie-inne-haslo']))
            ->assertSessionHasErrors('password');

        $this->assertSame(1, User::count());
    }

    public function test_every_field_is_required(): void
    {
        $this->from('/panel/administrators/new')
            ->post('/panel/administrators', [])
            ->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertSame(1, User::count());
    }

    public function test_an_administrator_can_be_removed(): void
    {
        $other = User::factory()->create(['name' => 'Anna Nowak']);

        $this->post("/panel/administrators/{$other->id}/delete")
            ->assertRedirect('/panel/administrators')
            ->assertSessionHas('status');

        $this->assertSame(0, User::where('id', $other->id)->count());
    }

    public function test_you_cannot_remove_your_own_account(): void
    {
        $this->post("/panel/administrators/{$this->me->id}/delete")
            ->assertRedirect('/panel/administrators')
            ->assertSessionHas('error');

        $this->assertSame(1, User::count());
    }
}
