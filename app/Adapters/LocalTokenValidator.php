<?php

namespace App\Adapters;

use App\Contracts\TokenValidator;
use App\Models\User;

/**
 * DEMO ONLY — the bearer token is simply the user's id (e.g. "Bearer 42").
 * Deliberately naive: at adoption this is replaced by a validator for JWTs
 * issued by the live BeeOrder backend. Nothing outside this class may assume
 * the token format.
 */
class LocalTokenValidator implements TokenValidator
{
    public function validate(string $token): ?User
    {
        if (! ctype_digit($token)) {
            return null;
        }

        return User::find((int) $token);
    }
}
