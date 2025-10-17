<?php

namespace App\Entity\Enum;

enum CandidateJobMatchStatus: string
{
    case ACTIVE = 'active';
    case IGNORED = 'ignored';
}
