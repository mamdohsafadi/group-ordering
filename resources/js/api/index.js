import { usePage } from '@inertiajs/vue3';

/**
 * API client facade for the group-ordering contract, backed by the real
 * /api/v1 endpoints. Every request carries the shared Bearer token
 * (page.props.auth.apiToken); response and error shapes match what the
 * pages already consume — errors surface as ApiError with the server's
 * `message` string.
 */

export class ApiError extends Error {
    constructor(status, message) {
        super(message);
        this.status = status;
    }
}

async function request(token, path, { method = 'GET', body } = {}) {
    let response;

    try {
        response = await fetch(`/api/v1${path}`, {
            method,
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
                ...(body ? { 'Content-Type': 'application/json' } : {}),
            },
            body: body ? JSON.stringify(body) : undefined,
        });
    } catch {
        // Spec §10.3: network failure during any group-order call.
        throw new ApiError(0, 'Connection error. Please check your internet and try again.');
    }

    const payload = await response.json().catch(() => null);

    if (!response.ok) {
        throw new ApiError(response.status, payload?.message ?? 'Something went wrong. Please try again.');
    }

    return payload;
}

export function useApi() {
    const page = usePage();
    const token = () => page.props.auth?.apiToken;

    return {
        createGroupOrder: (payload) =>
            request(token(), '/group-orders', { method: 'POST', body: payload }),

        getGroupOrder: (id) => request(token(), `/group-orders/${id}`),

        getGroupOrderByToken: (linkToken) => request(token(), `/group-orders/by-token/${linkToken}`),

        // The join endpoint lives under the numeric id (spec §8.2) but the
        // join screen only knows the link token — resolve it first, then join.
        joinGroupOrder: async ({ link_token }) => {
            const groupOrder = await request(token(), `/group-orders/by-token/${link_token}`);

            return request(token(), `/group-orders/${groupOrder.id}/join`, {
                method: 'POST',
                body: { link_token },
            });
        },

        cancelGroupOrder: (id) => request(token(), `/group-orders/${id}/cancel`, { method: 'POST' }),

        // US-004 (spec §8.3): payload is { menu_item_id, quantity, modifiers?, special_instructions? }.
        addCartItem: (groupOrderId, payload) =>
            request(token(), `/group-orders/${groupOrderId}/cart/items`, { method: 'POST', body: payload }),
    };
}
