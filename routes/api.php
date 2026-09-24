<?php

use App\Http\Controllers\Api\OpenApiController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\PostcodeController;
use App\Http\Controllers\Api\V1\SicController;
use App\Http\Controllers\Api\V1\VatController;
use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\EnforceApiRateLimit;
use App\Http\Middleware\EnforceMonthlyQuota;
use Illuminate\Support\Facades\Route;

Route::get('openapi/v1.yaml', OpenApiController::class)->name('api.openapi.v1');

Route::prefix('v1')
    ->middleware([
        AuthenticateApiKey::class,
        EnforceApiRateLimit::class,
        EnforceMonthlyQuota::class,
    ])
    ->group(function (): void {
        Route::get('vat/calculate', [VatController::class, 'calculate'])
            ->defaults('ukapi_endpoint', 'vat.calculate')
            ->name('api.v1.vat.calculate');
        Route::get('vat/remove', [VatController::class, 'remove'])
            ->defaults('ukapi_endpoint', 'vat.remove')
            ->name('api.v1.vat.remove');

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

        Route::get('sic/search', [SicController::class, 'search'])
            ->defaults('ukapi_endpoint', 'sic.search')
            ->name('api.v1.sic.search');
        Route::get('sic/{code}', [SicController::class, 'show'])
            ->defaults('ukapi_endpoint', 'sic.show')
            ->name('api.v1.sic.show');

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
    });
