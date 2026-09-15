<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'team_a_score' => ['required', 'integer', 'min:0', 'max:100'],
            'team_b_score' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $match = $this->route('match');
            $target = (int) optional($match?->round?->tournament)->target_points;
            $scoreA = (int) $this->input('team_a_score', -1);
            $scoreB = (int) $this->input('team_b_score', -1);

            if ($target > 0 && ($scoreA + $scoreB) !== $target) {
                $validator->errors()->add('team_a_score', "Scores must total {$target} points.");
            }
        });
    }
}
