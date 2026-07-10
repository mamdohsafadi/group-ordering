<script setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useApi } from '../api';

const props = defineProps({
    restaurantId: { type: Number, required: true },
    // The logged-in user's saved addresses, passed down from the menu page.
    addresses: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const api = useApi();
const form = ref({
    delivery_address_id: props.addresses[0]?.id ?? null,
    delivery_time_type: 'ASAP',
    scheduled_time: '',
});
const submitting = ref(false);
const error = ref(null);

async function start() {
    submitting.value = true;
    error.value = null;

    try {
        // datetime-local gives a timezone-naive wall-clock string; convert it
        // to a real instant so the backend (UTC) validates and stores the
        // moment the user actually picked.
        const scheduledInstant =
            form.value.delivery_time_type === 'SCHEDULED' && form.value.scheduled_time
                ? new Date(form.value.scheduled_time).toISOString()
                : null;

        const created = await api.createGroupOrder({
            restaurant_id: props.restaurantId,
            delivery_address_id: form.value.delivery_address_id,
            delivery_time_type: form.value.delivery_time_type,
            scheduled_time: scheduledInstant,
        });

        router.visit(`/group-orders/${created.group_order_id}/lobby`);
    } catch (e) {
        error.value = e.message;
        submitting.value = false;
    }
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 p-6" @click.self="emit('close')">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <h2 class="text-xl font-semibold text-stone-900">Start a Group Order</h2>
            <p class="mt-1 text-sm text-stone-500">
                You'll be the group leader — you set the address, apply promos, and pay for everyone.
            </p>

            <form class="mt-6 flex flex-col gap-4" @submit.prevent="start">
                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium text-stone-700">Delivery address</span>
                    <select
                        v-model="form.delivery_address_id"
                        class="rounded-xl border border-stone-200 px-4 py-2.5 text-stone-900 outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-100"
                    >
                        <option v-for="address in addresses" :key="address.id" :value="address.id">
                            {{ address.name }} — {{ address.details }}
                        </option>
                    </select>
                </label>

                <fieldset class="flex flex-col gap-1.5">
                    <legend class="text-sm font-medium text-stone-700">Delivery time</legend>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            v-for="type in ['ASAP', 'SCHEDULED']"
                            :key="type"
                            type="button"
                            class="rounded-xl border px-4 py-2.5 text-sm font-medium transition"
                            :class="
                                form.delivery_time_type === type
                                    ? 'border-amber-400 bg-amber-50 text-amber-700'
                                    : 'border-stone-200 text-stone-600 hover:border-stone-300'
                            "
                            @click="form.delivery_time_type = type"
                        >
                            {{ type === 'ASAP' ? 'ASAP' : 'Schedule' }}
                        </button>
                    </div>
                </fieldset>

                <label v-if="form.delivery_time_type === 'SCHEDULED'" class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium text-stone-700">Scheduled for</span>
                    <input
                        v-model="form.scheduled_time"
                        type="datetime-local"
                        required
                        class="rounded-xl border border-stone-200 px-4 py-2.5 text-stone-900 outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-100"
                    />
                </label>

                <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

                <div class="mt-2 flex gap-3">
                    <button
                        type="button"
                        class="flex-1 rounded-xl border border-stone-200 px-4 py-3 font-medium text-stone-600 transition hover:bg-stone-50"
                        @click="emit('close')"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="flex-1 rounded-xl bg-amber-500 px-4 py-3 font-medium text-white shadow-lg shadow-amber-500/25 transition hover:bg-amber-600 disabled:opacity-50"
                    >
                        {{ submitting ? 'Creating…' : 'Create group' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
