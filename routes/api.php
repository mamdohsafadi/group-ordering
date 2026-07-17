<?php

use App\Http\Controllers\Api\V1\CartItemController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\GroupOrderController;
use App\Http\Controllers\Api\V1\InvoiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — Group Ordering service contract
|--------------------------------------------------------------------------
| These endpoints are the durable deliverable of this repo — the live app
| will consume them after adoption. Spec: Group Ordering documentation §8.
| Keep them stateless — no session dependency; authentication resolves
| through the TokenValidator contract.
*/

Route::prefix('v1')->group(function () {
    Route::get('/ping', fn () => response()->json([
        'service' => 'group-ordering',
        'version' => 'v1',
        'status' => 'ok',
    ]));

    // Group order lifecycle (spec §8.1–8.9) — added feature by feature:
    Route::middleware('auth.api-token')->group(function () {
        Route::post('/group-orders', [GroupOrderController::class, 'store']);
        // No token-format constraint here: a clipped or malformed token must
        // still reach the controller so the contract 404 message is returned
        // (handoff §2.3) instead of Laravel's route-not-found error.
        Route::get('/group-orders/by-token/{token}', [GroupOrderController::class, 'showByToken']);
        Route::get('/group-orders/{groupOrder}', [GroupOrderController::class, 'show'])
            ->whereNumber('groupOrder');
        Route::post('/group-orders/{groupOrder}/join', [GroupOrderController::class, 'join'])
            ->whereNumber('groupOrder');
        Route::post('/group-orders/{groupOrder}/cancel', [GroupOrderController::class, 'cancel'])
            ->whereNumber('groupOrder');
        Route::post('/group-orders/{groupOrder}/cart/items', [CartItemController::class, 'store'])
            ->whereNumber('groupOrder');
        Route::put('/group-orders/{groupOrder}/cart/items/{item}', [CartItemController::class, 'update'])
            ->whereNumber('groupOrder')->whereNumber('item');
        Route::delete('/group-orders/{groupOrder}/cart/items/{item}', [CartItemController::class, 'destroy'])
            ->whereNumber('groupOrder')->whereNumber('item');
        Route::get('/group-orders/{groupOrder}/invoice', [InvoiceController::class, 'show'])
            ->whereNumber('groupOrder');
        Route::get('/group-orders/{groupOrder}/invoice/master', [InvoiceController::class, 'master'])
            ->whereNumber('groupOrder');
        Route::post('/group-orders/{groupOrder}/checkout', [CheckoutController::class, 'store'])
            ->whereNumber('groupOrder');
    });

    // Still to come, story by story:
    // POST   /group-orders/{groupOrder}/leave
});
