<?php

namespace Tests\Feature\Panel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_administrator(): void
    {
        $this->artisan('pensec:create-admin', ['--name' => 'Rafał', '--email' => 'admin@pensec.top'])
            ->expectsQuestion('Password', 'correct-horse-battery')
            ->expectsQuestion('Repeat password', 'correct-horse-battery')
            ->assertSuccessful();

        $user = User::sole();

        $this->assertSame('admin@pensec.top', $user->email);
        $this->assertTrue(Hash::check('correct-horse-battery', $user->password));
    }

    public function test_it_refuses_a_password_that_does_not_match_its_confirmation(): void
    {
        $this->artisan('pensec:create-admin', ['--name' => 'Rafał', '--email' => 'admin@pensec.top'])
            ->expectsQuestion('Password', 'correct-horse-battery')
            ->expectsQuestion('Repeat password', 'something-else-entirely')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_it_refuses_a_short_password(): void
    {
        $this->artisan('pensec:create-admin', ['--name' => 'Rafał', '--email' => 'admin@pensec.top'])
            ->expectsQuestion('Password', 'krotkie')
            ->expectsQuestion('Repeat password', 'krotkie')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_it_refuses_an_address_that_is_already_taken(): void
    {
        User::factory()->create(['email' => 'admin@pensec.top']);

        $this->artisan('pensec:create-admin', ['--name' => 'Rafał', '--email' => 'admin@pensec.top'])
            ->expectsQuestion('Password', 'correct-horse-battery')
            ->expectsQuestion('Repeat password', 'correct-horse-battery')
            ->assertFailed();

        $this->assertSame(1, User::count());
    }
}
