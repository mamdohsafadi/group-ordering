<script setup>
import { computed, ref } from 'vue';
import { useApi } from '../api';

const props = defineProps({
    groupOrderId: { type: Number, required: true },
    // { id, name, eng_name, price, option_groups: [{ id, name, options: [{id, name, price}] }] }
    dish: { type: Object, required: true },
    // US-005: pass an existing cart line to edit it instead of adding.
    existingItem: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);

const api = useApi();
const quantity = ref(props.existingItem?.quantity ?? 1);
const selected = ref(new Set((props.existingItem?.modifiers ?? []).map((m) => m.id)));
const instructions = ref(props.existingItem?.special_instructions ?? '');
const submitting = ref(false);
const error = ref(null);

const optionsById = new Map(
    props.dish.option_groups.flatMap((group) => group.options.map((option) => [option.id, option])),
);

const unitPrice = computed(
    () => props.dish.price + [...selected.value].reduce((sum, id) => sum + (optionsById.get(id)?.price ?? 0), 0),
);
const totalPrice = computed(() => (unitPrice.value * quantity.value).toFixed(2));

function toggle(optionId) {
    const next = new Set(selected.value);

    if (next.has(optionId)) {
        next.delete(optionId);
    } else {
        next.add(optionId);
    }

    selected.value = next;
}

async function save() {
    submitting.value = true;
    error.value = null;

    try {
        if (props.existingItem) {
            // US-005 AC1: version travels with the edit (NFR-008).
            await api.updateCartItem(props.groupOrderId, props.existingItem.id, {
                quantity: quantity.value,
                version: props.existingItem.version,
                modifiers: [...selected.value],
                special_instructions: instructions.value.trim() || null,
            });
        } else {
            await api.addCartItem(props.groupOrderId, {
                menu_item_id: props.dish.id,
                quantity: quantity.value,
                modifiers: [...selected.value],
                special_instructions: instructions.value.trim() || null,
            });
        }

        emit('saved');
    } catch (e) {
        error.value = e.message;
        submitting.value = false;
    }
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 p-6" @click.self="emit('close')">
        <div class="max-h-[85vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-stone-900">{{ dish.eng_name }}</h2>
                    <p class="text-sm text-stone-400">{{ dish.name }}</p>
                </div>
                <span class="shrink-0 font-semibold text-amber-600">{{ dish.price.toFixed(2) }}</span>
            </div>

            <div v-for="group in dish.option_groups" :key="group.id" class="mt-5">
                <p class="mb-2 text-sm font-medium text-stone-700">{{ group.name }}</p>
                <div class="flex flex-col gap-2">
                    <label
                        v-for="option in group.options"
                        :key="option.id"
                        class="flex cursor-pointer items-center justify-between rounded-xl border px-4 py-2.5 transition"
                        :class="
                            selected.has(option.id)
                                ? 'border-amber-400 bg-amber-50'
                                : 'border-stone-200 hover:border-stone-300'
                        "
                    >
                        <span class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                class="accent-amber-500"
                                :checked="selected.has(option.id)"
                                @change="toggle(option.id)"
                            />
                            <span class="text-sm text-stone-700">{{ option.name }}</span>
                        </span>
                        <span class="text-sm text-stone-400">
                            {{ option.price > 0 ? `+${option.price.toFixed(2)}` : 'Free' }}
                        </span>
                    </label>
                </div>
            </div>

            <label class="mt-5 flex flex-col gap-1.5">
                <span class="text-sm font-medium text-stone-700">Special instructions</span>
                <textarea
                    v-model="instructions"
                    rows="2"
                    maxlength="500"
                    placeholder="e.g. no onions, extra crispy…"
                    class="rounded-xl border border-stone-200 px-4 py-2.5 text-sm text-stone-900 outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-100"
                />
            </label>

            <div class="mt-5 flex items-center justify-between">
                <div class="flex items-center gap-3 rounded-xl border border-stone-200 px-2 py-1.5">
                    <button
                        type="button"
                        class="flex size-7 items-center justify-center rounded-lg text-lg font-medium text-stone-500 transition hover:bg-stone-100 disabled:opacity-40"
                        :disabled="quantity <= 1"
                        @click="quantity--"
                    >
                        −
                    </button>
                    <span class="w-6 text-center font-semibold text-stone-900">{{ quantity }}</span>
                    <button
                        type="button"
                        class="flex size-7 items-center justify-center rounded-lg text-lg font-medium text-stone-500 transition hover:bg-stone-100"
                        :disabled="quantity >= 99"
                        @click="quantity++"
                    >
                        +
                    </button>
                </div>
                <span class="text-lg font-semibold text-stone-900">{{ totalPrice }}</span>
            </div>

            <p v-if="error" class="mt-3 text-sm text-red-600">{{ error }}</p>

            <div class="mt-5 flex gap-3">
                <button
                    type="button"
                    class="flex-1 rounded-xl border border-stone-200 px-4 py-3 font-medium text-stone-600 transition hover:bg-stone-50"
                    @click="emit('close')"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    :disabled="submitting"
                    class="flex-1 rounded-xl bg-amber-500 px-4 py-3 font-medium text-white shadow-lg shadow-amber-500/25 transition hover:bg-amber-600 disabled:opacity-50"
                    @click="save"
                >
                    {{ submitting ? 'Saving…' : existingItem ? 'Save changes' : 'Add to my cart' }}
                </button>
            </div>
        </div>
    </div>
</template>
