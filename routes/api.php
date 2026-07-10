<?php

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
    // POST   /group-orders
    // POST   /group-orders/{groupOrder}/join
    // POST   /group-orders/{groupOrder}/cart/items
    // PUT    /group-orders/{groupOrder}/cart/items/{item}
    // DELETE /group-orders/{groupOrder}/cart/items/{item}
    // GET    /group-orders/{groupOrder}/invoice
    // GET    /group-orders/{groupOrder}/invoice/master
    // POST   /group-orders/{groupOrder}/checkout
    // POST   /group-orders/{groupOrder}/leave
});
