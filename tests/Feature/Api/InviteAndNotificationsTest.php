<?php

namespace Tests\Feature\Api;

use App\Models\DemoNotification;
use App\Models\GroupOrder;
use App\Models\GroupParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Contract tests for invites and the demo notification inbox (US-003). */
class InviteAndNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private GroupOrder $groupOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->groupOrder = GroupOrder::factory()->create();

        GroupParticipant::factory()->create([
            'group_order_id' => $this->groupOrder->id,
            'user_id' => $this->groupOrder->leader_id,
        ]);
    }

    public function test_leader_invites_users_and_they_receive_the_deep_link(): void
    {
        $invitees = User::factory()->count(2)->create();

        $this->withToken((string) $this->groupOrder->leader_id)
            ->postJson("/api/v1/group-orders/{$this->groupOrder->id}/invite", [
                'user_ids' => $invitees->pluck('id')->all(),
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'invited' => 2]);

        // US-003 AC1/AC2: each invitee got a notification carrying the join token.
        $notification = DemoNotification::query()->where('user_id', $invitees[0]->id)->firstOrFail();
        $this->assertSame('group.invited', $notification->type);
        $this->assertSame($this->groupOrder->shareable_link, $notification->payload['token']);
    }

    public function test_only_the_leader_can_invite(): void
    {
        $member = User::factory()->create();
        GroupParticipant::factory()->create([
            'group_order_id' => $this->groupOrder->id,
            'user_id' => $member->id,
        ]);

        $this->withToken((string) $member->id)
            ->postJson("/api/v1/group-orders/{$this->groupOrder->id}/invite", [
                'user_ids' => [User::factory()->create()->id],
            ])->assertStatus(403);
    }

    public function test_already_joined_users_are_not_reinvited(): void
    {
        $member = User::factory()->create();
        GroupParticipant::factory()->create([
            'group_order_id' => $this->groupOrder->id,
            'user_id' => $member->id,
        ]);

        $this->withToken((string) $this->groupOrder->leader_id)
            ->postJson("/api/v1/group-orders/{$this->groupOrder->id}/invite", [
                'user_ids' => [$member->id, $this->groupOrder->leader_id],
            ])
            ->assertOk()
            ->assertJson(['invited' => 0]);
    }

    public function test_invites_stop_when_the_window_closes(): void
    {
        $this->groupOrder->update(['status' => GroupOrder::STATUS_SUBMITTED]);

        $this->withToken((string) $this->groupOrder->leader_id)
            ->postJson("/api/v1/group-orders/{$this->groupOrder->id}/invite", [
                'user_ids' => [User::factory()->create()->id],
            ])->assertStatus(410);
    }

    public function test_the_inbox_is_private_and_marks_read(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        DemoNotification::create(['user_id' => $user->id, 'type' => 'group.invited', 'payload' => []]);
        DemoNotification::create(['user_id' => $other->id, 'type' => 'group.invited', 'payload' => []]);

        $this->withToken((string) $user->id)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'notifications')
            ->assertJson(['unread_count' => 1]);

        $this->withToken((string) $user->id)
            ->postJson('/api/v1/notifications/read')
            ->assertOk();

        $this->withToken((string) $user->id)
            ->getJson('/api/v1/notifications')
            ->assertJson(['unread_count' => 0]);
    }
}
