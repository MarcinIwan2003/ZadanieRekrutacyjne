<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetPokemonInfoRequest;
use App\Services\PokeApi\PokeApiService;

class PokemonInfoController extends Controller
{
    public function show(GetPokemonInfoRequest $request, PokeApiService $pokeApi)
    {
        $items = $request->validatedNormalized();

        $result = $pokeApi->getMany($items);

        return response()->json($result);
    }
}