<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomPokemonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pokemon_id' => ['required', 'integer', 'min:1', Rule::unique('custom_pokemons', 'pokemon_id')],
            'name' => ['required','string','max:60', Rule::unique('custom_pokemons', 'name')],
            'height' => ['nullable','integer','min:1','max:9999'],
            'weight' => ['nullable','integer','min:1','max:9999'],
            'types' => ['nullable','array','max:5'],
            'types.*' => ['string','max:30'],
            'sprite_url' => ['nullable','url','max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => mb_strtolower(trim((string) $this->input('name')))]);
        }
    }
}
