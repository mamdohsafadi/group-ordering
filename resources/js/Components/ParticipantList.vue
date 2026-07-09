<script setup>
defineProps({
    participants: { type: Array, required: true },
});

const palette = ['bg-amber-100 text-amber-700', 'bg-sky-100 text-sky-700', 'bg-violet-100 text-violet-700', 'bg-rose-100 text-rose-700', 'bg-emerald-100 text-emerald-700'];

function color(participant) {
    return palette[participant.user_id % palette.length];
}
</script>

<template>
    <ul class="flex flex-col gap-2">
        <li
            v-for="participant in participants.filter((p) => p.status === 'JOINED')"
            :key="participant.id"
            class="flex items-center gap-3 rounded-xl border border-stone-100 bg-white px-4 py-3"
        >
            <span
                class="flex size-9 items-center justify-center rounded-full text-sm font-semibold"
                :class="color(participant)"
            >
                {{ participant.name?.charAt(0) ?? '?' }}
            </span>
            <span class="font-medium text-stone-800">{{ participant.name }}</span>
            <span
                v-if="participant.is_leader"
                class="ml-auto rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700"
            >
                Leader
            </span>
        </li>
    </ul>
</template>
