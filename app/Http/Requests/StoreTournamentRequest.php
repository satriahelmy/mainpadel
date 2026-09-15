<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'players' => array_values(array_map(static fn ($player): string => trim((string) $player), $this->input('players', []))),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'played_at' => ['required', 'date'],
            'players' => ['required', 'array', 'min:4'],
            'players.*' => ['required', 'string', 'max:100'],
            'number_of_courts' => ['required', 'integer', 'min:1', 'max:20'],
            'target_points' => ['required', 'integer', 'min:1', 'max:100'],
            'round_mode' => ['required', 'in:auto,custom'],
            'number_of_rounds' => ['nullable', 'integer', 'min:1', 'max:50', 'required_if:round_mode,custom'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $normalized = array_map(static fn (string $name): string => mb_strtolower($name), $this->input('players', []));
            $duplicates = array_keys(array_filter(array_count_values($normalized), static fn (int $count): bool => $count > 1));

            if ($duplicates !== []) {
                $validator->errors()->add('players', 'Player names must be unique within this Game.');
            }
        });
    }
}
