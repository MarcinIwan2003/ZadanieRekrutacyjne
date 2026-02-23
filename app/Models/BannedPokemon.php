<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BannedPokemon extends Model
{
     protected $table = 'banned_pokemons';
     
     protected $fillable = [
        'pokemon_name',
        'pokemon_id',
        'reason',
    ];
}
