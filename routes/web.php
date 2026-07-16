<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GroupOrderMenuController;
use App\Http\Controllers\RestaurantController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [RestaurantController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Demo screens; menu/address data comes from the DB as props (handoff §3),
    // group-order state from /api/v1.
    Route::get('/restaurants/{restaurant}', [RestaurantController::class, 'show'])
        ->whereNumber('restaurant')->name('restaurants.show');

    Route::get('/group-orders/{groupOrder}/menu', [GroupOrderMenuController::class, 'show'])
        ->whereNumber('groupOrder')->name('group-orders.menu');

    Route::get('/group-orders/{groupOrder}/checkout', fn (int $groupOrder) => Inertia::render('GroupOrders/Checkout', [
        'groupOrderId' => $groupOrder,
    ]))->whereNumber('groupOrder')->name('group-orders.checkout');

    Route::get('/group-orders/{groupOrder}/lobby', fn (int $groupOrder) => Inertia::render('GroupOrders/Lobby', [
        'groupOrderId' => $groupOrder,
    ]))->whereNumber('groupOrder')->name('group-orders.lobby');

    // US-002 AC2: guests hitting a join link are sent to login and returned
    // here afterwards (redirect()->intended in AuthController).
    Route::get('/join/{token}', fn (string $token) => Inertia::render('GroupOrders/Join', [
        'token' => $token,
    ]))->where('token', '[a-f0-9]{32}')->name('group-orders.join');
});
