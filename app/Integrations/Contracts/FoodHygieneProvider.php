<?php

namespace App\Integrations\Contracts;

use App\Domain\Food\FoodEstablishmentCollection;
use App\Domain\Food\FoodEstablishmentData;
use App\Domain\Food\FoodEstablishmentId;
use App\Domain\Food\FoodEstablishmentSearch;

interface FoodHygieneProvider
{
    public function find(FoodEstablishmentId $id): FoodEstablishmentData;

    public function search(FoodEstablishmentSearch $search): FoodEstablishmentCollection;
}
