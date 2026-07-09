<script setup>
import { ref } from 'vue';

const props = defineProps({
    url: { type: String, required: true },
});

const copied = ref(false);

async function copy() {
    await navigator.clipboard.writeText(props.url);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
}

const shareText = 'Join my Beeorder group order!';
const whatsappHref = () => `https://wa.me/?text=${encodeURIComponent(`${shareText} ${props.url}`)}`;
const smsHref = () => `sms:?body=${encodeURIComponent(`${shareText} ${props.url}`)}`;
</script>

<template>
    <div class="flex flex-col gap-3">
        <div class="flex items-stretch gap-2">
            <input
                :value="url"
                readonly
                class="min-w-0 flex-1 truncate rounded-xl border border-stone-200 bg-stone-50 px-4 py-2.5 font-mono text-sm text-stone-600"
                @focus="$event.target.select()"
            />
            <button
                type="button"
                class="shrink-0 rounded-xl px-4 py-2.5 text-sm font-medium transition"
                :class="
                    copied
                        ? 'bg-green-100 text-green-700'
                        : 'bg-amber-500 text-white shadow-lg shadow-amber-500/25 hover:bg-amber-600'
                "
                @click="copy"
            >
                {{ copied ? 'Copied!' : 'Copy link' }}
            </button>
        </div>
        <div class="flex items-center gap-3 text-sm">
            <span class="text-stone-400">Share via</span>
            <a
                :href="whatsappHref()"
                target="_blank"
                rel="noopener"
                class="rounded-lg border border-stone-200 px-3 py-1.5 font-medium text-stone-600 transition hover:border-green-300 hover:bg-green-50 hover:text-green-700"
            >
                WhatsApp
            </a>
            <a
                :href="smsHref()"
                class="rounded-lg border border-stone-200 px-3 py-1.5 font-medium text-stone-600 transition hover:border-stone-300 hover:bg-stone-50"
            >
                SMS
            </a>
        </div>
    </div>
</template>
