<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useApi } from '../api';

// US-003 AC1/AC2: in-app notifications with a deep link into the join flow.
const api = useApi();
const page = usePage();

const open = ref(false);
const notifications = ref([]);
const unread = ref(0);

const POLL_MS = 8000;
let pollTimer = null;

async function refresh() {
    if (!page.props.auth?.user) {
        return;
    }

    try {
        const data = await api.getNotifications();
        notifications.value = data.notifications;
        unread.value = data.unread_count;
    } catch {
        // The bell is decoration — never let a failed poll break the page.
    }
}

onMounted(() => {
    refresh();
    pollTimer = setInterval(refresh, POLL_MS);
});

onBeforeUnmount(() => clearInterval(pollTimer));

async function toggle() {
    open.value = !open.value;

    if (open.value && unread.value > 0) {
        await api.markNotificationsRead().catch(() => {});
        unread.value = 0;
    }
}

const items = computed(() =>
    notifications.value.map((n) => {
        const p = n.payload ?? {};

        switch (n.type) {
            case 'group.invited':
                return { ...n, text: `${p.leader_name} invited you to a group order from ${p.restaurant_name}`, href: `/join/${p.token}` };
            case 'participant.joined':
                return { ...n, text: `${p.name} joined your group order`, href: `/group-orders/${p.group_order_id}/lobby` };
            case 'participant.left':
                return { ...n, text: `${p.name} left the group order`, href: `/group-orders/${p.group_order_id}/lobby` };
            case 'group.cancelled':
                return { ...n, text: 'A group order you joined was cancelled by the leader', href: null };
            case 'group.expired':
                return { ...n, text: 'Your group order expired before anyone joined', href: null };
            case 'order.confirmed':
                return { ...n, text: 'Order placed! Check your invoice', href: `/group-orders/${p.group_order_id}/lobby` };
            default:
                return { ...n, text: n.type, href: null };
        }
    }),
);
</script>

<template>
    <div v-if="page.props.auth?.user" class="relative">
        <button
            type="button"
            class="relative flex size-9 items-center justify-center rounded-lg border border-stone-200 text-stone-500 transition hover:bg-stone-50"
            @click="toggle"
        >
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"
                />
            </svg>
            <span
                v-if="unread > 0"
                class="absolute -top-1 -right-1 flex size-5 items-center justify-center rounded-full bg-amber-500 text-[10px] font-bold text-white"
            >
                {{ unread > 9 ? '9+' : unread }}
            </span>
        </button>

        <div
            v-if="open"
            class="absolute right-0 z-50 mt-2 w-80 rounded-2xl border border-stone-100 bg-white p-2 shadow-xl"
        >
            <p v-if="!items.length" class="p-4 text-sm text-stone-400">No notifications yet.</p>
            <ul v-else class="flex max-h-96 flex-col gap-1 overflow-y-auto">
                <li v-for="item in items" :key="item.id">
                    <component
                        :is="item.href ? Link : 'div'"
                        :href="item.href ?? undefined"
                        class="block rounded-xl px-3 py-2.5 text-sm transition"
                        :class="[item.href ? 'cursor-pointer hover:bg-amber-50' : '', item.read_at ? 'text-stone-400' : 'font-medium text-stone-700']"
                        @click="open = false"
                    >
                        {{ item.text }}
                    </component>
                </li>
            </ul>
        </div>
    </div>
</template>
