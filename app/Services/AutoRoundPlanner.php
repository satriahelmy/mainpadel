<?php

namespace App\Services;

final class AutoRoundPlanner
{
    public function forPlayerCount(int $playerCount): int
    {
        $minimum = (int) config('mainpadel.auto_rounds.minimum', 3);
        $maximum = (int) config('mainpadel.auto_rounds.maximum', 8);

        return min($maximum, max($minimum, $playerCount - 1));
    }
}
