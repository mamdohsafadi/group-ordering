<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import DemoLayout from '../../Layouts/DemoLayout.vue';
import InvoiceBreakdown from '../../Components/InvoiceBreakdown.vue';
import { useApi } from '../../api';

// US-007: leader review — all sub-carts, promo, place order (AC1..AC5).
const props = defineProps({
    groupOrderId: { type: Number, required: true },
});

const api = useApi();
const master = ref(null);
const error = ref(null);
const promoCode = ref('');
const promoError = ref(null);
const placing = ref(false);
const confirmation = ref(null);

async function refresh() {
    try {
        master.value = await api.getMasterInvoice(props.groupOrderId);
    } catch (e) {
        error.value = e.message;
    }
}

onMounted(refresh);

async function placeOrder() {
    placing.value = true;
    promoError.value = null;

    try {
        confirmation.value = await api.checkout(props.groupOrderId, {
            promo_code: promoCode.value.trim() || null,
        });
    } catch (e) {
        promoError.value = e.message;
    } finally {
        placing.value = false;
    }
}

function money(value) {
    return Number(value).toFixed(2);
}
</script>

<template>
    <Head title="Review & Checkout" />
    <DemoLayout>
        <section v-if="error" class="mx-auto max-w-xl px-6 pt-24 text-center">
            <h1 class="text-2xl font-semibold text-stone-900">{{ error }}</h1>
            <Link href="/" class="mt-6 inline-block rounded-xl bg-amber-500 px-6 py-3 font-medium text-white">
                Back to home
            </Link>
        </section>

        <!-- US-007 AC5: confirmation once payment (COD) is accepted. -->
        <section v-else-if="confirmation" class="mx-auto max-w-xl px-6 pt-20 text-center">
            <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-green-100 text-green-600">
                <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </span>
            <h1 class="mt-4 text-3xl font-semibold text-stone-900">Order placed!</h1>
            <p class="mt-2 text-stone-500">
                Order #{{ confirmation.order_id }} — {{ money(confirmation.total_charged) }} to pay on delivery.
                Everyone has been notified.
            </p>
            <button
                type="button"
                class="mt-6 rounded-xl bg-amber-500 px-6 py-3 font-medium text-white shadow-lg shadow-amber-500/25"
                @click="router.visit(`/group-orders/${groupOrderId}/lobby`)"
            >
                Back to the lobby
            </button>
        </section>

        <section v-else-if="master" class="mx-auto max-w-2xl px-6 pt-10 pb-24">
            <Link
                :href="`/group-orders/${groupOrderId}/lobby`"
                class="text-sm font-medium text-stone-500 transition hover:text-amber-600"
            >
                ← Back to the lobby
            </Link>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-stone-900">Review & Checkout</h1>
            <p class="mt-1 text-stone-500">
                One unified order, paid by you on delivery — everyone sees their own share (US-006).
            </p>

            <!-- US-006 AC3: master invoice, per participant. -->
            <div
                v-for="participant in master.participants"
                :key="participant.participant_id"
                class="mt-6 rounded-2xl border border-stone-100 bg-white p-6 shadow-sm"
            >
                <h2 class="mb-3 font-semibold text-stone-900">{{ participant.name }}</h2>
                <ul class="mb-4 flex flex-col gap-1 text-sm text-stone-600">
                    <li v-for="item in participant.items" :key="item.id" class="flex justify-between">
                        <span>{{ item.quantity }}× {{ item.dish_name }}</span>
                        <span>{{ money(item.total_price) }}</span>
                    </li>
                    <li v-if="!participant.items.length" class="text-stone-400">No items</li>
                </ul>
                <InvoiceBreakdown :invoice="participant" />
            </div>

            <!-- US-007 AC2/AC3: totals + promo. -->
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-6">
                <div class="flex flex-col gap-1.5 text-sm text-stone-600">
                    <div class="flex justify-between"><span>Items total</span><span>{{ money(master.grand_total) }}</span></div>
                    <div class="flex justify-between"><span>Delivery fee</span><span>{{ money(master.delivery_fee) }}</span></div>
                    <div class="flex justify-between"><span>Tax</span><span>{{ money(master.tax) }}</span></div>
                    <div class="mt-1 flex justify-between border-t border-amber-200 pt-2 text-lg font-semibold text-stone-900">
                        <span>Grand total</span><span>{{ money(master.total) }}</span>
                    </div>
                </div>

                <div class="mt-4 flex gap-2">
                    <input
                        v-model="promoCode"
                        placeholder="Promo code (optional)"
                        class="min-w-0 flex-1 rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-amber-400"
                    />
                    <button
                        type="button"
                        :disabled="placing"
                        class="shrink-0 rounded-xl bg-amber-500 px-6 py-2.5 font-medium text-white shadow-lg shadow-amber-500/25 transition hover:bg-amber-600 disabled:opacity-50"
                        @click="placeOrder"
                    >
                        {{ placing ? 'Placing…' : 'Place Order' }}
                    </button>
                </div>
                <p v-if="promoError" class="mt-2 text-sm text-red-600">{{ promoError }}</p>
                <p class="mt-3 text-xs text-stone-400">
                    Cash on delivery — the discount from a promo code is split equally across everyone.
                </p>
            </div>
        </section>
    </DemoLayout>
</template>
