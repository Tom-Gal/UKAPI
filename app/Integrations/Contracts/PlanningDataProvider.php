<?php

namespace App\Integrations\Contracts;

use App\Domain\Planning\PlanningConstraintCollection;
use App\Domain\Planning\PlanningConstraintQuery;

interface PlanningDataProvider
{
    public function constraints(PlanningConstraintQuery $query): PlanningConstraintCollection;
}
