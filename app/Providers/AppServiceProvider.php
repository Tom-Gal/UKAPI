<?php

namespace App\Providers;

use App\Integrations\CompaniesHouse\CompaniesHouseCompanyProvider;
use App\Integrations\Contracts\CompanyProvider;
use App\Integrations\Contracts\PostcodeProvider;
use App\Integrations\PostcodesIo\PostcodesIoPostcodeProvider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CompanyProvider::class, CompaniesHouseCompanyProvider::class);
        $this->app->bind(PostcodeProvider::class, PostcodesIoPostcodeProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
