<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import DemoLayout from '../../Layouts/DemoLayout.vue';

const props = defineProps({
    demoUsers: { type: Array, default: () => [] },
});

const form = useForm({
    email: '',
    password: '',
});

function submit() {
    form.post('/login');
}

function quickLogin(user) {
    form.email = user.email;
    form.password = 'password';
    form.post('/login');
}
</script>

<template>
    <Head title="Log in" />
    <DemoLayout>
        <section class="mx-auto flex max-w-md flex-col gap-8 px-6 pt-20">
            <div class="text-center">
                <h1 class="text-3xl font-semibold tracking-tight text-stone-900">Welcome back</h1>
                <p class="mt-2 text-stone-500">Log in to start or join a group order.</p>
            </div>

            <form
                class="flex flex-col gap-4 rounded-2xl border border-stone-100 bg-white p-6 shadow-xl shadow-stone-900/5"
                @submit.prevent="submit"
            >
                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium text-stone-700">Email</span>
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="email"
                        class="rounded-xl border border-stone-200 px-4 py-2.5 text-stone-900 outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-100"
                    />
                </label>

                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium text-stone-700">Password</span>
                    <input
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="rounded-xl border border-stone-200 px-4 py-2.5 text-stone-900 outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-100"
                    />
                </label>

                <p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="mt-2 rounded-xl bg-amber-500 px-6 py-3 font-medium text-white shadow-lg shadow-amber-500/25 transition hover:bg-amber-600 disabled:opacity-50"
                >
                    Log in
                </button>
            </form>

            <div v-if="demoUsers.length" class="flex flex-col gap-3">
                <div class="flex items-center gap-3 text-xs font-medium tracking-wide text-stone-400 uppercase">
                    <span class="h-px flex-1 bg-stone-200" />
                    Demo accounts
                    <span class="h-px flex-1 bg-stone-200" />
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        v-for="user in demoUsers"
                        :key="user.id"
                        type="button"
                        :disabled="form.processing"
                        class="rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-left transition hover:border-amber-300 hover:bg-amber-50 disabled:opacity-50"
                        @click="quickLogin(user)"
                    >
                        <span class="block text-sm font-medium text-stone-800">{{ user.name }}</span>
                        <span class="block truncate text-xs text-stone-400">{{ user.email }}</span>
                    </button>
                </div>
            </div>
        </section>
    </DemoLayout>
</template>
