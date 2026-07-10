<?php

namespace App\Contracts;

use App\Models\User;

/**
 * Resolves a bearer token to a user. This service never owns identity
 * (NFR-005): in production this validates JWTs issued by the live BeeOrder
 * backend; the local/demo implementation is a deliberately naive stand-in.
 */
interface TokenValidator
{
    /** Returns the authenticated user, or null when the token is invalid. */
    public function validate(string $token): ?User;
}
