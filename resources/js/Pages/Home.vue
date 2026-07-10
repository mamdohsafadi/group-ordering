<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DemoLayout from '../Layouts/DemoLayout.vue';

// Live restaurant list from the DB (handoff §3) — replaces the old
// hardcoded /restaurants/1 shortcut.
defineProps({
    restaurants: { type: Array, default: () => [] },
});

const page = usePage();
const loggedIn = computed(() => Boolean(page.props.auth?.user));
</script>

<template>
    <Head title="Group Ordering" />
    <DemoLayout>
        <section class="mx-auto flex max-w-3xl flex-col items-center gap-6 px-6 pt-20 text-center">
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
            <Link
                v-if="!loggedIn"
                href="/login"
                class="mt-2 rounded-xl bg-amber-500 px-6 py-3 font-medium text-white shadow-lg shadow-amber-500/25 transition hover:bg-amber-600"
            >
                Log in to start
            </Link>
        </section>

        <section class="mx-auto max-w-4xl px-6 pt-14 pb-24">
            <h2 class="mb-4 text-lg font-semibold text-stone-900">Pick a restaurant</h2>
            <ul class="grid gap-3 sm:grid-cols-2">
                <li v-for="restaurant in restaurants" :key="restaurant.id">
                    <Link
                        :href="`/restaurants/${restaurant.id}`"
                        class="flex items-center justify-between gap-4 rounded-2xl border border-stone-100 bg-white p-5 shadow-sm transition hover:border-amber-200 hover:shadow-md"
                    >
                        <div>
                            <p class="font-medium text-stone-900">{{ restaurant.name }}</p>
                            <p class="text-sm text-stone-400">{{ restaurant.tagline }}</p>
                        </div>
                        <span class="shrink-0 text-sm text-stone-400" dir="rtl">{{ restaurant.arabic_name }}</span>
                    </Link>
                </li>
            </ul>
            <p v-if="restaurants.length === 0" class="text-stone-400">
                No restaurants yet — run <code class="rounded bg-stone-100 px-1.5 py-0.5">php artisan db:seed</code>.
            </p>
        </section>
    </DemoLayout>
</template>
