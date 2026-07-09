<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Session login for the demo frontend only. The /api/v1 surface stays
 * bearer-token based; production identity is owned by the live app.
 */
class AuthController extends Controller
{
    /** Show the login screen with one-click demo accounts. */
    public function show(): Response
    {
        return Inertia::render('Auth/Login', [
            'demoUsers' => app()->isLocal()
                ? User::query()->orderBy('id')->limit(8)->get(['id', 'name', 'email'])
                : [],
        ]);
    }

    /** Authenticate and start a session. */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, remember: true)) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    /** End the session. */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
