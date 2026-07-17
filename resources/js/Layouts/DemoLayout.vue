<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import NotificationBell from '../Components/NotificationBell.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

function logout() {
    router.post('/logout');
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-b from-amber-50/40 to-white text-stone-900">
        <header class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
            <Link href="/" class="flex items-center gap-2 text-lg font-semibold">
                <span class="flex size-8 items-center justify-center rounded-lg bg-amber-500 text-white">B</span>
                Beeorder
            </Link>
            <nav class="flex items-center gap-4 text-sm font-medium text-stone-500">
                <slot name="nav" />
                <NotificationBell />
                <template v-if="user">
                    <span class="hidden items-center gap-2 sm:flex">
                        <span
                            class="flex size-7 items-center justify-center rounded-full bg-amber-100 text-xs font-semibold text-amber-700"
                        >
                            {{ user.name?.charAt(0) ?? '?' }}
                        </span>
                        {{ user.name }}
                    </span>
                    <button
                        type="button"
                        class="rounded-lg border border-stone-200 px-3 py-1.5 transition hover:border-stone-300 hover:bg-stone-50"
                        @click="logout"
                    >
                        Log out
                    </button>
                </template>
                <Link
                    v-else
                    href="/login"
                    class="rounded-lg border border-stone-200 px-3 py-1.5 transition hover:border-stone-300 hover:bg-stone-50"
                >
                    Log in
                </Link>
            </nav>
        </header>
        <main>
            <slot />
        </main>
    </div>
</template>
