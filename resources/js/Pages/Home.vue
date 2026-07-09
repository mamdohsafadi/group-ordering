<script setup>
import { Head } from '@inertiajs/vue3';
import DemoLayout from '../Layouts/DemoLayout.vue';

defineProps({
    apiVersion: { type: String, default: 'v1' },
});
</script>

<template>
    <Head title="Group Ordering" />
    <DemoLayout>
        <section class="mx-auto flex max-w-3xl flex-col items-center gap-6 px-6 pt-24 text-center">
            <span
                class="rounded-full border border-amber-200 bg-amber-50 px-4 py-1 text-sm font-medium text-amber-700"
            >
                Beeorder · Group Ordering Demo
            </span>
            <h1 class="text-5xl font-semibold tracking-tight text-stone-900">
                Order together.<br />
                <span class="text-amber-500">Pay once.</span>
            </h1>
            <p class="max-w-xl text-lg text-stone-500">
                Start a group order, share the link, and let everyone build their own
                sub-cart — one unified order, individual invoices, zero back-and-forth.
            </p>
            <div class="mt-4 flex gap-4">
                <a
                    href="#"
                    class="rounded-xl bg-amber-500 px-6 py-3 font-medium text-white shadow-lg shadow-amber-500/25 transition hover:bg-amber-600"
                >
                    Start a Group Order
                </a>
                <a
                    :href="`/api/${apiVersion}/ping`"
                    class="rounded-xl border border-stone-200 px-6 py-3 font-medium text-stone-600 transition hover:border-stone-300 hover:bg-stone-50"
                >
                    API status
                </a>
            </div>
        </section>
    </DemoLayout>
</template>
