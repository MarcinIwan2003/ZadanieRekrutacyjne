<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PokemonApiCache extends Model
{
    protected $table = 'pokemon_api_cache';

    protected $fillable = [
        'cache_key',
        'normalized',
        'payload',
        'fetched_at',
        'expires_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'fetched_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
