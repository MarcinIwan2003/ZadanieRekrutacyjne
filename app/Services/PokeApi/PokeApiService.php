<?php

namespace App\Services\PokeApi;

use App\Models\BannedPokemon;
use Illuminate\Support\Facades\Http;
//model z etapu 4
use App\Models\CustomPokemon;
//etap5
use App\Models\PokemonApiCache;
use Carbon\Carbon;

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

        $cachedByKey = $this->getCacheMany($toPokeApi);
        $cachedSet = [];
        $toFetch = [];

        foreach ($toPokeApi as $v) {
            $key = $this->cacheKey($v);

                if (isset($cachedByKey[$key])) {
                    $cachedSet[$key] = $cachedByKey[$key];
                    continue;
                }

            $toFetch[] = $v;
        
        }

        $responses = Http::pool(function ($pool) use ($toFetch) {
            foreach ($toFetch as $v) {
                $pool->as((string) $v)->get($this->pokemonUrl($v));
            }
        });

        $data = [];
        $notFound = [];

        foreach ($allowed as $v) {
           
            // z etapu 4 -custom
            if (is_int($v) && $customById->has($v)) {
                $data[] = $this->mapCustom($customById->get($v));
                continue;
            }

            if (is_string($v) && $customByName->has($v)) {
                $data[] = $this->mapCustom($customByName->get($v));
                continue;
            }
            //etap 5
            $ckey = $this->cacheKey($v);

            if (isset($cachedSet[$ckey])) {
                $data[] = $cachedSet[$ckey];
                continue;
            }

            $key = (string) $v;
            $res = $responses[$key] ?? null;

            
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

        
            $payload = [
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
                'source' => 'pokeapi',
            ];

            $data[] = $payload;

            // etap 5 zapis cache
            $this->putCache($json['id'], $payload);
            $this->putCache($json['name'], $payload);
        
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

    //etap4 - pomocnicze funkcja cacheowania
        private function cacheKey(int|string $v): string
        {
            return is_int($v) ? (string) $v : mb_strtolower((string) $v);
        }

        private function getCacheMany(array $items): array
        {
            $keys = array_map(fn($v) => $this->cacheKey($v), $items);

            $now = Carbon::now('Etc/GMT-1'); // stałe UTC+1

            return PokemonApiCache::query()
                ->whereIn('cache_key', $keys)
                ->where('expires_at', '>', $now)
                ->get()
                ->mapWithKeys(fn($row) => [$row->cache_key => $row->payload])
                ->all();
        }

        private function putCache(int|string $v, array $payload): void
        {
            $key = $this->cacheKey($v);

            $now = Carbon::now('Etc/GMT-1');
            $expiresAt = $this->nextNoonUtcPlus1($now);

            PokemonApiCache::updateOrCreate(
                ['cache_key' => $key],
                [
                    'normalized' => $key,
                    'payload' => $payload,
                    'fetched_at' => $now,
                    'expires_at' => $expiresAt,
                ]
            );
        }

        private function nextNoonUtcPlus1(Carbon $now): Carbon
        {
            $noonToday = $now->copy()->setTime(12, 0, 0);

                if ($now->lt($noonToday)) {
                    return $noonToday;
                }

            return $noonToday->addDay();
        }

}