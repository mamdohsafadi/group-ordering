<?php

namespace Tests\Feature;

use App\Models\GroupOrder;
use App\Models\GroupParticipant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-005 sweep: the scheduler half of expiry (handoff §2.6). */
class ExpireGroupOrdersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_expires_stale_sessions_nobody_joined(): void
    {
        $stale = GroupOrder::factory()->windowElapsed()->create();

        GroupParticipant::factory()->create([
            'group_order_id' => $stale->id,
            'user_id' => $stale->leader_id,
        ]);

        $this->artisan('group-orders:expire')->assertSuccessful();

        $this->assertSame(GroupOrder::STATUS_EXPIRED, $stale->fresh()->status);
    }

    public function test_leaves_sessions_with_participants_and_fresh_sessions_alone(): void
    {
        $withParticipant = GroupOrder::factory()->windowElapsed()->create();

        GroupParticipant::factory()->create([
            'group_order_id' => $withParticipant->id,
            'user_id' => $withParticipant->leader_id,
        ]);
        GroupParticipant::factory()->create(['group_order_id' => $withParticipant->id]);

        $fresh = GroupOrder::factory()->create();

        $this->artisan('group-orders:expire')->assertSuccessful();

        $this->assertSame(GroupOrder::STATUS_ACTIVE, $withParticipant->fresh()->status);
        $this->assertSame(GroupOrder::STATUS_ACTIVE, $fresh->fresh()->status);
    }
}
