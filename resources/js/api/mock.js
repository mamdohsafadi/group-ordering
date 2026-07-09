/**
 * Mock implementation of the /api/v1 group-orders contract (spec paragraph 8).
 *
 * State lives in localStorage so it survives navigation and is shared across
 * tabs of the same browser — opening the share link in a second tab while
 * logged in as another demo user genuinely joins the same group, and the
 * leader's lobby picks it up live via the `storage` event.
 *
 * Every function returns the exact response shape the real endpoint will
 * return, so swapping to fetch() changes nothing in the pages.
 */

const STORE_KEY = 'beeorder-demo-group-orders';
const JOIN_WINDOW_MS = 5 * 60 * 1000; // FR-003: fixed 5-minute participation window
const LATENCY_MS = 250;

class ApiError extends Error {
    constructor(status, message) {
        super(message);
        this.status = status;
    }
}

function delay() {
    return new Promise((resolve) => setTimeout(resolve, LATENCY_MS));
}

function readStore() {
    try {
        return JSON.parse(localStorage.getItem(STORE_KEY)) ?? { groupOrders: [], nextId: 1 };
    } catch {
        return { groupOrders: [], nextId: 1 };
    }
}

function writeStore(store) {
    localStorage.setItem(STORE_KEY, JSON.stringify(store));
}

/** NFR-006: cryptographically random link token (16 bytes, hex). */
function randomToken() {
    const bytes = new Uint8Array(16);
    crypto.getRandomValues(bytes);
    return Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');
}

/**
 * FR-005: a group order with no joined participants (besides the leader)
 * expires once the window elapses. Evaluated lazily on every read.
 */
function withDerivedStatus(groupOrder) {
    if (
        groupOrder.status === 'ACTIVE' &&
        Date.now() > Date.parse(groupOrder.expires_at) &&
        groupOrder.participants.filter((p) => !p.is_leader && p.status === 'JOINED').length === 0
    ) {
        groupOrder.status = 'EXPIRED';
    }

    return groupOrder;
}

function findById(store, id) {
    const groupOrder = store.groupOrders.find((g) => g.id === Number(id));

    if (!groupOrder) {
        throw new ApiError(404, 'Group order not found.');
    }

    return withDerivedStatus(groupOrder);
}

function joinWindowOpen(groupOrder) {
    return groupOrder.status === 'ACTIVE' && Date.now() <= Date.parse(groupOrder.expires_at);
}

/** POST /api/v1/group-orders — spec 8.1 (US-001). */
export async function createGroupOrder(user, { restaurant_id, delivery_address_id, delivery_time_type, scheduled_time }) {
    await delay();

    const store = readStore();
    const now = new Date();

    const groupOrder = {
        id: store.nextId++,
        leader_id: user.id,
        leader_name: user.name,
        restaurant_id,
        delivery_address_id,
        delivery_time_type,
        scheduled_time: scheduled_time ?? null,
        status: 'ACTIVE',
        shareable_link: randomToken(),
        promo_code: null,
        created_at: now.toISOString(),
        expires_at: new Date(now.getTime() + JOIN_WINDOW_MS).toISOString(),
        submitted_at: null,
        participants: [
            {
                id: 1,
                user_id: user.id,
                name: user.name,
                is_leader: true,
                status: 'JOINED',
                joined_at: now.toISOString(),
            },
        ],
    };

    store.groupOrders.push(groupOrder);
    writeStore(store);

    return {
        group_order_id: groupOrder.id,
        shareable_link: groupOrder.shareable_link,
        expires_at: groupOrder.expires_at,
        status: 'CREATED',
    };
}

/** GET /api/v1/group-orders/{id} — lobby state (leader and participants). */
export async function getGroupOrder(id) {
    await delay();

    const store = readStore();
    const groupOrder = findById(store, id);
    writeStore(store);

    return structuredClone(groupOrder);
}

/** Resolve a shareable-link token to lobby state (US-002 join screen). */
export async function getGroupOrderByToken(token) {
    await delay();

    const store = readStore();
    const groupOrder = store.groupOrders.find((g) => g.shareable_link === token);

    if (!groupOrder) {
        throw new ApiError(404, 'This group order link is not valid.');
    }

    withDerivedStatus(groupOrder);
    writeStore(store);

    return structuredClone(groupOrder);
}

/** POST /api/v1/group-orders/{id}/join — spec 8.2 (US-002). */
export async function joinGroupOrder(user, { link_token }) {
    await delay();

    const store = readStore();
    const groupOrder = store.groupOrders.find((g) => g.shareable_link === link_token);

    if (!groupOrder) {
        throw new ApiError(404, 'This group order link is not valid.');
    }

    withDerivedStatus(groupOrder);

    // US-002 AC4: submitted or expired sessions cannot be joined.
    if (!joinWindowOpen(groupOrder)) {
        writeStore(store);
        throw new ApiError(410, 'This group order has expired or been submitted.');
    }

    const existing = groupOrder.participants.find((p) => p.user_id === user.id);

    if (existing?.status === 'JOINED') {
        writeStore(store);
        return {
            participant_id: existing.id,
            group_order_id: groupOrder.id,
            status: 'JOINED',
            joined_at: existing.joined_at,
        };
    }

    // BR-007: a participant cannot rejoin after leaving.
    if (existing?.status === 'LEFT') {
        writeStore(store);
        throw new ApiError(403, 'You cannot rejoin a group order after leaving it.');
    }

    const participant = {
        id: Math.max(0, ...groupOrder.participants.map((p) => p.id)) + 1,
        user_id: user.id,
        name: user.name,
        is_leader: false,
        status: 'JOINED',
        joined_at: new Date().toISOString(),
    };

    groupOrder.participants.push(participant);
    writeStore(store);

    return {
        participant_id: participant.id,
        group_order_id: groupOrder.id,
        status: 'JOINED',
        joined_at: participant.joined_at,
    };
}

/** Leader cancels before submission (BR-012; DELETE-style action). */
export async function cancelGroupOrder(user, id) {
    await delay();

    const store = readStore();
    const groupOrder = findById(store, id);

    if (groupOrder.leader_id !== user.id) {
        throw new ApiError(403, 'Only the group leader can cancel the group order.');
    }

    if (groupOrder.status === 'SUBMITTED') {
        throw new ApiError(409, 'Order has been submitted and cannot be modified.');
    }

    groupOrder.status = 'CANCELLED';
    writeStore(store);

    return { success: true, status: 'CANCELLED' };
}

/** Subscribe to cross-tab store changes (drives the live lobby in the demo). */
export function onStoreChange(callback) {
    const handler = (event) => {
        if (event.key === STORE_KEY) {
            callback();
        }
    };

    window.addEventListener('storage', handler);

    return () => window.removeEventListener('storage', handler);
}

export { ApiError };
