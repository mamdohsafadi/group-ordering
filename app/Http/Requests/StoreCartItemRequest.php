<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /group-orders/{id}/cart/items (spec §8.3). The spec calls the dish
 * `menu_item_id`; it maps to `dish_id` internally.
 */
class StoreCartItemRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'menu_item_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'modifiers' => ['sometimes', 'array', 'max:20'],
            'modifiers.*' => ['integer'],
            'special_instructions' => ['nullable', 'string', 'max:500'],
        ];
    }
}
