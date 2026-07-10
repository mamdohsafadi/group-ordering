<?php

namespace Tests\Feature\Api;

use App\Models\GroupOrder;
use App\Models\GroupParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Contract tests for GET /api/v1/group-orders/by-token/{token} (handoff §2.3). */
class ShowGroupOrderByTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/group-orders/by-token/'.str_repeat('a', 32))
            ->assertStatus(401);
    }

    public function test_unknown_tokens_return_404_with_the_exact_message(): void
    {
        $user = User::factory()->create();

        $this->withToken((string) $user->id)
            ->getJson('/api/v1/group-orders/by-token/'.str_repeat('a', 32))
            ->assertStatus(404)
            ->assertJson(['message' => 'This group order link is not valid.']);
    }

    public function test_malformed_tokens_get_the_same_contract_message(): void
    {
        // A clipped invite link must not surface Laravel's route-not-found error.
        $user = User::factory()->create();

        $this->withToken((string) $user->id)
            ->getJson('/api/v1/group-orders/by-token/'.str_repeat('a', 31))
            ->assertStatus(404)
            ->assertJson(['message' => 'This group order link is not valid.']);
    }

    public function test_any_authenticated_user_can_preview_the_group(): void
    {
        // US-002 AC5: an invitee sees participants + countdown before joining.
        $groupOrder = GroupOrder::factory()->create();

        GroupParticipant::factory()->create([
            'group_order_id' => $groupOrder->id,
            'user_id' => $groupOrder->leader_id,
        ]);

        $invitee = User::factory()->create();

        $this->withToken((string) $invitee->id)
            ->getJson("/api/v1/group-orders/by-token/{$groupOrder->shareable_link}")
            ->assertOk()
            ->assertJson([
                'id' => $groupOrder->id,
                'shareable_link' => $groupOrder->shareable_link,
                'status' => 'ACTIVE',
            ])
            ->assertJsonCount(1, 'participants');
    }
}
