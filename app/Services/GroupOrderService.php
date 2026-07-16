<?php

namespace App\Services;

use App\Contracts\NotificationDispatcher;
use App\Models\GroupOrder;
use App\Models\GroupParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Owns the group-order lifecycle for US-001/US-002: create, resolve, join,
 * cancel and expiry (spec §8.1/§8.2, FR-001..FR-008, BR-004/BR-007/BR-012).
 *
 * All error responses use the exact messages the frontend surfaces verbatim;
 * change them only together with the UI.
 */
class GroupOrderService
{
    /** BR-004: the participation window is fixed and non-extendable. */
    public const JOIN_WINDOW_MINUTES = 5;

    public function __construct(
        private readonly NotificationDispatcher $notifier,
    ) {}

    /**
     * US-001: start a group order session. The spec's CREATED→ACTIVE
     * transition is collapsed — "link shared" is not observable server-side,
     * so the window starts at creation. The leader is enrolled as the first
     * participant (BR-006 includes them in every split).
     */
    public function create(User $leader, array $data): GroupOrder
    {
        return DB::transaction(function () use ($leader, $data) {
            $groupOrder = GroupOrder::create([
                'leader_id' => $leader->id,
                'restaurant_id' => $data['restaurant_id'],
                'delivery_address_id' => $data['delivery_address_id'],
                'delivery_time_type' => $data['delivery_time_type'],
                'scheduled_time' => $data['scheduled_time'] ?? null,
                'status' => GroupOrder::STATUS_ACTIVE,
                'shareable_link' => $this->uniqueLinkToken(),
                'expires_at' => now()->addMinutes(self::JOIN_WINDOW_MINUTES),
            ]);

            $groupOrder->participants()->create([
                'user_id' => $leader->id,
                'status' => GroupParticipant::STATUS_JOINED,
                'joined_at' => now(),
            ]);

            return $groupOrder;
        });
    }

    /**
     * Lobby state (GET /group-orders/{id}) — only the leader or an enrolled
     * participant (any status) may view it.
     */
    public function findForUser(int $id, User $user): GroupOrder
    {
        $groupOrder = GroupOrder::query()->with($this->lobbyRelations())->find($id);

        if ($groupOrder === null) {
            abort(404, 'Group order not found.');
        }

        $isMember = $groupOrder->leader_id === $user->id
            || $groupOrder->participants->contains(fn ($p) => $p->user_id === $user->id);

        if (! $isMember) {
            abort(403, 'You are not part of this group order.');
        }

        return $this->applyLazyExpiry($groupOrder);
    }

    /**
     * Invite preview (GET /group-orders/by-token/{token}) — any authenticated
     * user holding the link may look before joining (US-002 AC5).
     */
    public function findByToken(string $token): GroupOrder
    {
        $groupOrder = GroupOrder::query()
            ->with($this->lobbyRelations())
            ->where('shareable_link', $token)
            ->first();

        if ($groupOrder === null) {
            abort(404, 'This group order link is not valid.');
        }

        return $this->applyLazyExpiry($groupOrder);
    }

    /**
     * US-002: join via link. Possessing the token is the invitation — the id
     * alone is not enough. Idempotent for already-joined users; rejoin after
     * leaving is blocked (BR-007).
     *
     * @return array{participant: GroupParticipant, created: bool}
     */
    public function join(int $id, string $linkToken, User $user): array
    {
        return DB::transaction(function () use ($id, $linkToken, $user) {
            $groupOrder = GroupOrder::query()->whereKey($id)->lockForUpdate()->first();

            if ($groupOrder === null || $groupOrder->shareable_link !== $linkToken) {
                abort(404, 'This group order link is not valid.');
            }

            $this->applyLazyExpiry($groupOrder);

            // US-002 AC4: submitted, cancelled or expired sessions cannot be joined,
            // and the window itself closes at expires_at (FR-003).
            if (! $this->joinWindowOpen($groupOrder)) {
                abort(410, 'This group order has expired or been submitted.');
            }

            $existing = $groupOrder->participants()->where('user_id', $user->id)->first();

            if ($existing?->status === GroupParticipant::STATUS_JOINED) {
                return ['participant' => $existing, 'created' => false];
            }

            if ($existing?->status === GroupParticipant::STATUS_LEFT) {
                abort(403, 'You cannot rejoin a group order after leaving it.');
            }

            $participant = $groupOrder->participants()->create([
                'user_id' => $user->id,
                'status' => GroupParticipant::STATUS_JOINED,
                'joined_at' => now(),
            ]);

            $others = $groupOrder->participants()
                ->where('user_id', '!=', $user->id)
                ->where('status', GroupParticipant::STATUS_JOINED)
                ->pluck('user_id')
                ->all();

            // Never notify from inside an open transaction — a rollback must
            // also cancel the notification (runs immediately when no
            // transaction is open).
            DB::afterCommit(fn () => $this->notifier->notify($others, 'participant.joined', [
                'group_order_id' => $groupOrder->id,
                'user_id' => $user->id,
                'name' => $user->name,
            ]));

            return ['participant' => $participant, 'created' => true];
        });
    }

    /** BR-012: the leader cancels before submission; everyone is told. */
    public function cancel(int $id, User $user): GroupOrder
    {
        return DB::transaction(function () use ($id, $user) {
            $groupOrder = GroupOrder::query()->whereKey($id)->lockForUpdate()->first();

            if ($groupOrder === null) {
                abort(404, 'Group order not found.');
            }

            if ($groupOrder->leader_id !== $user->id) {
                abort(403, 'Only the group leader can cancel the group order.');
            }

            if ($groupOrder->status === GroupOrder::STATUS_SUBMITTED) {
                abort(409, 'Order has been submitted and cannot be modified.');
            }

            // Idempotent: a second cancel succeeds without re-notifying.
            if ($groupOrder->status === GroupOrder::STATUS_CANCELLED) {
                return $groupOrder;
            }

            $groupOrder->update(['status' => GroupOrder::STATUS_CANCELLED]);

            $participantIds = $groupOrder->participants()
                ->where('status', GroupParticipant::STATUS_JOINED)
                ->where('user_id', '!=', $user->id)
                ->pluck('user_id')
                ->all();

            DB::afterCommit(fn () => $this->notifier->notify($participantIds, 'group.cancelled', [
                'group_order_id' => $groupOrder->id,
            ]));

            return $groupOrder;
        });
    }

    /**
     * FR-005 sweep for the scheduler: expire ACTIVE orders past their window
     * that nobody (besides the leader) joined, and notify each leader
     * (US-001 AC5). Returns the number of orders expired.
     */
    public function expireStale(): int
    {
        $stale = GroupOrder::query()
            ->where('status', GroupOrder::STATUS_ACTIVE)
            ->where('expires_at', '<', now())
            ->whereDoesntHave('participants', function ($query) {
                $query->where('status', GroupParticipant::STATUS_JOINED)
                    ->whereColumn('user_id', '!=', 'group_orders.leader_id');
            })
            ->get();

        foreach ($stale as $groupOrder) {
            $this->markExpired($groupOrder);
        }

        return $stale->count();
    }

    public function joinWindowOpen(GroupOrder $groupOrder): bool
    {
        return $groupOrder->status === GroupOrder::STATUS_ACTIVE
            && now()->lessThanOrEqualTo($groupOrder->expires_at);
    }

    /**
     * FR-005 belt-and-braces: derive expiry on every read so clients never
     * see a stale ACTIVE session, even between scheduler runs.
     */
    private function applyLazyExpiry(GroupOrder $groupOrder): GroupOrder
    {
        $joinedOthers = $groupOrder->participants()
            ->where('status', GroupParticipant::STATUS_JOINED)
            ->where('user_id', '!=', $groupOrder->leader_id)
            ->exists();

        if (
            $groupOrder->status === GroupOrder::STATUS_ACTIVE
            && now()->greaterThan($groupOrder->expires_at)
            && ! $joinedOthers
        ) {
            $this->markExpired($groupOrder);
        }

        return $groupOrder;
    }

    private function markExpired(GroupOrder $groupOrder): void
    {
        // Guard against double-firing when a read races the scheduler, and
        // re-verify the "nobody joined" condition inside the atomic update so
        // a join committing between check and transition wins the race.
        $transitioned = GroupOrder::query()
            ->whereKey($groupOrder->id)
            ->where('status', GroupOrder::STATUS_ACTIVE)
            ->whereDoesntHave('participants', function ($query) {
                $query->where('status', GroupParticipant::STATUS_JOINED)
                    ->whereColumn('user_id', '!=', 'group_orders.leader_id');
            })
            ->update(['status' => GroupOrder::STATUS_EXPIRED]) === 1;

        if (! $transitioned) {
            return;
        }

        $groupOrder->status = GroupOrder::STATUS_EXPIRED;

        DB::afterCommit(fn () => $this->notifier->notify([$groupOrder->leader_id], 'group.expired', [
            'group_order_id' => $groupOrder->id,
        ]));
    }

    /** NFR-006: 16 random bytes, hex-encoded — 32 chars matching [a-f0-9]{32}. */
    private function uniqueLinkToken(): string
    {
        do {
            $token = bin2hex(random_bytes(16));
        } while (GroupOrder::query()->where('shareable_link', $token)->exists());

        return $token;
    }

    /** @return array<string> */
    private function lobbyRelations(): array
    {
        return ['leader', 'restaurant', 'participants.user', 'participants.cartItems.dish'];
    }
}
