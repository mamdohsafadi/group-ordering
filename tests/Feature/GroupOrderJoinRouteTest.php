<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupOrderJoinRouteTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    public function test_guests_are_redirected_to_login_and_return_to_the_join_screen(): void
    {
        // US-002 AC2: login redirect with a return-to-group flow.
        $this->get('/join/'.self::TOKEN)->assertRedirect('/login');

        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/join/'.self::TOKEN);
    }

    public function test_authenticated_users_see_the_join_screen(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/join/'.self::TOKEN)
            ->assertOk();
    }

    public function test_malformed_tokens_are_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/join/not-a-valid-token')
            ->assertNotFound();
    }
}
