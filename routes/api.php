<?php

use App\Http\Controllers\Api\OpenApiController;
use App\Http\Controllers\Api\V1\CalendarController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\CouncilController;
use App\Http\Controllers\Api\V1\CrimeController;
use App\Http\Controllers\Api\V1\FloodController;
use App\Http\Controllers\Api\V1\FoodEstablishmentController;
use App\Http\Controllers\Api\V1\MotHistoryController;
use App\Http\Controllers\Api\V1\PlanningController;
use App\Http\Controllers\Api\V1\PostcodeController;
use App\Http\Controllers\Api\V1\SicController;
use App\Http\Controllers\Api\V1\SicExportController;
use App\Http\Controllers\Api\V1\TaxYearController;
use App\Http\Controllers\Api\V1\VatController;
use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\EnforceApiRateLimit;
use App\Http\Middleware\EnforceMonthlyQuota;
use App\Http\Middleware\RecordApiOperation;
use App\Http\Middleware\RecordApiRequestLog;
use App\Http\Middleware\TrackFirstApiSuccess;
use Illuminate\Support\Facades\Route;

Route::get('openapi/v1.yaml', OpenApiController::class)->name('api.openapi.v1');

Route::prefix('v1')
    ->middleware([
        RecordApiRequestLog::class,
        AuthenticateApiKey::class,
        'ukapi.feature:current_data',
        EnforceApiRateLimit::class,
        EnforceMonthlyQuota::class,
        TrackFirstApiSuccess::class,
        RecordApiOperation::class,
    ])
    ->group(function (): void {
        Route::get('vat/calculate', [VatController::class, 'calculate'])
            ->defaults('ukapi_endpoint', 'vat.calculate')
            ->name('api.v1.vat.calculate');
        Route::get('vat/remove', [VatController::class, 'remove'])
            ->defaults('ukapi_endpoint', 'vat.remove')
            ->name('api.v1.vat.remove');

        Route::get('mot/registration/{registration}', [MotHistoryController::class, 'registration'])
            ->defaults('ukapi_endpoint', 'mot.registration')
            ->name('api.v1.mot.registration');
        Route::get('mot/vin/{vin}', [MotHistoryController::class, 'vin'])
            ->defaults('ukapi_endpoint', 'mot.vin')
            ->name('api.v1.mot.vin');

        Route::get('companies/search', [CompanyController::class, 'search'])
            ->defaults('ukapi_endpoint', 'companies.search')
            ->name('api.v1.companies.search');
        Route::get('companies/{companyNumber}/officers', [CompanyController::class, 'officers'])
            ->defaults('ukapi_endpoint', 'companies.officers')
            ->name('api.v1.companies.officers');
        Route::get('companies/{companyNumber}/filings', [CompanyController::class, 'filings'])
            ->defaults('ukapi_endpoint', 'companies.filings')
            ->name('api.v1.companies.filings');
        Route::get('companies/{companyNumber}', [CompanyController::class, 'show'])
            ->defaults('ukapi_endpoint', 'companies.show')
            ->name('api.v1.companies.show');

        Route::get('sic/export', SicExportController::class)
            ->middleware('ukapi.feature:bulk_export')
            ->defaults('ukapi_endpoint', 'sic.export')
            ->name('api.v1.sic.export');
        Route::get('sic/search', [SicController::class, 'search'])
            ->defaults('ukapi_endpoint', 'sic.search')
            ->name('api.v1.sic.search');
        Route::get('sic/changes', [SicController::class, 'changes'])
            ->middleware('ukapi.feature:change_feeds')
            ->defaults('ukapi_endpoint', 'sic.changes')
            ->name('api.v1.sic.changes');
        Route::get('sic/{code}', [SicController::class, 'show'])
            ->defaults('ukapi_endpoint', 'sic.show')
            ->name('api.v1.sic.show');

        Route::get('crime/nearby', [CrimeController::class, 'nearby'])
            ->defaults('ukapi_endpoint', 'crime.nearby')
            ->name('api.v1.crime.nearby');
        Route::get('crime/summary', [CrimeController::class, 'summary'])
            ->defaults('ukapi_endpoint', 'crime.summary')
            ->name('api.v1.crime.summary');
        Route::get('crime/categories', [CrimeController::class, 'categories'])
            ->defaults('ukapi_endpoint', 'crime.categories')
            ->name('api.v1.crime.categories');
        Route::get('crime/changes', [CrimeController::class, 'changes'])
            ->middleware('ukapi.feature:change_feeds')
            ->defaults('ukapi_endpoint', 'crime.changes')
            ->name('api.v1.crime.changes');

        Route::get('flood/warnings', [FloodController::class, 'warnings'])
            ->defaults('ukapi_endpoint', 'flood.warnings')
            ->name('api.v1.flood.warnings');
        Route::get('flood/nearby', [FloodController::class, 'nearby'])
            ->defaults('ukapi_endpoint', 'flood.nearby')
            ->name('api.v1.flood.nearby');
        Route::get('flood/stations/nearby', [FloodController::class, 'stationsNearby'])
            ->defaults('ukapi_endpoint', 'flood.stations.nearby')
            ->name('api.v1.flood.stations.nearby');
        Route::get('flood/stations/{id}/readings', [FloodController::class, 'readings'])
            ->defaults('ukapi_endpoint', 'flood.stations.readings')
            ->name('api.v1.flood.stations.readings');
        Route::get('flood/stations/{id}/changes', [FloodController::class, 'changes'])
            ->middleware('ukapi.feature:change_feeds')
            ->defaults('ukapi_endpoint', 'flood.stations.changes')
            ->name('api.v1.flood.stations.changes');

        Route::get('food-establishments/search', [FoodEstablishmentController::class, 'search'])
            ->defaults('ukapi_endpoint', 'food.establishments.search')
            ->name('api.v1.food.establishments.search');
        Route::get('food-establishments/nearby', [FoodEstablishmentController::class, 'nearby'])
            ->defaults('ukapi_endpoint', 'food.establishments.nearby')
            ->name('api.v1.food.establishments.nearby');
        Route::get('food-establishments/{id}', [FoodEstablishmentController::class, 'show'])
            ->defaults('ukapi_endpoint', 'food.establishments.show')
            ->name('api.v1.food.establishments.show');

        Route::get('planning/constraints', [PlanningController::class, 'constraints'])
            ->defaults('ukapi_endpoint', 'planning.constraints')
            ->name('api.v1.planning.constraints');

        Route::get('postcodes/{postcode}/validate', [PostcodeController::class, 'validate'])
            ->defaults('ukapi_endpoint', 'postcodes.validate')
            ->name('api.v1.postcodes.validate');
        Route::get('postcodes/{postcode}/nearby', [PostcodeController::class, 'nearby'])
            ->defaults('ukapi_endpoint', 'postcodes.nearby')
            ->name('api.v1.postcodes.nearby');
        Route::get('postcodes/{postcode}', [PostcodeController::class, 'show'])
            ->defaults('ukapi_endpoint', 'postcodes.show')
            ->name('api.v1.postcodes.show');
        Route::get('coordinates/{latitude}/{longitude}/postcode', [PostcodeController::class, 'reverse'])
            ->defaults('ukapi_endpoint', 'coordinates.postcode')
            ->name('api.v1.coordinates.postcode');

        Route::get('outcodes/{outcode}', [PostcodeController::class, 'outcode'])
            ->defaults('ukapi_endpoint', 'outcodes.show')
            ->name('api.v1.outcodes.show');
        Route::get('councils/postcode/{postcode}', [CouncilController::class, 'postcode'])
            ->defaults('ukapi_endpoint', 'councils.postcode')
            ->name('api.v1.councils.postcode');
        Route::get('councils/{code}', [CouncilController::class, 'show'])
            ->defaults('ukapi_endpoint', 'councils.show')
            ->name('api.v1.councils.show');
        Route::get('bank-holidays', [CalendarController::class, 'bankHolidays'])
            ->defaults('ukapi_endpoint', 'calendar.bankholidays')
            ->name('api.v1.bankholidays.index');
        Route::get('bank-holidays/{division}', [CalendarController::class, 'bankHolidayDivision'])
            ->defaults('ukapi_endpoint', 'calendar.bankholidays.division')
            ->name('api.v1.bankholidays.show');
        Route::get('working-days', [CalendarController::class, 'workingDays'])
            ->defaults('ukapi_endpoint', 'calendar.workingdays')
            ->name('api.v1.workingdays.calculate');
        Route::get('tax-years/current', [TaxYearController::class, 'current'])
            ->defaults('ukapi_endpoint', 'tax.currentyear')
            ->name('api.v1.tax.currentyear');
        Route::get('tax-years/{year}', [TaxYearController::class, 'show'])
            ->defaults('ukapi_endpoint', 'tax.year')
            ->name('api.v1.tax.year');
    });
