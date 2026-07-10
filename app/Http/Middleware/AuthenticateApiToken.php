<?php

namespace App\Http\Middleware;

use App\Contracts\TokenValidator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates /api/v1 requests via the TokenValidator contract (NFR-005).
 * API routes stay stateless: authentication comes exclusively from the
 * Authorization: Bearer header — never from the session — so the service
 * deploys next to the live app unchanged.
 */
class AuthenticateApiToken
{
    public function __construct(
        private readonly TokenValidator $tokens,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        $user = $token !== null ? $this->tokens->validate($token) : null;

        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
