<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import DemoLayout from '../../Layouts/DemoLayout.vue';
import CountdownTimer from '../../Components/CountdownTimer.vue';
import CopyLinkField from '../../Components/CopyLinkField.vue';
import ParticipantList from '../../Components/ParticipantList.vue';
import SubCartPanel from '../../Components/SubCartPanel.vue';
import { useApi } from '../../api';

const props = defineProps({
    groupOrderId: { type: Number, required: true },
});

const api = useApi();
const page = usePage();

const groupOrder = ref(null);
const error = ref(null);
const cancelling = ref(false);

const isLeader = computed(() => groupOrder.value?.leader_id === page.props.auth.user?.id);
const joinedCount = computed(
    () => groupOrder.value?.participants.filter((p) => p.status === 'JOINED').length ?? 0,
);
const joinUrl = computed(() =>
    groupOrder.value ? `${window.location.origin}/join/${groupOrder.value.shareable_link}` : '',
);
const isActive = computed(() => groupOrder.value?.status === 'ACTIVE');

// US-002 AC5 / spec 10.1: participant list updates live. Polling is the
// stopgap until broadcasting (Reverb) lands — handoff §5.
const POLL_MS = 4000;
let pollTimer = null;

async function refresh() {
    try {
        groupOrder.value = await api.getGroupOrder(props.groupOrderId);
        error.value = null;

        // Terminal states never change again — stop asking.
        if (groupOrder.value.status !== 'ACTIVE') {
            clearInterval(pollTimer);
        }
    } catch (e) {
        // A dropped poll (server restart, wifi blip) must not kill an
        // already-rendered lobby — keep the last good state and retry.
        const transient = e.status === 0 || e.status >= 500;

        if (!transient || !groupOrder.value) {
            error.value = e.message;
            clearInterval(pollTimer);
        }
    }
}

onMounted(async () => {
    await refresh();
    pollTimer = setInterval(refresh, POLL_MS);
});

onBeforeUnmount(() => clearInterval(pollTimer));

async function cancelGroup() {
    if (!window.confirm('Cancel this group order for everyone?')) {
        return;
    }

    cancelling.value = true;

    try {
        await api.cancelGroupOrder(props.groupOrderId);
        await refresh();
    } catch (e) {
        // Stop polling so the next successful refresh doesn't wipe the message.
        clearInterval(pollTimer);
        error.value = e.message;
    } finally {
        cancelling.value = false;
    }
}
</script>

<template>
    <Head title="Group Order Lobby" />
    <DemoLayout>
        <section v-if="error" class="mx-auto max-w-xl px-6 pt-24 text-center">
            <h1 class="text-2xl font-semibold text-stone-900">{{ error }}</h1>
            <Link href="/" class="mt-6 inline-block rounded-xl bg-amber-500 px-6 py-3 font-medium text-white">
                Back to home
            </Link>
        </section>

        <section v-else-if="groupOrder" class="mx-auto max-w-2xl px-6 pt-10 pb-24">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <!-- US-002 AC3 / US-004 AC1: the group menu is one tap away. -->
                    <p class="text-sm font-medium text-amber-600">
                        Group order ·
                        <Link
                            :href="`/group-orders/${groupOrder.id}/menu`"
                            class="underline decoration-amber-300 underline-offset-2 transition hover:text-amber-700"
                        >
                            {{ groupOrder.restaurant_name }}
                        </Link>
                    </p>
                    <h1 class="mt-1 text-3xl font-semibold tracking-tight text-stone-900">
                        {{ isLeader ? 'Your group lobby' : `${groupOrder.leader_name}'s group` }}
                    </h1>
                    <Link
                        :href="`/group-orders/${groupOrder.id}/menu`"
                        class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-stone-500 transition hover:text-amber-600"
                    >
                        Browse the menu & add items
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </Link>
                </div>
                <!-- US-001 AC4 / FR-004: countdown visible to everyone. -->
                <CountdownTimer v-if="isActive" :expires-at="groupOrder.expires_at" @expired="refresh" />
            </div>

            <!-- Terminal states -->
            <div
                v-if="groupOrder.status === 'EXPIRED'"
                class="mt-8 rounded-2xl border border-stone-200 bg-stone-50 p-6 text-center"
            >
                <h2 class="text-lg font-semibold text-stone-700">This group order has expired</h2>
                <p class="mt-1 text-stone-500">
                    Nobody joined within the 5-minute window, so the group was cancelled automatically.
                </p>
                <Link
                    :href="`/restaurants/${groupOrder.restaurant_id}`"
                    class="mt-4 inline-block rounded-xl bg-amber-500 px-5 py-2.5 font-medium text-white"
                >
                    Start a new one
                </Link>
            </div>

            <div
                v-else-if="groupOrder.status === 'CANCELLED'"
                class="mt-8 rounded-2xl border border-stone-200 bg-stone-50 p-6 text-center"
            >
                <h2 class="text-lg font-semibold text-stone-700">This group order was cancelled</h2>
                <p class="mt-1 text-stone-500">The group leader cancelled the order before submission.</p>
            </div>

            <template v-else>
                <!-- US-001 AC2/AC3: shareable link with copy and share options (leader only). -->
                <div v-if="isLeader" class="mt-8 rounded-2xl border border-stone-100 bg-white p-6 shadow-sm">
                    <h2 class="font-semibold text-stone-900">Invite people</h2>
                    <p class="mt-1 mb-4 text-sm text-stone-500">
                        Anyone with this link can join while the timer is running.
                    </p>
                    <CopyLinkField :url="joinUrl" />
                </div>

                <div class="mt-6 rounded-2xl border border-stone-100 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="font-semibold text-stone-900">
                            Participants
                            <span class="ml-1 text-sm font-normal text-stone-400">{{ joinedCount }}</span>
                        </h2>
                        <span v-if="isActive" class="flex items-center gap-1.5 text-xs font-medium text-green-600">
                            <span class="size-2 animate-pulse rounded-full bg-green-500" />
                            Live
                        </span>
                    </div>
                    <ParticipantList :participants="groupOrder.participants" />
                    <p v-if="joinedCount === 1" class="mt-3 text-sm text-stone-400">
                        Waiting for people to join…
                    </p>
                </div>

                <!-- US-004 AC4: the participant's own sub-cart with its running subtotal. -->
                <SubCartPanel :cart="groupOrder.my_cart" class="mt-6" />

                <div v-if="isLeader" class="mt-6 flex justify-end">
                    <button
                        type="button"
                        :disabled="cancelling"
                        class="rounded-xl border border-red-200 px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 disabled:opacity-50"
                        @click="cancelGroup"
                    >
                        Cancel group order
                    </button>
                </div>
            </template>
        </section>
    </DemoLayout>
</template>
