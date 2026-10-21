<?php

namespace App\Providers;

use App\Models\Role;
use App\Models\User;
use App\Observers\ActivityObserver;
use App\Policies\ReportPolicy;
use App\Policies\RolePolicy;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // One ceiling for every page and action, per signed-in user (per address for guests):
        // far above human use, low enough to stop a script hammering the app.
        \Illuminate\Support\Facades\RateLimiter::for('app', fn (\Illuminate\Http\Request $request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(300)
            ->by($request->user()?->id ? 'u'.$request->user()->id : 'ip'.$request->ip()));

        // Stronger passwords in production (min 10, mixed case, a digit); light rules while developing.
        \Illuminate\Validation\Rules\Password::defaults(fn () => $this->app->isProduction()
            ? \Illuminate\Validation\Rules\Password::min(10)->letters()->mixedCase()->numbers()
            : \Illuminate\Validation\Rules\Password::min(8));

        // A relation used without being eager-loaded is an N+1 query waiting for a longer list.
        // Fail loudly while developing and testing; in production just carry on.
        \Illuminate\Database\Eloquent\Model::preventLazyLoading(! $this->app->isProduction());

        // Report has no Eloquent model to hang a discoverable Policy off of
        // (Section 0.1 still requires a Policy for authorization), so its
        // abilities are registered as named Gates here, one per report.
        foreach (['viewAny', 'loads', 'drivers', 'brokers', 'brokerAging', 'settlements', 'invoices', 'receivables', 'receipts', 'conversions', 'expenses', 'accountStatement', 'fundsFlow', 'dispatcherCommissions', 'profitAndLoss'] as $ability) {
            Gate::define("reports.{$ability}", fn (User $user): bool => (new ReportPolicy())->{$ability}($user));
        }

        // Spatie's Role lives outside App\Models, and the Gate only guesses
        // App\Models\Policies — so this one has to be wired by hand.
        Gate::policy(Role::class, RolePolicy::class);

        // The platform owner (SaaS super admin) passes every check and alone
        // reaches the plans / access-requests area.
        Gate::before(fn (User $user): ?bool => $user->is_super_admin ? true : null);
        // A module the tenant's plan leaves out is closed to every user of it,
        // whatever role they hold: policies, `can:` routes and the menu all go through here.
        Gate::before(function (User $user, string $ability, array $arguments = []): ?bool {
            $module = \App\Support\PlanModules::forAbility($ability, $arguments);

            return $module !== null && ! app(\App\Services\Tenancy\ModuleAccess::class)->allows($module) ? false : null;
        });
        Gate::define('manage-saas', fn (User $user): bool => (bool) $user->is_super_admin);

        // App-wide settings (team timezones) belong to admins alone.
        Gate::define('manage-data', fn (User $user): bool => $user->can('data.import') || $user->can('data.export'));
        // Paying for the subscription is an owner/admin job, the same people who run settings.
        Gate::define('manage-billing', fn (User $user): bool => $user->can('settings.manage'));
        Gate::define('manage-settings', fn (User $user): bool => $user->can('settings.manage'));

        // Demo sandboxes use addresses on the reserved .invalid domain: never actually email them.
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Mail\Events\MessageSending::class, function (\Illuminate\Mail\Events\MessageSending $event): ?bool {
            foreach ([...$event->message->getTo(), ...$event->message->getCc(), ...$event->message->getBcc()] as $address) {
                if (str_ends_with(strtolower($address->getAddress()), '.invalid')) {
                    return false;
                }
            }

            return null;
        });

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
