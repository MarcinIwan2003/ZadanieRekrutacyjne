<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBannedPokemonRequest;
use App\Models\BannedPokemon;
use Illuminate\Http\Request;

class BannedPokemonController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $search = trim((string) $request->input('search', ''));
        $sort   = (string) $request->input('sort', '-created_at');

        $query = BannedPokemon::query();

        if ($search !== '') {
            $searchLower = mb_strtolower($search);

            $query->where(function ($q) use ($searchLower) {
                $q->where('pokemon_name', 'like', "%{$searchLower}%");

                if (ctype_digit($searchLower)) {
                    $q->orWhere('pokemon_id', (int) $searchLower);
                }
            });
        }

        [$sortField, $direction] = $this->parseSort($sort);

        return response()->json(
            $query->orderBy($sortField, $direction)
                ->paginate($perPage)
                ->appends($request->query())
        );
    }

    public function store(StoreBannedPokemonRequest $request)
    {
        $banned = BannedPokemon::create($request->validated());

        return response()->json(['data' => $banned], 201);
    }

    public function show(BannedPokemon $banned)
    {
        return response()->json(['data' => $banned]);
    }

    public function destroy(BannedPokemon $banned)
    {
        $banned->delete();

        return response()->noContent();
    }

    private function parseSort(string $sort): array
    {
        $allowed = ['created_at', 'pokemon_name', 'pokemon_id'];

        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        if (!in_array($field, $allowed, true)) {
            return ['created_at', 'desc'];
        }

        return [$field, $direction];
    }
}