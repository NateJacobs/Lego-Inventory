<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\VaultController;
use App\Http\Controllers\CatalogController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Totals for vault.ngtj.com, read with a `vault:read` token (php artisan vault:token).
// ConfineVaultTokens keeps that token away from every other route.
Route::middleware(['auth:sanctum', 'abilities:vault:read'])->prefix('vault')->group(function () {
    Route::get('summary', [VaultController::class, 'summary']);
    Route::get('history', [VaultController::class, 'history']);
});

Route::middleware('auth:sanctum')->prefix('catalog')->group(function () {
    Route::get('{setNumber}/{setVariant?}', [CatalogController::class, 'getDetails']);
    // Route::get('{setNumber}/owned', [CatalogController::class, 'getOwned']);
});

Route::fallback(function(){
    return response()->json([
        'message' => 'Not Found.'], 404);
});
