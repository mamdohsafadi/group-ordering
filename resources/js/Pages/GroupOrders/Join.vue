<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import DemoLayout from '../../Layouts/DemoLayout.vue';
import CountdownTimer from '../../Components/CountdownTimer.vue';
import ParticipantList from '../../Components/ParticipantList.vue';
import { onStoreChange, useApi } from '../../api';
import { findRestaurant } from '../../api/fixtures';

const props = defineProps({
    token: { type: String, required: true },
});

const api = useApi();
const page = usePage();

const groupOrder = ref(null);
const loading = ref(true);
const error = ref(null);
const joining = ref(false);

const restaurant = computed(() => (groupOrder.value ? findRestaurant(groupOrder.value.restaurant_id) : null));
const joinable = computed(() => groupOrder.value?.status === 'ACTIVE');
const alreadyIn = computed(() =>
    groupOrder.value?.participants.some(
        (p) => p.user_id === page.props.auth.user?.id && p.status === 'JOINED',
    ),
);

let unsubscribe = null;

async function refresh() {
    try {
        groupOrder.value = await api.getGroupOrderByToken(props.token);
        error.value = null;
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    await refresh();
    unsubscribe = onStoreChange(refresh);
});

onBeforeUnmount(() => unsubscribe?.());

async function join() {
    joining.value = true;

    try {
        // US-002 AC3: confirming adds the user as a participant.
        const joined = await api.joinGroupOrder({ link_token: props.token });
        router.visit(`/group-orders/${joined.group_order_id}/lobby`);
    } catch (e) {
        error.value = e.message;
        joining.value = false;
        await refresh();
    }
}
</script>

<template>
    <Head title="Join Group Order" />
    <DemoLayout>
        <section class="mx-auto max-w-xl px-6 pt-16 pb-24">
            <div v-if="loading" class="text-center text-stone-400">Loading group order…</div>

            <!-- US-002 AC4 / spec 10.3: expired, submitted, or invalid link. -->
            <div
                v-else-if="error || !joinable"
                class="rounded-2xl border border-stone-200 bg-stone-50 p-8 text-center"
            >
                <span
                    class="mx-auto flex size-12 items-center justify-center rounded-full bg-stone-200 text-stone-500"
                >
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </span>
                <h1 class="mt-4 text-xl font-semibold text-stone-800">
                    {{ error ?? 'This group order has expired or already been submitted.' }}
                </h1>
                <Link
                    href="/"
                    class="mt-6 inline-block rounded-xl bg-amber-500 px-6 py-3 font-medium text-white shadow-lg shadow-amber-500/25 transition hover:bg-amber-600"
                >
                    Back to home
                </Link>
            </div>

            <template v-else>
                <div class="text-center">
                    <p class="text-sm font-medium text-amber-600">You're invited</p>
                    <h1 class="mt-1 text-3xl font-semibold tracking-tight text-stone-900">
                        {{ groupOrder.leader_name }} started a group order
                    </h1>
                    <p class="mt-2 text-stone-500">
                        from <span class="font-medium text-stone-700">{{ restaurant?.name }}</span> — add your own
                        items, pay nothing: the leader covers the bill.
                    </p>
                </div>

                <!-- US-002 AC5: countdown and current participants are visible before joining. -->
                <div class="mt-8 flex justify-center">
                    <CountdownTimer :expires-at="groupOrder.expires_at" @expired="refresh" />
                </div>

                <div class="mt-8 rounded-2xl border border-stone-100 bg-white p-6 shadow-sm">
                    <h2 class="mb-4 font-semibold text-stone-900">Already in the group</h2>
                    <ParticipantList :participants="groupOrder.participants" />
                </div>

                <div class="mt-8 flex flex-col items-center gap-3">
                    <Link
                        v-if="alreadyIn"
                        :href="`/group-orders/${groupOrder.id}/lobby`"
                        class="rounded-xl bg-amber-500 px-8 py-3.5 font-medium text-white shadow-lg shadow-amber-500/25 transition hover:bg-amber-600"
                    >
                        You're in — open the lobby
                    </Link>
                    <button
                        v-else
                        type="button"
                        :disabled="joining"
                        class="rounded-xl bg-amber-500 px-8 py-3.5 font-medium text-white shadow-lg shadow-amber-500/25 transition hover:bg-amber-600 disabled:opacity-50"
                        @click="join"
                    >
                        {{ joining ? 'Joining…' : 'Join this group order' }}
                    </button>
                    <Link href="/" class="text-sm text-stone-400 transition hover:text-stone-600">No thanks</Link>
                </div>
            </template>
        </section>
    </DemoLayout>
</template>
