<?php

namespace App\Providers;

use App\Integrations\CompaniesHouse\CompaniesHouseCompanyProvider;
use App\Integrations\Contracts\CompanyProvider;
use App\Integrations\Contracts\CrimeProvider;
use App\Integrations\Contracts\FloodProvider;
use App\Integrations\Contracts\PostcodeProvider;
use App\Integrations\EnvironmentAgency\EnvironmentAgencyFloodProvider;
use App\Integrations\PoliceUk\PoliceUkCrimeProvider;
use App\Integrations\PostcodesIo\PostcodesIoPostcodeProvider;
use App\Notifications\SecurityAlertNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Knuckles\Scribe\Scribe;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CompanyProvider::class, CompaniesHouseCompanyProvider::class);
        $this->app->bind(CrimeProvider::class, PoliceUkCrimeProvider::class);
        $this->app->bind(FloodProvider::class, EnvironmentAgencyFloodProvider::class);
        $this->app->bind(PostcodeProvider::class, PostcodesIoPostcodeProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureSecurityNotifications();
        $this->configureScribe();
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

    /**
     * Send account alerts only once two-factor authentication is confirmed.
     */
    protected function configureSecurityNotifications(): void
    {
        Event::listen(TwoFactorAuthenticationConfirmed::class, function (TwoFactorAuthenticationConfirmed $event): void {
            $event->user->notify(SecurityAlertNotification::twoFactorEnabled());
        });

        Event::listen(TwoFactorAuthenticationDisabled::class, function (TwoFactorAuthenticationDisabled $event): void {
            $event->user->notify(SecurityAlertNotification::twoFactorDisabled());
        });
    }

    /**
     * Keep externally documented paths identical to the public API contract.
     *
     * Scribe normally converts Laravel resource-style parameters to generic
     * identifiers, which would turn the real `{postcode}` placeholder into
     * `{id}` for the named `postcodes.show` route.
     */
    protected function configureScribe(): void
    {
        if (! class_exists(Scribe::class)) {
            return;
        }

        Scribe::normalizeEndpointUrlUsing(
            static fn (string $uri): string => $uri,
        );
    }
}
