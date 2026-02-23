<?php

namespace App\Services\PokeApi;

use App\Models\BannedPokemon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PokeApiService
{
    public function getMany(array $items): array
    {
        $ids = [];
        $names = [];

        foreach ($items as $v) {
            if (is_int($v)) $ids[] = $v;
            else $names[] = $v;
        }

        $bannedIds = BannedPokemon::query()
            ->whereIn('pokemon_id', $ids)
            ->pluck('pokemon_id')
            ->all();

        $bannedNames = BannedPokemon::query()
            ->whereIn('pokemon_name', $names)
            ->pluck('pokemon_name')
            ->map(fn ($n) => mb_strtolower($n))
            ->all();

        $bannedSet = array_fill_keys(array_map('strval', $bannedIds), true)
            + array_fill_keys($bannedNames, true);

        $allowed = [];
        $skippedBanned = [];

        foreach ($items as $v) {
            $key = is_int($v) ? (string) $v : $v;

            if (isset($bannedSet[$key])) {
                $skippedBanned[] = $v;
                continue;
            }

            $allowed[] = $v;
        }

        $responses = Http::pool(function ($pool) use ($allowed) {
            foreach ($allowed as $v) {
                $pool->as((string) $v)->get($this->pokemonUrl($v));
            }
        });

        $data = [];
        $notFound = [];

        foreach ($allowed as $v) {
            $key = (string) $v;
            $res = $responses[$key];

            if ($res->status() === 404) {
                $notFound[] = $v;
                continue;
            }

            if (!$res->ok()) {
                // traktujemy jak not_found
                $notFound[] = $v;
                continue;
            }

            $json = $res->json();

        
            $data[] = [
                'id' => $json['id'],
                'name' => $json['name'],
                'height' => $json['height'],
                'weight' => $json['weight'],
                'types' => array_values(array_map(
                    fn ($t) => $t['type']['name'],
                    $json['types'] ?? []
                )),
                'sprites' => [
                    'front_default' => $json['sprites']['front_default'] ?? null,
                ],
            ];
        }

        return [
            'data' => $data,
            'skipped' => [
                'banned' => $skippedBanned,
                'not_found' => $notFound,
            ],
        ];
    }

    private function pokemonUrl(int|string $idOrName): string
    {
        return 'https://pokeapi.co/api/v2/pokemon/' . urlencode((string) $idOrName);
    }
}