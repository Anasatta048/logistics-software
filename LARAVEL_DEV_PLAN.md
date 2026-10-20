# Dreamelect Logistics – Dispatch & Brokerage Management System
## Laravel Development Plan (Phased, for AI Coding Agent)

> **How to use this file:** Give this whole file to your coding agent (opencode) as the project brief.
> Work phase by phase, in order. Do not start a phase until the previous phase's "Definition of Done" is satisfied.
> Every rule in **Section 0 – Coding Standards** applies to *every* phase, not just Phase 1.

---

## 0. Coding Standards (Apply to the Whole Project)

These are non-negotiable rules the agent must follow in every phase.

### 0.1 Architecture Rules
- **Controllers must stay thin.** A controller method only: validates the request (via a Form Request), calls **one** Service method, and returns a response/view/resource. No business logic, no direct DB queries, no `Model::where(...)` chains inside controllers.
- **No direct/raw function calls scattered in controllers.** All business logic (calculations, status changes, reversals, commission math, distance calculation, etc.) lives inside a **Service class**, injected into the controller via the constructor (constructor property promotion + dependency injection). Controllers call `$this->service->doSomething($data)` — never inline logic.
- **Every module has its own Service class(es)**, under `app/Services/{Module}/`. If a module has more than one responsibility (e.g. Load has "create/update" + "status transition" + "cancellation"), split into multiple services (`LoadService`, `LoadStatusService`, `LoadCancellationService`) rather than one giant service.
- **One folder per module**, mirrored consistently across every layer (Controllers, Services, Requests, Resources). Never dump unrelated module logic into a shared/general folder.
- **Every module has its own route file.** No module shares a route file with another. All module route files are `require`d from `routes/web.php` (or `routes/api.php` if built API-first).
- **State-changing side effects use Events + Listeners**, not manual calls scattered across services. Example: cancelling a load fires `LoadCancelled` — a set of Listeners (release driver, reverse commission, void financial entries) react to it. This keeps `LoadCancellationService` simple and makes each reversal independently testable.
- **Use Form Requests for all validation** (`app/Http/Requests/{Module}/Store...Request.php`, `Update...Request.php`). No `$request->validate([...])` inline in controllers.
- **Use API Resources** (`app/Http/Resources/{Module}/...Resource.php`) for shaping any JSON/API output, and for data passed to frontend views/components.
- **Use PHP Enums** for fixed value sets: Load Status, Account Type, Expense Category, Permission types, etc. (`app/Enums/`). Never hardcode status strings like `'booked'` directly in logic — reference `LoadStatus::Booked`.
- **Use Policies** (`app/Policies/`) for authorization (who can view/edit/delete/approve/cancel what), registered against Models, checked via `$this->authorize()` in controllers or `can:` middleware in routes — never manual `if ($user->role == 'admin')` checks scattered in code.
- **Use Repositories only where genuinely useful** (Reports module, since reporting queries are complex and reused) — see Phase 8. Simple CRUD modules do not need a Repository layer; Service → Eloquent Model directly is fine there.
- **Database:** every table has a migration, every status/enum-like column documented, foreign keys with `constrained()->cascadeOnUpdate()->restrictOnDelete()` (or `nullOnDelete()` where appropriate), soft deletes (`SoftDeletes` trait) on Load, Driver, Broker, Account, JournalVoucher — nothing is hard-deleted for audit purposes.
- **Audit trail:** every model that matters (Load especially) logs status changes with user + timestamp + reason in a dedicated history table — not just an `updated_at` column.
- **Naming conventions:** PSR-12, singular model names (`Load`, `Driver`), plural table names (`loads`, `drivers`), camelCase methods, snake_case DB columns, Route names as `module.action` (e.g. `loads.cancel`).
- **No business logic in Blade/frontend.** Frontend only displays what the Controller/Resource gives it.

### 0.2 Standard Folder Structure

```
app/
├── Enums/
│   ├── LoadStatus.php
│   ├── AccountType.php
│   ├── ExpenseCategory.php
│   └── PermissionAction.php
├── Events/
│   ├── Load/
│   │   ├── LoadStatusChanged.php
│   │   ├── LoadCancelled.php
│   │   └── LoadPaid.php
├── Listeners/
│   ├── Load/
│   │   ├── ReleaseDriverOnCancellation.php
│   │   ├── ReverseCommissionOnCancellation.php
│   │   ├── VoidFinancialEntriesOnCancellation.php
│   │   └── CalculateCommissionOnPaid.php
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   ├── User/UserController.php
│   │   ├── Driver/DriverController.php
│   │   ├── Broker/BrokerController.php
│   │   ├── Load/LoadController.php
│   │   ├── Commission/CommissionController.php
│   │   ├── Expense/ExpenseController.php
│   │   ├── Account/AccountController.php
│   │   ├── Account/JournalVoucherController.php
│   │   └── Report/ReportController.php
│   ├── Requests/
│   │   ├── Driver/{Store,Update}DriverRequest.php
│   │   ├── Broker/{Store,Update}BrokerRequest.php
│   │   ├── Load/{Store,Update,ChangeStatus,Cancel}LoadRequest.php
│   │   ├── Expense/{Store,Update}ExpenseRequest.php
│   │   ├── Account/{Store,Update}AccountRequest.php
│   │   └── Account/{Store}JournalVoucherRequest.php
│   └── Resources/
│       ├── Driver/DriverResource.php
│       ├── Broker/BrokerResource.php
│       ├── Load/LoadResource.php
│       ├── Commission/CommissionResource.php
│       └── ... (mirrors modules)
├── Models/
│   ├── User.php
│   ├── Driver.php
│   ├── Broker.php
│   ├── Load.php
│   ├── LoadStatusHistory.php
│   ├── Commission.php
│   ├── Expense.php
│   ├── Account.php
│   ├── AccountTransaction.php
│   └── JournalVoucher.php
├── Policies/
│   ├── LoadPolicy.php
│   ├── DriverPolicy.php
│   ├── BrokerPolicy.php
│   ├── AccountPolicy.php
│   └── JournalVoucherPolicy.php
├── Repositories/
│   └── Report/ReportRepository.php
└── Services/
    ├── User/UserService.php
    ├── Driver/DriverService.php
    ├── Broker/BrokerService.php
    ├── Load/
    │   ├── LoadService.php
    │   ├── LoadStatusService.php
    │   └── LoadCancellationService.php
    ├── Map/MapService.php
    ├── Commission/CommissionService.php
    ├── Expense/ExpenseService.php
    ├── Account/
    │   ├── AccountService.php
    │   └── JournalVoucherService.php
    └── Report/ReportService.php

routes/
├── web.php                 (only `require`s below — no logic here)
└── modules/
    ├── auth.php
    ├── users.php
    ├── drivers.php
    ├── brokers.php
    ├── loads.php
    ├── commissions.php
    ├── expenses.php
    ├── accounts.php
    ├── journal-vouchers.php
    └── reports.php
```

**`routes/web.php` pattern (every phase follows this — never add routes directly here):**
```php
require __DIR__.'/modules/auth.php';
require __DIR__.'/modules/users.php';
require __DIR__.'/modules/drivers.php';
require __DIR__.'/modules/brokers.php';
require __DIR__.'/modules/loads.php';
require __DIR__.'/modules/commissions.php';
require __DIR__.'/modules/expenses.php';
require __DIR__.'/modules/accounts.php';
require __DIR__.'/modules/journal-vouchers.php';
require __DIR__.'/modules/reports.php';
```

**Example module route file (`routes/modules/drivers.php`):**
```php
use App\Http\Controllers\Driver\DriverController;

Route::middleware(['auth'])->prefix('drivers')->name('drivers.')->group(function () {
    Route::get('/', [DriverController::class, 'index'])->name('index')->middleware('can:viewAny,App\Models\Driver');
    Route::get('/create', [DriverController::class, 'create'])->name('create')->middleware('can:create,App\Models\Driver');
    Route::post('/', [DriverController::class, 'store'])->name('store')->middleware('can:create,App\Models\Driver');
    Route::get('/{driver}/edit', [DriverController::class, 'edit'])->name('edit')->middleware('can:update,driver');
    Route::put('/{driver}', [DriverController::class, 'update'])->name('update')->middleware('can:update,driver');
    Route::delete('/{driver}', [DriverController::class, 'destroy'])->name('destroy')->middleware('can:delete,driver');
});
```

**Example thin controller pattern (applies to every module):**
```php
final class DriverController extends Controller
{
    public function __construct(private readonly DriverService $driverService) {}

    public function store(StoreDriverRequest $request): RedirectResponse
    {
        $driver = $this->driverService->create($request->validated());

        return redirect()->route('drivers.index')->with('success', "Driver {$driver->name} created.");
    }
}
```

**Example service pattern:**
```php
final class DriverService
{
    public function create(array $data): Driver
    {
        return Driver::create($data);
    }

    public function updateStatus(Driver $driver, DriverStatus $status): Driver
    {
        $driver->update(['status' => $status]);
        return $driver;
    }
}
```

Every phase below reuses these exact patterns for its own module — the agent should not invent a different structure per phase.

---

## Phase 0 – Project Bootstrap

**Goal:** A clean Laravel base ready for module development.

- [ ] Install Laravel (latest LTS), configure `.env`, database connection (MySQL).
- [ ] Install packages: `spatie/laravel-permission` (roles & permissions), `laravel/breeze` or `laravel/jetstream` (auth scaffold — pick Breeze for simplicity), `barryvdh/laravel-debugbar` (dev only).
- [ ] Set up `routes/web.php` to only contain `require` statements (per Section 0.2) — create empty `routes/modules/` files now so the pattern exists from day one.
- [ ] Create base `app/Enums`, `app/Services`, `app/Repositories` folders with a `.gitkeep`.
- [ ] Configure `config/permission.php` (Spatie) and run its migration.
- [ ] Set up code style tooling: `laravel/pint` for PSR-12 formatting, pre-commit hook or CI step to run `./vendor/bin/pint --test`.
- [ ] Set up base `.editorconfig`, `phpstan`/`larastan` (static analysis) at a reasonable level (level 5+).
- [ ] Create a base `ApiResponse`/`JsonResponse` helper or trait if the frontend will consume JSON (skip if pure Blade/server-rendered).

**Definition of Done:** Fresh Laravel app boots, migrations run, Pint/Larastan configured, folder skeleton exists, empty module route files exist and are required from `web.php`.

---

## Phase 1 – Users, Roles & Permissions

**Module folder:** `User`

**Database:**
- `users` (extend default: add `phone`, `status` enum active/inactive, `commission_percentage` decimal nullable — default commission %, `is_admin` boolean or rely purely on roles).
- Spatie tables: `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`.

**Roles seeded (via seeder):** `admin`, `manager`, `dispatcher`, `accountant`, `viewer`.

**Permissions seeded**, one set per module, following pattern `{module}.view`, `{module}.create`, `{module}.edit`, `{module}.delete`, `{module}.approve` — e.g. `loads.view`, `loads.create`, `loads.cancel`, `accounts.approve`, `journal-vouchers.approve`, etc. Map these to roles in the seeder per the permission matrix agreed in the requirements document.

**Build:**
- `App\Models\User` — add `hasRole`/`hasPermissionTo` (via Spatie trait), relation `commissions()`, `loads()` (as dispatcher).
- `App\Services\User\UserService` — `create()`, `update()`, `assignRole()`, `setCommissionPercentage()`, `deactivate()`.
- `App\Http\Controllers\User\UserController` — thin, calls `UserService`.
- `App\Http\Requests\User\{Store,Update}UserRequest`.
- `App\Policies\UserPolicy` — only Admin manages users.
- `routes/modules/users.php`.
- Auth scaffolding (login, password reset) via Breeze — customize to redirect based on role after login (dispatcher → their load list, accountant → accounts dashboard, admin → full dashboard).

**Definition of Done:** Admin can log in, create a Dispatcher user, assign role + default commission %, and the Dispatcher can log in and see only what their role allows (verified with a quick manual test + a Policy feature test).

---

## Phase 2 – Master Data: Drivers & Brokers

**Module folders:** `Driver`, `Broker`

**Database:**
- `drivers`: name, truck_number, trailer_number, equipment_type (enum), driver_mc_dot (nullable), phone, email (nullable), payment_details (text), status (enum: available/on_load/inactive), notes, soft deletes, timestamps.
- `brokers`: company_name, broker_mc_number (unique), contact_person, phone, email, payment_terms, credit_notes, status (enum: active/blacklisted), soft deletes, timestamps.

**Enums:** `App\Enums\EquipmentType` (dry_van, reefer, flatbed, power_only, box_truck), `App\Enums\DriverStatus`, `App\Enums\BrokerStatus`.

**Build (identical pattern for both modules):**
- Model with casts for enums, `SoftDeletes`.
- `{Module}Service` — `create()`, `update()`, `delete()` (soft), `search()` (for autocomplete/lookup used later on the Load form), `markAsAvailable()` / `markAsOnLoad()` for Driver.
- Form Requests: Store/Update, with `broker_mc_number` uniqueness rule, phone format validation.
- `{Module}Resource` — used for the JSON/autocomplete lookups the Load form will consume (Phase 3).
- `{Module}Controller` — index (list + filters), create, store, edit, update, destroy.
- `{Module}Policy` — CRUD gated by `drivers.*` / `brokers.*` permissions.
- `routes/modules/drivers.php`, `routes/modules/brokers.php`.
- Include a lightweight **search/lookup endpoint** (`GET /drivers/search?q=`, `GET /brokers/search?q=`) returning `{Module}Resource` collections — this is what the Load form's dropdowns/autocomplete will call in Phase 3. Keep this endpoint inside the same controller (`search()` method) calling `$this->driverService->search($query)` — not a raw query in the controller.

**Definition of Done:** Full CRUD on Drivers and Brokers through the UI, respecting permissions; search endpoints return correct filtered results; broker MC number uniqueness enforced.

---

## Phase 3 – Load / Dispatch Management (Core Module)

This is the heart of the system. Build Phases 2 and 1 first — this phase depends on both.

**Module folder:** `Load`

**Database:**
- `loads`: reference_number (unique, auto-generated e.g. `DEL-000123`), company_mc_number, broker_id (FK), driver_id (FK nullable until assigned), equipment_type, origin_address, origin_lat, origin_lng, destination_address, destination_lat, destination_lng, total_miles (decimal), weight_lbs, pickup_at (datetime), dropoff_at (datetime), rate (decimal), rate_per_mile (decimal, computed on save), driver_pay (decimal), dispatcher_id (FK to users), status (enum, see `LoadStatus`), cancellation_reason (nullable), cancelled_by (nullable FK users), cancelled_at (nullable), notes, soft deletes, timestamps.
- `load_status_histories`: load_id (FK), from_status, to_status, changed_by (FK users), reason (nullable), created_at.
- `load_attachments`: load_id (FK), type (enum: rate_confirmation/bol/pod/other), file_path, uploaded_by, timestamps.

**Enum:** `App\Enums\LoadStatus` — `Booked`, `Loaded`, `Dispatched`, `Paid`, `Cancelled`. Include a method `canTransitionTo(LoadStatus $next): bool` encoding the allowed forward path (`Booked→Loaded→Dispatched→Paid`) and allowing `Cancelled` from any non-final state.

**Services (split responsibilities — do not merge into one class):**
1. `LoadService` — `create()`, `update()`, `attachDocument()`, `calculateRatePerMile()` (rate ÷ total_miles, guards divide-by-zero).
2. `LoadStatusService` — `changeStatus(Load $load, LoadStatus $newStatus, User $user, ?string $reason = null)`:
   - Validates transition via `LoadStatus::canTransitionTo()`, throws a domain exception if invalid.
   - Writes a `LoadStatusHistory` row.
   - Updates `loads.status`.
   - Dispatches `LoadStatusChanged` event (and `LoadPaid` event specifically when new status is `Paid`).
3. `LoadCancellationService` — `cancel(Load $load, User $user, string $reason)`:
   - Guards: cannot cancel an already-Paid or already-Cancelled load.
   - Sets status to `Cancelled`, records `cancellation_reason`, `cancelled_by`, `cancelled_at`.
   - Writes `LoadStatusHistory` entry.
   - Dispatches `LoadCancelled` event — **all reversal logic lives in Listeners, not here** (see below).

**Events & Listeners (this is where the "cancel reverses everything" requirement is implemented — cleanly, not as a pile of if-statements in the service):**
- `Events\Load\LoadCancelled` (carries the `Load` model).
  - `Listeners\Load\ReleaseDriverOnCancellation` → sets driver status back to `available` (via `DriverService`).
  - `Listeners\Load\ReverseCommissionOnCancellation` → zeroes/reverses any `Commission` record tied to this load (via `CommissionService`, Phase 5).
  - `Listeners\Load\VoidFinancialEntriesOnCancellation` → voids any linked `JournalVoucher`/ledger entries tied to this load (via `JournalVoucherService`/`AccountService`, Phase 7).
- `Events\Load\LoadPaid` (fired when status becomes `Paid`).
  - `Listeners\Load\CalculateCommissionOnPaid` → calls `CommissionService::calculateForLoad($load)` (Phase 5 dependency — stub this listener now, implement fully once Phase 5 exists).
- Register all of the above in `App\Providers\EventServiceProvider`.

**Form Requests:**
- `StoreLoadRequest`, `UpdateLoadRequest` — validate broker/driver exist, rate/weight numeric & positive, pickup date before/equal drop-off date.
- `ChangeLoadStatusRequest` — validates `status` is a valid `LoadStatus` value.
- `CancelLoadRequest` — `reason` required, min length.

**Resource:** `LoadResource` — shapes the load with nested `broker`, `driver`, `dispatcher` summaries, computed `rate_per_mile`, `status_label`.

**Controller (`Load\LoadController`)** — thin; one method per action, each delegating to the correct service:
```php
public function changeStatus(ChangeLoadStatusRequest $request, Load $load, LoadStatusService $service)
{
    $service->changeStatus($load, LoadStatus::from($request->validated('status')), $request->user());
    return back()->with('success', 'Status updated.');
}

public function cancel(CancelLoadRequest $request, Load $load, LoadCancellationService $service)
{
    $service->cancel($load, $request->user(), $request->validated('reason'));
    return back()->with('success', 'Load cancelled and all effects reversed.');
}
```

**Policy:** `LoadPolicy` — a Dispatcher can only `view`/`update` their **own** loads (`$load->dispatcher_id === $user->id`) unless the user has an `admin`/`manager` role; `cancel` requires `loads.cancel` permission (optionally reserve final cancel-approval for Admin if the business wants an extra control).

**routes/modules/loads.php** — standard CRUD + `POST /loads/{load}/status`, `POST /loads/{load}/cancel`, `POST /loads/{load}/attachments`.

**Definition of Done:** A load can be created, walked through Booked→Loaded→Dispatched→Paid with full history logged; cancelling at any stage releases the driver, zeroes commission, and voids financial entries (verified with feature tests simulating cancellation from each stage).

---

## Phase 4 – Map Integration

**Module folder:** `Map` (service-only, no dedicated controller — consumed by the Load module's frontend + `LoadService`)

- [ ] Choose provider: Google Maps JavaScript API + Distance Matrix/Directions API (requires billing-enabled API key) — confirm key with the business before building (cost implication).
- [ ] `App\Services\Map\MapService`:
  - `geocode(string $query): array` — returns lat/lng/formatted address for a search string.
  - `calculateDistance(float $originLat, float $originLng, float $destLat, float $destLng): float` — returns driving miles.
- [ ] Frontend: Origin/Destination fields on the Load form become a map-picker component (autocomplete search box + pin drop) that, on selection, fills the hidden `origin_lat/lng`, `destination_lat/lng`, and address fields, then calls the backend (`POST /loads/calculate-distance`) which uses `MapService::calculateDistance()` and returns miles to auto-fill `total_miles`.
- [ ] Add a single route for this in `routes/modules/loads.php` (it's a Load-form utility endpoint, not a separate module): `POST /loads/calculate-distance` → `LoadController::calculateDistance()` → `MapService`.
- [ ] Cache/log API responses minimally to avoid hitting rate limits during testing (config flag to use a stub `MapService` in local/testing env so tests don't hit the real API).

**Definition of Done:** Selecting origin & destination on the map auto-fills addresses and total miles on the Load form without a page reload.

---

## Phase 5 – Commission / Margin Module

**Module folder:** `Commission`

**Database:**
- `commissions`: load_id (FK, unique — one commission record per load), dispatcher_id (FK users), margin_amount (decimal), commission_percentage (decimal, snapshot at calculation time), commission_amount (decimal), status (enum: accrued/reversed), calculated_at, reversed_at (nullable), timestamps.

**Build:**
- `App\Services\Commission\CommissionService`:
  - `calculateForLoad(Load $load): Commission` — margin = `rate - driver_pay - linkedExpensesSum($load)`; commission = `margin * dispatcher.commission_percentage` (or load-level override if present); creates/updates the `Commission` row; called from `Listeners\Load\CalculateCommissionOnPaid` (Phase 3).
  - `reverseForLoad(Load $load): void` — marks the related `Commission` row `reversed`, zeroes it out of active totals; called from `Listeners\Load\ReverseCommissionOnCancellation` (Phase 3).
  - `totalForUser(User $user, ?Carbon $from, ?Carbon $to): array` — used by Reports (Phase 8).
- `CommissionController` — read-only views: a dispatcher sees only their own commission history; Admin/Manager see everyone's.
- `CommissionPolicy` — `viewAny` scoped so non-admins only ever see their own rows (enforce in the Service's query scope, not just the Policy, so it can't be bypassed).
- `routes/modules/commissions.php`.

**Definition of Done:** Marking a load Paid creates a correct Commission record; cancelling a Paid-then-reverted (or earlier-stage) load reverses it to zero; a Dispatcher's commission report only ever shows their own data.

---

## Phase 6 – Expense Module

**Module folder:** `Expense`

**Database:**
- `expenses`: expense_date, category (enum: fuel/tolls/repairs_maintenance/office/software_subscriptions/salaries/other), amount, paid_via (enum: cash/bank → references an `account_id` FK), load_id (nullable FK — link to a specific load), attachment_path (nullable), entered_by (FK users), notes, timestamps.

**Build:**
- `App\Enums\ExpenseCategory`.
- `App\Services\Expense\ExpenseService` — `create()` (also posts a corresponding debit transaction against the chosen Account via `AccountService::recordTransaction()` — see Phase 7), `update()`, `delete()` (reverses the linked account transaction), `totalsByCategory()`, `totalsForLoad(Load $load)` (feeds into Commission margin calc if you choose to net expenses into margin).
- Standard Form Requests, Resource, Controller, Policy, `routes/modules/expenses.php`.

**Definition of Done:** Creating an expense against a Bank/Cash account correctly reduces that account's balance (via Phase 7's ledger); deleting an expense reverses the transaction.

---

## Phase 7 – Accounts Module (Cash/Bank) + Journal Voucher

**Module folders:** `Account` (covers both Accounts and Journal Vouchers, kept as two controllers/services under one folder since they're tightly related)

**Database:**
- `accounts`: name, type (enum: cash/bank), opening_balance, current_balance (kept in sync, never trust a "live SUM query" alone — update on every transaction), status (active/inactive), timestamps.
- `account_transactions`: account_id (FK), type (debit/credit), amount, running_balance (snapshot after this transaction), source_type (polymorphic: `expense`, `journal_voucher`, `manual`), source_id, description, created_by, timestamps.
- `journal_vouchers`: jv_number (auto, unique), date, debit_account_id (FK accounts), credit_account_id (FK accounts), amount, narration, attachment_path (nullable), approved_by (nullable FK users), status (enum: pending/approved/voided), load_id (nullable — for reversals tied to a cancelled load), timestamps.

**Build:**
- `App\Services\Account\AccountService`:
  - `create()`, `update()`.
  - `recordTransaction(Account $account, string $type, float $amount, string $sourceType, int $sourceId, string $description)` — the **single choke point** every other module (Expense, JournalVoucher, Commission payouts) must go through to touch a balance. Updates `current_balance` and inserts an `account_transactions` row with the correct `running_balance`. No other code is allowed to modify `current_balance` directly.
  - `ledgerFor(Account $account, ?Carbon $from, ?Carbon $to)`.
- `App\Services\Account\JournalVoucherService`:
  - `create()` — status `pending` until approved (if using the Approve permission), on approval calls `AccountService::recordTransaction()` twice (debit one account, credit the other).
  - `approve(JournalVoucher $jv, User $approver)`.
  - `voidForLoad(Load $load)` — used by `Listeners\Load\VoidFinancialEntriesOnCancellation` (Phase 3) to void any JV tied to a cancelled load, reversing its transactions via `AccountService::recordTransaction()` with the opposite sign.
- Standard Form Requests/Resources/Policies for both Account and JournalVoucher.
- `routes/modules/accounts.php`, `routes/modules/journal-vouchers.php`.

**Definition of Done:** Account balances always match the sum of their transaction ledger; a JV only posts after approval (if that flow is enabled); voiding a JV correctly reverses the balances it affected.

---

## Phase 8 – Reports Module

**Module folder:** `Report`

This is the one module allowed a **Repository** layer, because report queries are complex, reused across multiple report endpoints, and benefit from being isolated from the Service layer's business-logic responsibilities.

**Build:**
- `App\Repositories\Report\ReportRepository` — raw/optimized query methods: `marginByUser()`, `loadsByFilters()`, `cancelledLoads()`, `driverSettlement()`, `expensesByCategory()`, `accountStatement()`, `profitAndLoss()`, `brokerPerformance()`. Each takes a simple filter DTO/array (date range, user_id, broker_id, driver_id) and returns collections/arrays — no HTTP concerns here.
- `App\Services\Report\ReportService` — thin orchestration over the Repository (adds formatting, percentage calculations, export prep); this is what the Controller calls.
- `ReportController` — one method per report, each: validates filters (a `ReportFilterRequest`), calls `$this->reportService->{report}()`, returns a view or an Excel/PDF export (use `maatwebsite/excel` for Excel export, `barryvdh/laravel-dompdf` or similar for PDF).
- `ReportPolicy` — Dispatchers can only ever request reports scoped to themselves (`user_id` forced to their own ID server-side, never trust a `user_id` filter from the request for non-admins).
- `routes/modules/reports.php`.

**Definition of Done:** All 8 reports listed in the requirements document (User-wise Margin, Load Report, Cancelled Loads, Driver Settlement, Expense Report, Account Statement, P&L, Broker Performance) are available, filterable, exportable, and correctly scoped by role.

---

## Phase 9 – Dashboard, Notifications & Polish

- [ ] Role-aware dashboard (`DashboardController`, calling a `DashboardService` that aggregates: active loads by status, this month's margin, pending settlements — reuse `ReportService`/`CommissionService`, don't duplicate queries).
- [ ] Laravel Notifications (email + database/in-app) for: load booked, status changed, load cancelled, payment received — dispatch these from the existing Events (Phase 3) via new Listeners, so no controller changes are needed.
- [ ] File storage cleanup: confirm attachments (Rate Con/BOL/POD, expense receipts, JV attachments) are stored on a proper disk (S3 or local `storage/app/public` with correct `.gitignore`), never in the repo.
- [ ] Final pass: run Larastan + Pint across the whole codebase, fix violations.
- [ ] Write feature tests (PHPUnit/Pest) for the critical paths: full load lifecycle happy path, cancellation-at-each-stage reversal, commission calculation, account balance integrity after expense + JV.

**Definition of Done:** App is demo-ready end-to-end for a non-technical stakeholder walkthrough.

---

## 10. Suggested Build Order Summary

```
Phase 0  → Project bootstrap & standards
Phase 1  → Users, Roles & Permissions
Phase 2  → Drivers & Brokers (master data)
Phase 3  → Load / Dispatch (core) + status workflow + cancellation events
Phase 4  → Map integration (plugs into Phase 3's Load form)
Phase 5  → Commission / Margin (plugs into Phase 3's Paid/Cancelled events)
Phase 6  → Expenses (plugs into Phase 7's accounts)
Phase 7  → Accounts + Journal Vouchers (plugs into Phase 3's cancellation reversal)
Phase 8  → Reports (reads from everything above)
Phase 9  → Dashboard, notifications, polish, tests
```

Each phase should end with a short demo/checklist review before moving to the next — do not let phases blend into each other, especially Phase 3, since almost every later module depends on its Events being correctly designed.
