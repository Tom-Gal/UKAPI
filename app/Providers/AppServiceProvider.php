<?php

namespace App\Providers;

use App\Integrations\CompaniesHouse\CompaniesHouseCompanyProvider;
use App\Integrations\Contracts\CompanyProvider;
use App\Integrations\Contracts\CrimeProvider;
use App\Integrations\Contracts\FloodProvider;
use App\Integrations\Contracts\PostcodeProvider;
use App\Integrations\Contracts\MotHistoryProvider;
use App\Integrations\EnvironmentAgency\EnvironmentAgencyFloodProvider;
use App\Integrations\PoliceUk\PoliceUkCrimeProvider;
use App\Integrations\PostcodesIo\PostcodesIoPostcodeProvider;
use App\Integrations\MotHistory\MotHistoryProvider as MotHistoryProviderImplementation;
use App\Listeners\SendSubscriptionLifecycleNotification;
use App\Models\User;
use App\Notifications\SecurityAlertNotification;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Cashier\Events\WebhookHandled;
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
        $this->app->bind(MotHistoryProvider::class, MotHistoryProviderImplementation::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureSecurityNotifications();
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
        Event::listen(Login::class, function (Login $event): void {
            if ($event->guard !== 'web' || ! $event->user instanceof User) {
                return;
            }

            $event->user->notify(SecurityAlertNotification::signedIn(
                request()->ip(),
                request()->userAgent(),
            ));
        });
        Event::listen(WebhookHandled::class, SendSubscriptionLifecycleNotification::class);
    }
}
