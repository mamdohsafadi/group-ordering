<?php

namespace App\Http\Requests;

use App\Models\GroupOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /api/v1/group-orders (spec §8.1). The delivery address must belong to
 * the requesting user — leaders can only ship to their own saved addresses.
 */
class StoreGroupOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authentication is enforced by the auth.api-token middleware
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'restaurant_id' => [
                'required',
                'integer',
                Rule::exists('restaurant', 'id')->where('active', 1),
            ],
            'delivery_address_id' => [
                'required',
                'integer',
                Rule::exists('user_address', 'id')->where('user_id', $this->user()->id),
            ],
            'delivery_time_type' => [
                'required',
                Rule::in([GroupOrder::DELIVERY_ASAP, GroupOrder::DELIVERY_SCHEDULED]),
            ],
            'scheduled_time' => [
                'required_if:delivery_time_type,'.GroupOrder::DELIVERY_SCHEDULED,
                'nullable',
                'date',
                'after:now',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'restaurant_id.exists' => 'This restaurant is currently unavailable. Please try another restaurant.',
            'delivery_address_id.exists' => 'This delivery address is not one of your saved addresses.',
            'scheduled_time.after' => 'The scheduled delivery time must be in the future.',
        ];
    }
}
