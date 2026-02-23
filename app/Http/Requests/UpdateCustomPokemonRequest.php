<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomPokemonRequest extends FormRequest
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
        $id = $this->route('custom_pokemon')?->id ?? $this->route('custom_pokemon');

        return [
            'pokemon_id' => ['required', 'integer', 'min:1', Rule::unique('custom_pokemons', 'pokemon_id')],
            'name' => ['sometimes','required','string','max:60', Rule::unique('custom_pokemons','name')->ignore($id)],
            'height' => ['sometimes','nullable','integer','min:1','max:9999'],
            'weight' => ['sometimes','nullable','integer','min:1','max:9999'],
            'types' => ['sometimes','nullable','array','max:5'],
            'types.*' => ['string','max:30'],
            'sprite_url' => ['sometimes','nullable','url','max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => mb_strtolower(trim((string) $this->input('name')))]);
        }
    }
}
