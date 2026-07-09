<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-gradient-to-b from-amber-50/40 to-white text-stone-900">
        <header class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
            <Link href="/" class="flex items-center gap-2 text-lg font-semibold">
                <span class="flex size-8 items-center justify-center rounded-lg bg-amber-500 text-white">B</span>
                Beeorder
            </Link>
            <nav class="flex items-center gap-6 text-sm font-medium text-stone-500">
                <slot name="nav" />
            </nav>
        </header>
        <main>
            <slot />
        </main>
    </div>
</template>
