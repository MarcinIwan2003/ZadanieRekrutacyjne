<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('banned_pokemons', function (Blueprint $table) {
            $table->id();
            $table->string('pokemon_name')->index();
            $table->unsignedInteger('pokemon_id')->nullable()->index();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique('pokemon_name'); // unikalność po nazwie
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banned_pokemons');
    }
};
