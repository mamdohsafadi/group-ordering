import { usePage } from '@inertiajs/vue3';
import * as mock from './mock';

/**
 * API client facade for the group-ordering contract.
 *
 * Currently backed by the localStorage mock; when the real /api/v1 endpoints
 * land, each method body becomes a fetch() with the shared Bearer token
 * (page.props.auth.apiToken) — call sites in the pages do not change.
 */
export function useApi() {
    const page = usePage();
    const user = () => page.props.auth?.user;

    return {
        createGroupOrder: (payload) => mock.createGroupOrder(user(), payload),
        getGroupOrder: (id) => mock.getGroupOrder(id),
        getGroupOrderByToken: (token) => mock.getGroupOrderByToken(token),
        joinGroupOrder: (payload) => mock.joinGroupOrder(user(), payload),
        cancelGroupOrder: (id) => mock.cancelGroupOrder(user(), id),
    };
}

export { ApiError, onStoreChange } from './mock';
