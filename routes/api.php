<?php
use App\Http\Controllers\Api\BannedPokemonController;
use App\Http\Controllers\Api\PokemonInfoController;

use Illuminate\Support\Facades\Route;

Route::post('/info', [PokemonInfoController::class, 'show']);

Route::middleware('super.secret')->group(function () {
    Route::apiResource('banned', BannedPokemonController::class)->only(['index', 'store', 'show', 'destroy']);


});