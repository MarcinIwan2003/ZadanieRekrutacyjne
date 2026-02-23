<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBannedPokemonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('pokemon_name')) {
            $this->merge([
                'pokemon_name' => mb_strtolower(trim((string) $this->input('pokemon_name'))),
            ]);
        }
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pokemon_name' => ['required', 'string', 'max:255', Rule::unique('banned_pokemons', 'pokemon_name')],
            'pokemon_id'   => ['nullable', 'integer', 'min:1'],
            'reason'       => ['nullable', 'string', 'max:500'],
        ];
    }
}
