<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import DemoLayout from '../../Layouts/DemoLayout.vue';
import CountdownTimer from '../../Components/CountdownTimer.vue';
import DishCustomizerModal from '../../Components/DishCustomizerModal.vue';
import SubCartPanel from '../../Components/SubCartPanel.vue';
import { useApi } from '../../api';

// Catalogue comes from the DB as props; group state lives on the API.
const props = defineProps({
    groupOrderId: { type: Number, required: true },
    restaurant: { type: Object, required: true },
    dishes: { type: Array, required: true },
});

const api = useApi();

const groupOrder = ref(null);
const error = ref(null);
const customizing = ref(null); // the dish currently in the modal
const editingItem = ref(null); // the cart line being edited (US-005), null = adding

const isActive = computed(() => groupOrder.value?.status === 'ACTIVE');
const cart = computed(() => groupOrder.value?.my_cart ?? null);

// Same stopgap as the lobby: refresh keeps status/cart current until
// broadcasting lands.
const POLL_MS = 5000;
let pollTimer = null;

async function refresh() {
    try {
        groupOrder.value = await api.getGroupOrder(props.groupOrderId);
        error.value = null;

        if (groupOrder.value.status !== 'ACTIVE') {
            clearInterval(pollTimer);
        }
    } catch (e) {
        const transient = e.status === 0 || e.status >= 500;

        if (!transient || !groupOrder.value) {
            error.value = e.message;
            clearInterval(pollTimer);
        }
    }
}

onMounted(async () => {
    await refresh();
    pollTimer = setInterval(refresh, POLL_MS);
});

onBeforeUnmount(() => clearInterval(pollTimer));

async function onSaved() {
    closeModal();
    await refresh();
}

function closeModal() {
    customizing.value = null;
    editingItem.value = null;
}

// US-005 AC1: reopen the customizer prefilled with the line being edited.
function editItem(item) {
    const dish = props.dishes.find((d) => d.id === item.dish_id);

    if (!dish) {
        return;
    }

    editingItem.value = item;
    customizing.value = dish;
}

// US-005 AC3: confirmed removal, then the subtotal refreshes.
async function removeItem(item) {
    if (!window.confirm(`Remove ${item.dish_name} from your cart?`)) {
        return;
    }

    try {
        await api.removeCartItem(props.groupOrderId, item.id);
    } catch (e) {
        window.alert(e.message);
    }

    await refresh();
}

function formatPrice(price) {
    return Number(price).toFixed(2);
}
</script>

<template>
    <Head :title="`Menu — ${restaurant.name}`" />
    <DemoLayout>
        <section v-if="error" class="mx-auto max-w-xl px-6 pt-24 text-center">
            <h1 class="text-2xl font-semibold text-stone-900">{{ error }}</h1>
            <Link href="/" class="mt-6 inline-block rounded-xl bg-amber-500 px-6 py-3 font-medium text-white">
                Back to home
            </Link>
        </section>

        <section v-else-if="groupOrder" class="mx-auto max-w-5xl px-6 pt-10 pb-24">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <Link
                        :href="`/group-orders/${groupOrderId}/lobby`"
                        class="inline-flex items-center gap-1 text-sm font-medium text-stone-500 transition hover:text-amber-600"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                        </svg>
                        Back to the lobby
                    </Link>
                    <h1 class="mt-2 text-4xl font-semibold tracking-tight text-stone-900">{{ restaurant.name }}</h1>
                    <p class="mt-1 text-stone-500">{{ restaurant.tagline }}</p>
                </div>
                <CountdownTimer v-if="isActive" :expires-at="groupOrder.expires_at" @expired="refresh" />
            </div>

            <!-- FR-016 / US-005 AC4: editing is disabled outside ACTIVE. -->
            <div
                v-if="!isActive"
                class="mt-8 rounded-2xl border border-stone-200 bg-stone-50 p-6 text-center text-stone-600"
            >
                {{
                    groupOrder.status === 'SUBMITTED'
                        ? 'Order has been submitted and cannot be modified.'
                        : 'This group order is no longer active.'
                }}
            </div>

            <div v-else class="mt-8 grid gap-6 lg:grid-cols-[1fr_20rem]">
                <ul class="grid content-start gap-3 sm:grid-cols-2">
                    <li
                        v-for="dish in dishes"
                        :key="dish.id"
                        class="flex flex-col justify-between gap-3 rounded-2xl border border-stone-100 bg-white p-5 shadow-sm"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-stone-900">{{ dish.eng_name }}</p>
                                <p class="text-sm text-stone-400">{{ dish.name }}</p>
                            </div>
                            <span class="shrink-0 font-semibold text-amber-600">{{ formatPrice(dish.price) }}</span>
                        </div>
                        <!-- US-004 AC1 / spec §10.2: "Add to My Cart" in group context. -->
                        <button
                            type="button"
                            class="w-fit rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-1.5 text-sm font-medium text-amber-700 transition hover:bg-amber-100"
                            @click="customizing = dish"
                        >
                            Add to my cart
                        </button>
                    </li>
                </ul>

                <SubCartPanel
                    :cart="cart"
                    editable
                    class="lg:sticky lg:top-6 lg:self-start"
                    @edit="editItem"
                    @remove="removeItem"
                />
            </div>
        </section>

        <DishCustomizerModal
            v-if="customizing"
            :group-order-id="groupOrderId"
            :dish="customizing"
            :existing-item="editingItem"
            @close="closeModal"
            @saved="onSaved"
        />
    </DemoLayout>
</template>
