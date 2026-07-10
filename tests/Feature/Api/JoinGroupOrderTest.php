<?php

namespace Tests\Feature\Api;

use App\Models\GroupOrder;
use App\Models\GroupParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Contract tests for POST /api/v1/group-orders/{id}/join (handoff §2.4, spec §8.2). */
class JoinGroupOrderTest extends TestCase
{
    use RefreshDatabase;

    private function activeGroup(): GroupOrder
    {
        $groupOrder = GroupOrder::factory()->create();

        GroupParticipant::factory()->create([
            'group_order_id' => $groupOrder->id,
            'user_id' => $groupOrder->leader_id,
        ]);

        return $groupOrder;
    }

    public function test_requires_authentication(): void
    {
        $this->postJson('/api/v1/group-orders/1/join', ['link_token' => str_repeat('a', 32)])
            ->assertStatus(401);
    }

    public function test_the_id_alone_is_not_enough_the_token_must_match(): void
    {
        $groupOrder = $this->activeGroup();
        $user = User::factory()->create();

        $this->withToken((string) $user->id)
            ->postJson("/api/v1/group-orders/{$groupOrder->id}/join", [
                'link_token' => str_repeat('b', 32), // well-formed but wrong
            ])
            ->assertStatus(404)
            ->assertJson(['message' => 'This group order link is not valid.']);
    }

    public function test_malformed_tokens_fail_validation(): void
    {
        $user = User::factory()->create();

        $this->withToken((string) $user->id)
            ->postJson('/api/v1/group-orders/1/join', ['link_token' => 'not-a-token'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('link_token');
    }

    public function test_joining_within_the_window_creates_a_participant(): void
    {
        $this->freezeSecond();

        $groupOrder = $this->activeGroup();
        $user = User::factory()->create();

        $response = $this->withToken((string) $user->id)
            ->postJson("/api/v1/group-orders/{$groupOrder->id}/join", [
                'link_token' => $groupOrder->shareable_link,
            ])
            ->assertCreated()
            ->assertJson([
                'group_order_id' => $groupOrder->id,
                'status' => 'JOINED',
                'joined_at' => now()->toISOString(),
            ]);

        $this->assertDatabaseHas('group_participants', [
            'id' => $response->json('participant_id'),
            'group_order_id' => $groupOrder->id,
            'user_id' => $user->id,
            'status' => GroupParticipant::STATUS_JOINED,
        ]);
    }

    public function test_joining_twice_is_idempotent(): void
    {
        $groupOrder = $this->activeGroup();
        $user = User::factory()->create();

        $first = $this->withToken((string) $user->id)
            ->postJson("/api/v1/group-orders/{$groupOrder->id}/join", [
                'link_token' => $groupOrder->shareable_link,
            ])
            ->assertCreated();

        $this->withToken((string) $user->id)
            ->postJson("/api/v1/group-orders/{$groupOrder->id}/join", [
                'link_token' => $groupOrder->shareable_link,
            ])
            ->assertOk()
            ->assertJson(['participant_id' => $first->json('participant_id')]);

        $this->assertSame(
            1,
            GroupParticipant::query()
                ->where('group_order_id', $groupOrder->id)
                ->where('user_id', $user->id)
                ->count(),
        );
    }

    public function test_rejoining_after_leaving_is_blocked(): void
    {
        // BR-007: a participant cannot rejoin after leaving.
        $groupOrder = $this->activeGroup();
        $user = User::factory()->create();

        GroupParticipant::factory()->left()->create([
            'group_order_id' => $groupOrder->id,
            'user_id' => $user->id,
        ]);

        $this->withToken((string) $user->id)
            ->postJson("/api/v1/group-orders/{$groupOrder->id}/join", [
                'link_token' => $groupOrder->shareable_link,
            ])
            ->assertStatus(403)
            ->assertJson(['message' => 'You cannot rejoin a group order after leaving it.']);
    }

    public function test_joining_after_the_window_closes_returns_410(): void
    {
        // US-002 AC4 — the frontend shows this message verbatim.
        $groupOrder = GroupOrder::factory()->windowElapsed()->create();

        GroupParticipant::factory()->create([
            'group_order_id' => $groupOrder->id,
            'user_id' => $groupOrder->leader_id,
        ]);

        $user = User::factory()->create();

        $this->withToken((string) $user->id)
            ->postJson("/api/v1/group-orders/{$groupOrder->id}/join", [
                'link_token' => $groupOrder->shareable_link,
            ])
            ->assertStatus(410)
            ->assertJson(['message' => 'This group order has expired or been submitted.']);
    }

    public function test_joining_a_cancelled_group_returns_410(): void
    {
        $groupOrder = GroupOrder::factory()->cancelled()->create();
        $user = User::factory()->create();

        $this->withToken((string) $user->id)
            ->postJson("/api/v1/group-orders/{$groupOrder->id}/join", [
                'link_token' => $groupOrder->shareable_link,
            ])
            ->assertStatus(410);
    }
}
