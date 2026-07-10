<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /api/v1/group-orders/{id}/join (spec §8.2). The token must accompany
 * the request — possessing the link is the invitation (NFR-006).
 */
class JoinGroupOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authentication is enforced by the auth.api-token middleware
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'link_token' => ['required', 'string', 'regex:/^[a-f0-9]{32}$/'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'link_token.regex' => 'This group order link is not valid.',
        ];
    }
}
