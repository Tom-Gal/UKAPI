<?php

namespace App\Domain\Calendar;

use App\Integrations\Contracts\BankHolidayProvider;
use App\Support\Api\ApiException;
use Illuminate\Contracts\Cache\Repository;

final class BankHolidayService
{
    public function __construct(
        private readonly BankHolidayProvider $provider,
        private readonly Repository $cache,
    ) {}

    /** @return array{calendar: BankHolidayCalendar, cached: bool} */
    public function calendar(): array
    {
        $key = 'ukapi:v1:calendar:bank_holidays';
        $cached = $this->cache->get($key);

        if (is_array($cached)) {
            try {
                return ['calendar' => BankHolidayCalendar::fromArray($cached), 'cached' => true];
            } catch (\InvalidArgumentException) {
                $this->cache->forget($key);
            }
        }

        $calendar = $this->provider->calendar();
        $this->cache->put($key, $calendar->toArray(), now()->addHours(max(1, (int) config('ukapi.govuk_bank_holidays.cache_ttl_hours'))));

        return ['calendar' => $calendar, 'cached' => false];
    }

    /** @return array{division: BankHolidayDivision, cached: bool} */
    public function division(string $division): array
    {
        if (! BankHolidayDivision::isSupported($division)) {
            throw ApiException::invalidParameter('division', 'The division must be england-and-wales, scotland, or northern-ireland.');
        }

        $result = $this->calendar();

        return ['division' => $result['calendar']->divisions[$division], 'cached' => $result['cached']];
    }
}
