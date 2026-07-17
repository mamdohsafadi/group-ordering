<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * PUT /group-orders/{id}/cart/items/{item} (spec §8.4). `version` is the
 * optimistic-locking token (NFR-008); quantity 0 removes the line (US-005 AC2).
 */
class UpdateCartItemRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
            'version' => ['required', 'integer', 'min:1'],
            'modifiers' => ['sometimes', 'array', 'max:20'],
            'modifiers.*' => ['integer'],
            'special_instructions' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
