<?php

namespace App\Integrations\Contracts;

use App\Domain\Companies\CompanyData;
use App\Domain\Companies\CompanyFilings;
use App\Domain\Companies\CompanyNumber;
use App\Domain\Companies\CompanyOfficers;
use App\Domain\Companies\CompanySearchResults;

interface CompanyProvider
{
    public function find(CompanyNumber $companyNumber): CompanyData;

    public function search(string $query, int $page, int $perPage): CompanySearchResults;

    public function officers(CompanyNumber $companyNumber, int $page, int $perPage): CompanyOfficers;

    public function filings(CompanyNumber $companyNumber, int $page, int $perPage): CompanyFilings;
}
