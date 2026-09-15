<?php

namespace App\Enums;

enum TournamentStatus: string
{
    case Draft = 'draft';
    case Ongoing = 'ongoing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
