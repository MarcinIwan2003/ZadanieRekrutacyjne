<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetPokemonInfoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pokemons' => ['required', 'array', 'min:1', 'max:50'],
            'pokemons.*' => ['required'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('pokemons', []);

            foreach ($items as $i => $value) {
                if (is_int($value)) {
                    if ($value < 1) {
                        $validator->errors()->add("pokemons.$i", 'ID musi być większe lub równe 1');
                    }
                    continue;
                }

                if (is_string($value)) {
                    $v = trim($value);
                    if ($v === '') {
                        $validator->errors()->add("pokemons.$i", 'Nazwa nie może być pusta');
                    }
                    if (mb_strlen($v) > 60) {
                        $validator->errors()->add("pokemons.$i", 'Nazwa jest za długa');
                    }
                    continue;
                }

                $validator->errors()->add("pokemons.$i", 'Dozwolone są tylko: string (nazwa) lub int (id)');
            }
        });
    }

    public function validatedNormalized(): array
    {
        $items = $this->validated()['pokemons'];

        return array_values(array_map(function ($v) {
            if (is_int($v)) return $v;
            return mb_strtolower(trim($v));
        }, $items));
    }
}
