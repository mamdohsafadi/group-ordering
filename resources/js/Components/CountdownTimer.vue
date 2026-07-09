<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    expiresAt: { type: String, required: true },
});

const emit = defineEmits(['expired']);

const remainingMs = ref(remaining());
let interval = null;

function remaining() {
    return Math.max(0, Date.parse(props.expiresAt) - Date.now());
}

onMounted(() => {
    interval = setInterval(() => {
        remainingMs.value = remaining();

        if (remainingMs.value === 0) {
            clearInterval(interval);
            emit('expired');
        }
    }, 1000);
});

onBeforeUnmount(() => clearInterval(interval));

const minutes = computed(() => String(Math.floor(remainingMs.value / 60000)).padStart(2, '0'));
const seconds = computed(() => String(Math.floor((remainingMs.value % 60000) / 1000)).padStart(2, '0'));
const urgent = computed(() => remainingMs.value > 0 && remainingMs.value < 60000);
const expired = computed(() => remainingMs.value === 0);
</script>

<template>
    <div
        class="flex items-center gap-2 rounded-xl border px-4 py-2 font-mono text-2xl font-semibold tabular-nums transition-colors"
        :class="
            expired
                ? 'border-stone-200 bg-stone-100 text-stone-400'
                : urgent
                  ? 'border-red-200 bg-red-50 text-red-600'
                  : 'border-amber-200 bg-amber-50 text-amber-700'
        "
    >
        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        {{ minutes }}:{{ seconds }}
    </div>
</template>
