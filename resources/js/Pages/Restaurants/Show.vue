<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import DemoLayout from '../../Layouts/DemoLayout.vue';
import StartGroupOrderModal from '../../Components/StartGroupOrderModal.vue';

// Menu and address data comes from the DB via Inertia props (handoff §3);
// group-order state stays on the /api/v1 client.
const props = defineProps({
    restaurant: { type: Object, required: true },
    dishes: { type: Array, required: true },
    addresses: { type: Array, default: () => [] },
});

const restaurant = props.restaurant;
const menu = props.dishes;
const startingGroup = ref(false);

function formatPrice(price) {
    return price.toFixed(2);
}
</script>

<template>
    <Head :title="restaurant?.name ?? 'Restaurant'" />
    <DemoLayout>
        <section v-if="restaurant" class="mx-auto max-w-4xl px-6 pt-10 pb-24">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-4xl font-semibold tracking-tight text-stone-900">{{ restaurant.name }}</h1>
                    <p class="mt-1 text-stone-500">{{ restaurant.tagline }}</p>
                </div>
                <!-- US-001 AC1: group order starts from the restaurant menu screen. -->
                <button
                    type="button"
                    class="flex items-center gap-2 rounded-xl bg-amber-500 px-5 py-3 font-medium text-white shadow-lg shadow-amber-500/25 transition hover:bg-amber-600"
                    @click="startingGroup = true"
                >
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"
                        />
                    </svg>
                    Start Group Order
                </button>
            </div>

            <ul class="mt-10 grid gap-3 sm:grid-cols-2">
                <li
                    v-for="dish in menu"
                    :key="dish.id"
                    class="flex items-center justify-between gap-4 rounded-2xl border border-stone-100 bg-white p-5 shadow-sm"
                >
                    <div>
                        <p class="font-medium text-stone-900">{{ dish.eng_name }}</p>
                        <p class="text-sm text-stone-400">{{ dish.name }}</p>
                    </div>
                    <span class="shrink-0 font-semibold text-amber-600">{{ formatPrice(dish.price) }}</span>
                </li>
            </ul>
        </section>

        <StartGroupOrderModal
            v-if="startingGroup && restaurant"
            :restaurant-id="restaurant.id"
            :addresses="addresses"
            @close="startingGroup = false"
        />
    </DemoLayout>
</template>
