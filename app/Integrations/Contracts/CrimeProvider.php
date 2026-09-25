<?php

namespace App\Integrations\Contracts;

use App\Domain\Crime\CrimeCategory;
use App\Domain\Crime\CrimeData;
use App\Domain\Crime\YearMonth;
use App\Domain\Geography\Coordinates;

interface CrimeProvider
{
    /** @return list<CrimeData> */
    public function nearby(Coordinates $coordinates, ?YearMonth $month): array;

    /** @return list<CrimeCategory> */
    public function categories(?YearMonth $month): array;
}
