<?php
use App\Http\Controllers\Api\BannedPokemonController;
use Illuminate\Support\Facades\Route;

Route::middleware('super.secret')->group(function () {
    Route::apiResource('banned', BannedPokemonController::class)->only(['index', 'store', 'show', 'destroy']);


});