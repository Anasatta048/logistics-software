# Business flow & build plan

## 1. How a Pakistan-based truck-dispatch company actually runs

The company sits in Pakistan and dispatches trucks for **US carriers** (owner-operators and small fleets).

```
Carrier company (US, has an MC#)  ──hires──▶  Dispatch company (Pakistan)
        │                                            │
        │ owns/employs                               │ employs
        ▼                                            ▼
     Drivers + trucks                          Dispatchers (agents)
        ▲                                            │ find freight on load boards
        │ hauls freight                              ▼
        └────────────  Load  ◀──── rate confirmation ──── Broker (US, has an MC#)
```

1. **Setup** – add the carrier *company* (agreed service fee %, e.g. 5–10 % of each load), its *drivers*
   (truck, trailer, equipment type) and the *brokers* it will haul for.
2. **Book a load** – a dispatcher finds a load, agrees the rate with the broker, assigns a driver.
   Load = origin → destination, miles, rate, pickup/drop-off, equipment. Status: **Booked → Picked up → Loaded → Delivered → Paid** (or **Cancelled**, e.g. TONU).
3. **Earn** – when the load is **Paid**, the company earns its **service fee** (`rate × fee %`).
   The dispatcher earns a **commission** on that fee (a percentage or a fixed amount) – paid in PKR.
4. **Bill** – every week/fortnight the company issues a **driver-wise invoice** to the carrier for its loads
   (fee + any previous balance − any advance/credit).
5. **Get paid** – the carrier pays in **USD** (Zelle / ACH / wire / Payoneer / Wise). The receipt is recorded
   against the invoice (part-payments and advances allowed).
6. **Convert** – USD is withdrawn to a Pakistani bank at a **rate**, minus **tax / bank charges**, and lands
   in a **PKR account** (bank or cash). Until then the money is "USD received, not yet converted".
7. **Pay out** – dispatcher commissions are paid (usually monthly), and the office **expenses** are paid
   (salaries, rent, electricity, internet, load-board subscriptions such as DAT/Truckstop, software, ads, tax, bank charges).
8. **Know the numbers** – receivables per carrier, profit & loss, cash position per account, who owes what.

### Where money moves (the part the books must show)

| Money in | → lands in |
|---|---|
| Carrier pays invoice (USD) | *USD received* → converted → **PKR account** (conversion = rate, tax, charges) |
| Transfer from another own account | **PKR account** |
| Manual / journal entry | any account |

| Money out | ← leaves |
|---|---|
| Expense (salary, rent, subscription…) | the **account chosen** on the expense |
| Dispatcher commission payout | the **account chosen** on the payout |
| Transfer to another own account | the source account |
| Journal entry | any account |

Every movement is one row in the **account ledger** with its source (expense / receipt / payout / transfer / journal).
The **Funds Flow report** answers "kahan se aaya, kahan gaya": for any period, per account, opening + in − out = closing,
with each line traced to its document and counterparty.

## 2. What already exists vs. what this plan adds

| Area | Exists | This plan adds |
|---|---|---|
| Loads, companies, drivers, brokers, invoices, receipts, conversions, commissions, payouts | yes | – |
| Accounts + ledger + journal vouchers | yes | **Transfers between accounts**, ledger rows **link to their source**, **Funds Flow** report, per-account **cash position** |
| Expenses | fixed category list | **Per-company categories**, payee + reference, **expense reports** (by category / month / account / payee, trend, top payees) |
| Settings | profile, timezone, invoice details | **Dynamic settings hub**: business profile, finance defaults (due days, invoice start no., default fee %, FX hint), expense categories, payment methods, notification defaults |
| Roles & rights | role-name checks in places | **Everything permission-driven**: `settings.manage`, `activity-logs.view`, `loads.view-all`, `accounts.transfer`, … custom roles behave exactly like standard ones; role matrix **tested** |
| Dashboard | loads-centric | **Business dashboard**: revenue / fee / expenses / profit, receivables, cash position, unconverted USD, commission payable, trends, alerts, onboarding checklist, plan usage & trial banner |

## 3. Build order (each phase is committed and pushed)

1. **Rights & settings foundation** – permission-driven gates/scoping; settings registry + hub.
2. **Expenses** – dynamic categories, payee/reference, expense reports.
3. **Accounts** – transfers, source-linked ledger, Funds Flow report, cash position.
4. **Tenant dashboard** – business KPIs, alerts, onboarding, plan usage.
5. **Verification** – role-matrix tests, isolation tests, full smoke run.

## 4. Roles (standard set shipped with every workspace)

| Role | Can do |
|---|---|
| **Admin** | everything, incl. settings, roles, users, activity log, company commission |
| **Dispatcher** | book/move/cancel/pay *own* loads, see own commission, add drivers/brokers |
| **Accountant** | all money screens (receipts, invoices, expenses, accounts, transfers, vouchers), view loads |
| **Viewer** | read-only everywhere |

Custom roles are built from the same rights, so any combination works. "Own loads only" is itself a right
(`loads.view-all` removes it), not a role name.
