# Logistics SaaS

Multi-tenant dispatch and brokerage software for truck-dispatch companies (built around the Pakistan-based
dispatcher serving US carriers). One database, many companies, every row isolated by `tenant_id`.

* **Landing page** (`/`): pricing from the plans table and a "request access" form.
* **Super admin** (`/super-admin`): plans, access requests, workspaces (approve, trial/payment, suspend,
  enter a workspace), platform audit log, MRR and conversion dashboard.
* **Workspace** (each customer): loads, carriers, drivers, brokers, invoices, receipts and USD to PKR
  conversion, expenses, accounts, transfers, journal vouchers, commissions, reports, roles and settings.

How the business works and what each module is for: [docs/BUSINESS_FLOW_AND_PLAN.md](docs/BUSINESS_FLOW_AND_PLAN.md).

## Setup

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
# create the database named in .env (DB_DATABASE), then:
php artisan migrate --seed          # roles, plans, super admin, one demo workspace
php artisan db:seed --class=DemoDataSeeder   # optional: sample loads, payments, expenses
npm run build                       # or: npm run dev
php artisan serve
php artisan queue:work              # emails (welcome, request alerts) are queued
```

Logins (change after first sign-in):

| Who | Email | Password |
|---|---|---|
| Super admin | superadmin@logistics.test | password123 |
| Demo workspace admin | admin@dreamelectlogistics.com | password |
| Demo dispatcher | dispatcher@dreamelectlogistics.com | password |

Production: run `php artisan db:seed --class=ProductionSeeder` (roles, plans, super admin only) and
schedule `php artisan schedule:run` every minute (nightly `tenants:sweep` locks expired trials/subscriptions).

## How multi-tenancy works

* Every business table has `tenant_id` as the first column of its indexes. Models use the
  `BelongsToTenant` trait: a global scope filters reads and new rows are stamped automatically.
  `tenant_id` is never mass-assignable.
* `ResolveTenant` middleware fixes the tenant from the signed-in user and **fails closed** (no workspace,
  suspended or expired means a locked page). The super admin only reaches platform screens unless they
  enter a workspace.
* Validation `exists`/`unique` rules must use `TenantRule`, never `Rule::exists/unique`, for tenant tables.
* Roles: the four standard roles are shared (Spatie "teams", `team_id` null); a company's custom roles
  are private to it. Rights, not role names, drive every check (`loads.view-all`, `settings.manage`...).
* Files are stored under `tenants/{id}/...`; settings are one JSON row per (tenant, group).
* Plan limits (users, drivers, loads per month) are enforced on model `creating`.


## What each company can customise

Under **Business settings** (needs the `settings.manage` right); everything is stored per company.

| Area | Options |
|---|---|
| Branding | logo, accent and sidebar colours (whole app, charts, invoices, report PDFs), invoice layout |
| Numbering | load prefix / digits / first number, invoice prefix, voucher prefix / digits (gap-free per-company counters) |
| Regional | date format, week start, financial-year start, local and client currency symbols |
| Load rules | which optional details become compulsory |
| Dashboard | which sections show, revenue chart range |
| Notifications | which events also email, subject prefix and sign-off, alert overseers too |
| Custom fields | extra text / number / date / dropdown / yes-no fields on loads, drivers and carriers (can show as list columns) |
| Tax rules | named taxes picked on a USD to PKR conversion |
| Commission | flat per dispatcher, or marginal slabs that rise with monthly margin |
| Expense categories, equipment types, timezones, roles | managed from the same hub |

New settings are one entry in `config/tenant-settings.php`; no controller or view needed.

## Import, export, saved reports

* **Import** (`Import & Export`, needs `data.import`): CSV or Excel for carriers, brokers, drivers and past loads.
  Download a template, upload, map columns, review every row, then import; rejected rows come back as a CSV.
  Imports are history only: they never create commissions, invoices or ledger entries.
* **Export** (needs `data.export`): a queued full backup, a ZIP of one CSV per area (no passwords).
  Backups are deleted after seven days.
* **Saved reports**: save any report with its filters; optionally email it weekly or monthly as CSV or PDF.
  The scheduler runs `reports:send-scheduled` hourly; each report is produced as the person who saved it.

## Scaling notes

* Use Redis for cache, session and queue in production (`CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION`).
  The tenant row and the dashboard money block are cached; the settings table is read once per request.
* Report and list queries are tenant-leading index scans; add read replicas before sharding.
* Keep `php artisan queue:work` supervised; mail never blocks a request.

## Tests

`php artisan test` runs against MySQL (`logistics_saas_test`, see `phpunit.xml`): tenant isolation,
platform flows, money/ledger reconciliation, settings, and a role-by-route permission matrix.
