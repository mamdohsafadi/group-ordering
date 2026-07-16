<script setup>
// Shares block reused by the personal invoice and the master view (US-006).
defineProps({
    invoice: { type: Object, required: true },
});

function money(value) {
    return Number(value).toFixed(2);
}
</script>

<template>
    <dl class="flex flex-col gap-1.5 text-sm">
        <div class="flex justify-between text-stone-500">
            <dt>Subtotal</dt>
            <dd>{{ money(invoice.subtotal) }}</dd>
        </div>
        <div class="flex justify-between text-stone-500">
            <dt>Delivery share</dt>
            <dd>{{ money(invoice.delivery_fee_share) }}</dd>
        </div>
        <div class="flex justify-between text-stone-500">
            <dt>Tax share</dt>
            <dd>{{ money(invoice.tax_share) }}</dd>
        </div>
        <div v-if="Number(invoice.discount_share) !== 0" class="flex justify-between text-green-600">
            <dt>Discount share</dt>
            <dd>{{ money(invoice.discount_share) }}</dd>
        </div>
        <div class="mt-1 flex justify-between border-t border-stone-200 pt-2 font-semibold text-stone-900">
            <dt>Total</dt>
            <dd>{{ money(invoice.total) }}</dd>
        </div>
    </dl>
</template>
