<?php

namespace App\Services\PokeApi;

use App\Models\BannedPokemon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
//model z etapu 4
use App\Models\CustomPokemon;

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
        //z etapu 4 - odpytywanie PokeApi tylko nie z custom
        $allowedIds = array_values(array_filter($allowed, fn ($v) => is_int($v)));
        $allowedNames = array_values(array_filter($allowed, fn ($v) => is_string($v)));

        $customById = CustomPokemon::query()
            ->whereIn('pokemon_id', $allowedIds)
            ->get()
            ->keyBy('pokemon_id');

        $customByName = CustomPokemon::query()
            ->whereIn('name', $allowedNames)
            ->get()
            ->keyBy('name');
        
        $toPokeApi = [];

        foreach ($allowed as $v) {
            if (is_int($v) && $customById->has($v)) {
                continue;
            }
            if (is_string($v) && $customByName->has($v)) {
                continue;
            }
            $toPokeApi[] = $v;
        }

        $responses = Http::pool(function ($pool) use ($toPokeApi) {
            foreach ($toPokeApi as $v) {
                $pool->as((string) $v)->get($this->pokemonUrl($v));
            }
        });

        $data = [];
        $notFound = [];

        foreach ($allowed as $v) {
            $key = (string) $v;
            $res = $responses[$key] ?? null;

            

            // z etapu 4 -custom
            if (is_int($v) && $customById->has($v)) {
                $data[] = $this->mapCustom($customById->get($v));
                continue;
            }

            if (is_string($v) && $customByName->has($v)) {
                $data[] = $this->mapCustom($customByName->get($v));
                continue;
            }

            //reszta bez zmian z PokeApi
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
    //z etapu 3
    public function existsInPokeApi(string $name): bool
    {
    $res = Http::timeout(5)->get('https://pokeapi.co/api/v2/pokemon/' . urlencode($name));

    if ($res->status() === 404) return false;

    return $res->ok();
    }
    
    //etap 4 - mapowanie customs
     private function mapCustom(CustomPokemon $p): array
    {
        return [
            'id' => $p->pokemon_id,
            'name' => $p->name,
            'height' => $p->height,
            'weight' => $p->weight,
            'types' => $p->types ?? [],
            'sprites' => [
                'front_default' => $p->sprite_url,
            ],
            'source' => 'custom',
        ];
    }

}