<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomPokemon extends Model
{
    protected $table = 'custom_pokemons';

    protected $fillable = ['pokemon_id','name', 'height', 'weight', 'types', 'sprite_url'];

    protected $casts = [
        'types' => 'array',
    ];
}