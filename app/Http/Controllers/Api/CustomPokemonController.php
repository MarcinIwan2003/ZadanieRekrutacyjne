<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreCustomPokemonRequest;
use App\Http\Requests\UpdateCustomPokemonRequest;
use App\Services\PokeApi\PokeApiService;
use App\Models\CustomPokemon;

class CustomPokemonController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => CustomPokemon::query()
                ->orderByDesc('created_at')
                ->paginate((int) $request->input('per_page', 20))
                ->appends($request->query()),
        ]);
    }

    public function store(StoreCustomPokemonRequest $request, PokeApiService $pokeApi)
    {
        $data = $request->validated();

        //sprawdzenie czy występuje w pokeapi
        if ($pokeApi->existsInPokeApi($data['name'])) {
            return response()->json([
                'message' => 'Taka nazwa występuje w PokeAPI.',
            ], 422);
        }

        $pokemon = CustomPokemon::create($data);

        return response()->json(['data' => $pokemon], 201);
    }

    public function show(CustomPokemon $custom_pokemon)
    {
        return response()->json(['data' => $custom_pokemon]);
    }

    public function update(UpdateCustomPokemonRequest $request, CustomPokemon $custom_pokemon, PokeApiService $pokeApi)
    {
        $data = $request->validated();
        
        //sprawdzenie czy występuje w pokeapi
        if (array_key_exists('name', $data) && $pokeApi->existsInPokeApi($data['name'])) {
            return response()->json([
                'message' => 'Taka nazwa występuje w PokeAPI.',
            ], 422);
        }

        $custom_pokemon->update($data);

        return response()->json(['data' => $custom_pokemon]);
    }

    public function destroy(CustomPokemon $custom_pokemon)
    {
        $custom_pokemon->delete();
        return response()->noContent();
    }
}
