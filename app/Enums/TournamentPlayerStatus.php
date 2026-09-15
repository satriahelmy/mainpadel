<?php

namespace App\Enums;

enum TournamentPlayerStatus: string
{
    case Active = 'active';
    case Withdrawn = 'withdrawn';
}
