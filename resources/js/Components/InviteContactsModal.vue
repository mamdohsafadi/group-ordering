<script setup>
import { ref } from 'vue';
import { useApi } from '../api';

// US-003 AC1: the leader picks registered users to invite in-app.
const props = defineProps({
    groupOrderId: { type: Number, required: true },
    contacts: { type: Array, required: true },
});

const emit = defineEmits(['close']);

const api = useApi();
const selected = ref(new Set());
const sending = ref(false);
const error = ref(null);
const sent = ref(null);

function toggle(id) {
    const next = new Set(selected.value);
    next.has(id) ? next.delete(id) : next.add(id);
    selected.value = next;
}

async function send() {
    sending.value = true;
    error.value = null;

    try {
        const result = await api.inviteToGroupOrder(props.groupOrderId, [...selected.value]);
        sent.value = result.invited;
    } catch (e) {
        error.value = e.message;
        sending.value = false;
    }
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 p-6" @click.self="emit('close')">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <template v-if="sent !== null">
                <h2 class="text-xl font-semibold text-stone-900">Invitations sent 📨</h2>
                <p class="mt-2 text-sm text-stone-500">
                    {{ sent }} {{ sent === 1 ? 'person' : 'people' }} got an in-app invitation with the join link.
                </p>
                <button
                    type="button"
                    class="mt-5 w-full rounded-xl bg-amber-500 px-4 py-3 font-medium text-white"
                    @click="emit('close')"
                >
                    Done
                </button>
            </template>

            <template v-else>
                <h2 class="text-xl font-semibold text-stone-900">Invite from contacts</h2>
                <p class="mt-1 text-sm text-stone-500">They'll get an in-app notification with the join link.</p>

                <div class="mt-4 flex max-h-72 flex-col gap-2 overflow-y-auto">
                    <label
                        v-for="contact in contacts"
                        :key="contact.id"
                        class="flex cursor-pointer items-center gap-3 rounded-xl border px-4 py-2.5 transition"
                        :class="selected.has(contact.id) ? 'border-amber-400 bg-amber-50' : 'border-stone-200 hover:border-stone-300'"
                    >
                        <input
                            type="checkbox"
                            class="accent-amber-500"
                            :checked="selected.has(contact.id)"
                            @change="toggle(contact.id)"
                        />
                        <span>
                            <span class="block text-sm font-medium text-stone-800">{{ contact.name }}</span>
                            <span class="block text-xs text-stone-400">{{ contact.email }}</span>
                        </span>
                    </label>
                </div>

                <p v-if="error" class="mt-3 text-sm text-red-600">{{ error }}</p>

                <div class="mt-5 flex gap-3">
                    <button
                        type="button"
                        class="flex-1 rounded-xl border border-stone-200 px-4 py-3 font-medium text-stone-600 transition hover:bg-stone-50"
                        @click="emit('close')"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="sending || selected.size === 0"
                        class="flex-1 rounded-xl bg-amber-500 px-4 py-3 font-medium text-white shadow-lg shadow-amber-500/25 transition hover:bg-amber-600 disabled:opacity-50"
                        @click="send"
                    >
                        {{ sending ? 'Sending…' : `Invite (${selected.size})` }}
                    </button>
                </div>
            </template>
        </div>
    </div>
</template>
