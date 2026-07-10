<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Demo screens only (handoff §3): in production the live app owns menu and
 * address data — these props replace the old frontend fixtures.
 */
class RestaurantController extends Controller
{
    /** Landing page with the full restaurant list to browse. */
    public function index(): Response
    {
        return Inertia::render('Home', [
            'restaurants' => Restaurant::query()
                ->where('active', 1)
                ->orderBy('id')
                ->get(['id', 'name', 'arabic_name', 'tagline']),
        ]);
    }

    /** Menu screen; the start-order modal needs the user's saved addresses. */
    public function show(Request $request, int $restaurant): Response
    {
        $model = Restaurant::query()
            ->where('active', 1)
            ->findOrFail($restaurant);

        return Inertia::render('Restaurants/Show', [
            'restaurant' => $model->only('id', 'name', 'arabic_name', 'tagline'),
            'dishes' => $model->dishes()
                ->where('active', 1)
                ->orderBy('id')
                ->get(['id', 'restaurant_id', 'name', 'eng_name', 'price', 'active']),
            'addresses' => $request->user()
                ->addresses()
                ->orderBy('id')
                ->get(['id', 'name', 'details']),
        ]);
    }
}
