<?php

namespace App\Integrations\Contracts;

use App\Domain\Calendar\BankHolidayCalendar;

interface BankHolidayProvider
{
    public function calendar(): BankHolidayCalendar;
}
