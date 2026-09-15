<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MainPadel product defaults
    |--------------------------------------------------------------------------
    |
    | These values intentionally live outside controllers so product tuning
    | does not require changing the HTTP layer or the drawing algorithm.
    |
    */
    'defaults' => [
        'target_points' => 21,
        'round_mode' => 'auto',
    ],

    'auto_rounds' => [
        // Auto mode uses clamp(active_players - 1, minimum, maximum).
        'minimum' => 3,
        'maximum' => 8,
    ],

    'drawing' => [
        'candidate_limit' => 1500,
        'diagnostics' => (bool) env('MAINPADEL_DRAWING_DIAGNOSTICS', false),
        'weights' => [
            'match_count_imbalance' => 1000,
            'rest_imbalance' => 500,
            'repeated_partner' => 50,
            'repeated_opponent' => 25,
            'consecutive_rest' => 10,
        ],
    ],

    'ranking' => [
        'keys' => [
            'points_for',
            'point_difference',
            'wins',
        ],
        'tie_breaker' => 'name',
    ],
];
