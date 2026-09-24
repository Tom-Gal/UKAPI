<?php

namespace App\Integrations\Contracts;

use App\Domain\Geography\Coordinates;
use App\Domain\Geography\PostcodeData;
use App\Domain\Geography\UkPostcode;

interface PostcodeProvider
{
    public function find(UkPostcode $postcode): PostcodeData;

    /** @return list<PostcodeData> */
    public function nearby(UkPostcode $postcode, int $limit, int $radiusMetres): array;

    /** @return list<PostcodeData> */
    public function reverse(Coordinates $coordinates, int $limit = 1): array;
}
