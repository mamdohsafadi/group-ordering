<script setup>
defineProps({
    // { items: [...], subtotal } from the lobby payload's my_cart.
    cart: { type: Object, default: null },
});

function formatPrice(price) {
    return Number(price).toFixed(2);
}
</script>

<template>
    <div class="rounded-2xl border border-stone-100 bg-white p-6 shadow-sm">
        <h2 class="mb-4 font-semibold text-stone-900">
            My sub-cart
            <span v-if="cart?.items?.length" class="ml-1 text-sm font-normal text-stone-400">
                {{ cart.items.length }}
            </span>
        </h2>

        <p v-if="!cart?.items?.length" class="text-sm text-stone-400">
            Nothing yet — add something from the menu.
        </p>

        <ul v-else class="flex flex-col gap-3">
            <li
                v-for="item in cart.items"
                :key="item.id"
                class="flex items-start justify-between gap-3 border-b border-stone-100 pb-3 last:border-b-0 last:pb-0"
            >
                <div class="min-w-0">
                    <p class="font-medium text-stone-800">
                        <span class="text-amber-600">{{ item.quantity }}×</span>
                        {{ item.dish_name }}
                    </p>
                    <p v-if="item.modifiers?.length" class="mt-0.5 truncate text-xs text-stone-400">
                        {{ item.modifiers.map((m) => m.name).join(', ') }}
                    </p>
                    <p v-if="item.special_instructions" class="mt-0.5 truncate text-xs text-stone-400 italic">
                        “{{ item.special_instructions }}”
                    </p>
                    <slot name="item-actions" :item="item" />
                </div>
                <span class="shrink-0 text-sm font-semibold text-stone-700">
                    {{ formatPrice(item.total_price) }}
                </span>
            </li>
        </ul>

        <div
            v-if="cart?.items?.length"
            class="mt-4 flex items-center justify-between border-t border-stone-200 pt-4"
        >
            <span class="text-sm font-medium text-stone-500">Subtotal</span>
            <span class="text-lg font-semibold text-stone-900">{{ formatPrice(cart.subtotal) }}</span>
        </div>
    </div>
</template>
