<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Demo screens; data comes from the API client layer (mock for now).
    Route::get('/restaurants/{restaurant}', fn (int $restaurant) => Inertia::render('Restaurants/Show', [
        'restaurantId' => $restaurant,
    ]))->whereNumber('restaurant')->name('restaurants.show');

    Route::get('/group-orders/{groupOrder}/lobby', fn (int $groupOrder) => Inertia::render('GroupOrders/Lobby', [
        'groupOrderId' => $groupOrder,
    ]))->whereNumber('groupOrder')->name('group-orders.lobby');
});
