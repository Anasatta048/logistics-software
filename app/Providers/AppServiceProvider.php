<?php

namespace App\Providers;

use App\Models\Role;
use App\Models\User;
use App\Observers\ActivityObserver;
use App\Policies\ReportPolicy;
use App\Policies\RolePolicy;
use App\Services\Map\GeoapifyClient;
use App\Services\Map\GeoapifyMapService;
use App\Services\Map\GoogleClient;
use App\Services\Map\GoogleMapService;
use App\Services\Map\MapService;
use App\Services\Map\PlaceLookup;
use App\Services\Map\StubMapService;
use App\Support\MapProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One tenant context per request/job; scoped so it never outlives either.
        $this->app->scoped(\App\Support\Tenancy\TenantContext::class);

        // Map vendors (Geoapify / Google) are chosen by App\Support\MapProvider.
        $this->app->bind(GeoapifyClient::class, fn (): GeoapifyClient => new GeoapifyClient(config('services.geoapify.key')));
        $this->app->bind(GoogleClient::class, fn (): GoogleClient => new GoogleClient(config('services.google_maps.key')));

        // The address picker's suggestions come from whichever provider is chosen.
        $this->app->bind(PlaceLookup::class, fn (): PlaceLookup => MapProvider::name() === 'google'
            ? $this->app->make(GoogleClient::class)
            : $this->app->make(GeoapifyClient::class));

        // Miles and geocoding: the chosen provider first, then the other one if
        // it is keyed, then the offline estimate, so a map outage never blocks booking.
        $this->app->bind(MapService::class, function (): MapService {
            $geoapify = $this->app->make(GeoapifyClient::class);
            $google = $this->app->make(GoogleClient::class);

            $geoapifyService = $geoapify->enabled() ? new GeoapifyMapService($geoapify) : new StubMapService();

            return match (MapProvider::name()) {
                'google' => new GoogleMapService($google, $geoapifyService),
                'geoapify' => new GeoapifyMapService($geoapify, $google->enabled() ? new GoogleMapService($google) : new StubMapService()),
                default => new StubMapService(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Report has no Eloquent model to hang a discoverable Policy off of
        // (Section 0.1 still requires a Policy for authorization), so its
        // abilities are registered as named Gates here, one per report.
        foreach (['viewAny', 'loads', 'drivers', 'brokers', 'invoices', 'receivables', 'receipts', 'conversions', 'expenses', 'accountStatement', 'fundsFlow', 'dispatcherCommissions', 'profitAndLoss'] as $ability) {
            Gate::define("reports.{$ability}", fn (User $user): bool => (new ReportPolicy())->{$ability}($user));
        }

        // Spatie's Role lives outside App\Models, and the Gate only guesses
        // App\Models\Policies — so this one has to be wired by hand.
        Gate::policy(Role::class, RolePolicy::class);

        // The platform owner (SaaS super admin) passes every check and alone
        // reaches the plans / access-requests area.
        Gate::before(fn (User $user): ?bool => $user->is_super_admin ? true : null);
        Gate::define('manage-saas', fn (User $user): bool => (bool) $user->is_super_admin);

        // App-wide settings (team timezones) belong to admins alone.
        Gate::define('manage-settings', fn (User $user): bool => $user->can('settings.manage'));

        // Plan caps (users / drivers / loads per month) apply on every insert.
        \App\Services\Tenancy\TenantLimits::register();

        // The company's commission is the business's earning: admin only.
        Gate::define('view-company-commission', fn (User $user): bool => $user->can('commissions.view-company'));

        // The activity log shows everyone's actions, so it is admin-only too.
        Gate::define('view-activity-logs', fn (User $user): bool => $user->can('activity-logs.view'));

        // Audit trail: every model in config/activity.php. Sign-in/out is picked
        // up by LogAuthActivity through Laravel's listener discovery.
        foreach (config('activity.enabled', true) ? array_keys(config('activity.models', [])) : [] as $model) {
            $model::observe(ActivityObserver::class);
        }
    }
}
