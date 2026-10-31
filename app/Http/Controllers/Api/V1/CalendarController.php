<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Calendar\BankHolidayService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WorkingDaysRequest;
use App\Support\Api\ApiException;
use App\Support\Api\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CalendarController extends Controller
{
    public function bankHolidays(Request $request, BankHolidayService $service): JsonResponse
    {
        $result = $service->calendar();

        return ApiResponse::success($request, $result['calendar']->toArray(), $this->meta($result['cached'], 'UK'));
    }

    public function bankHolidayDivision(Request $request, string $division, BankHolidayService $service): JsonResponse
    {
        $result = $service->division($division);
        $coverage = match ($division) {
            'england-and-wales' => 'England and Wales',
            'scotland' => 'Scotland',
            'northern-ireland' => 'Northern Ireland',
            default => throw ApiException::invalidParameter('division', 'The division is invalid.'),
        };

        return ApiResponse::success($request, $result['division']->toArray(), $this->meta($result['cached'], $coverage));
    }

    public function workingDays(WorkingDaysRequest $request, BankHolidayService $service): JsonResponse
    {
        $from = CarbonImmutable::createFromFormat('!Y-m-d', $request->from());
        $to = CarbonImmutable::createFromFormat('!Y-m-d', $request->to());

        if (! $from instanceof CarbonImmutable || ! $to instanceof CarbonImmutable || $from->diffInDays($to) > 365) {
            throw ApiException::invalidParameter('to', 'The date range must be between one and 366 inclusive calendar days.');
        }

        $result = $service->division($request->division());
        $holidayDates = [];
        foreach ($result['division']->events as $holiday) {
            if ($holiday->date >= $request->from() && $holiday->date <= $request->to()) {
                $holidayDates[$holiday->date] = true;
            }
        }

        $calendarDays = $from->diffInDays($to) + 1;
        $workingDays = 0;
        $holidaysExcluded = 0;
        for ($date = $from; $date->lte($to); $date = $date->addDay()) {
            if ($date->isWeekend()) {
                continue;
            }

            if (isset($holidayDates[$date->format('Y-m-d')])) {
                $holidaysExcluded++;

                continue;
            }

            $workingDays++;
        }

        return ApiResponse::success($request, [
            'from' => $request->from(),
            'to' => $request->to(),
            'division' => $request->division(),
            'calendar_days' => $calendarDays,
            'working_days' => $workingDays,
            'holidays_excluded' => $holidaysExcluded,
        ], [
            ...$this->meta($result['cached'], $request->division()),
            'source' => 'gov_uk',
        ]);
    }

    /** @return array<string, bool|string|null> */
    private function meta(bool $cached, string $coverage): array
    {
        return [
            'cached' => $cached,
            'source' => 'gov_uk',
            'source_updated_at' => null,
            'coverage' => $coverage,
        ];
    }
}
