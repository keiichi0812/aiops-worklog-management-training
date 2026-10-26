<?php

namespace App\Services;

class WorkLogReport
{
    public function hours(int $halfHours): float
    {
        return $halfHours / 2;
    }
}
