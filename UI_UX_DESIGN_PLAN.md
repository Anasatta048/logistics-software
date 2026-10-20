# Dreamelect Logistics – Dispatch System
## UI/UX Design Plan (Frontend Reference for Developer)

> **Reference used:** Layout/UX structure inspired by the Florenza inventory dashboard (sidebar, summary cards, charts, tables). Colors/branding taken from Dreamelect Logistics' own website (dreamelectlogistics.com) — navy/orange/gold, not Florenza's pink.
> **Golden rule:** The theme is defined **once, globally** (design tokens / CSS variables / Tailwind config). No page or form ever hardcodes a color, radius, shadow, or font size — everything pulls from the shared token set. This is what makes "same theme system-wide" actually true instead of just a guideline.

---

## 1. Design Tokens (Single Source of Truth)

All of the below live in **one config file** (e.g. `tailwind.config.js` `theme.extend.colors` + a `:root { --var }` CSS file). Every component, every module, every form references these tokens — never a raw hex code typed inside a Blade/component file.

### 1.1 Brand Colors

| Token name | Hex | Usage |
|---|---|---|
| `brand-primary` | `#E8622C` (Dreamelect orange) | Primary buttons, active sidebar/topbar item, links, primary CTA |
| `brand-navy` | `#1B2A4A` | Sidebar background (dark mode of layout), headings, footer |
| `brand-gold` | `#C9972E` | Secondary accents, highlighted numbers, dividers |
| `brand-navy-light` | `#3A5A8C` | Secondary buttons, info elements |

### 1.2 Semantic / Status Colors

| Token name | Hex | Usage |
|---|---|---|
| `status-booked` | `#1B2A4A` (navy) | "Booked" badge/card |
| `status-loaded` | `#3A5A8C` (navy-light) | "Loaded" badge/card |
| `status-dispatched` | `#C9972E` (gold) | "Dispatched" badge/card |
| `status-paid` | `#2E7D32` (green) | "Paid" badge/card, positive figures |
| `status-cancelled` | `#C0392B` (red) | "Cancelled" badge/card, negative figures |
| `warning` | `#D97706` | Low-priority alerts (e.g. pickup approaching) |
| `danger` | `#C0392B` | Errors, overdue items |

### 1.3 Neutrals

| Token name | Hex | Usage |
|---|---|---|
| `surface` | `#FFFFFF` | Card/panel background (light mode) |
| `bg` | `#F7F7F9` | Page background (light mode) |
| `surface-dark` | `#111827` | Card/panel background (dark mode) |
| `bg-dark` | `#0B0F19` | Page background (dark mode) |
| `border` | `#E5E7EB` | Card/table borders |
| `text-primary` | `#1F2333` | Headings, main text |
| `text-muted` | `#6B7280` | Secondary text, labels |

### 1.4 Typography, Radius, Shadow (tokens too — not per-page choices)

- Font: **Inter** or **Manrope** (clean, matches the modern feel of both reference sites) — one font family for the whole app.
- Radius: `--radius-card: 14px`, `--radius-button: 10px`, `--radius-input: 8px` — consistent rounded look everywhere, matching the soft rounded cards seen in the Florenza reference.
- Shadow: one soft card shadow token (`--shadow-card: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04)`), reused on every card/table/modal.
- Both **Light Mode** and **Dark Mode** token sets must be defined from day one (the reference UI has a light/dark toggle in the topbar — see Section 5).

---

## 2. Layout Shell

### 2.1 Structure (matches the Florenza reference)
- **Left Sidebar** (collapsible via the toggle icon top-left, same as reference): logo + app name at top, grouped nav sections with section labels (uppercase, muted), active item highlighted with `brand-primary` background tint + colored left border.
  - **Dashboard**
  - **OPERATIONS**: Loads, Drivers, Brokers
  - **ACCOUNTS**: Cash/Bank Accounts, Expenses, Journal Vouchers
  - **REPORTS**: (single link opening the reports hub)
  - **ADMIN** *(admin-only)*: Users & Roles, Settings
- **Top Bar**: sidebar-collapse icon, current section title (left), and on the right: notifications bell (badge count), light/dark mode toggle, user avatar + name + dropdown (Profile, Settings, Logout). No branch/company switcher needed unless the business later expands to multiple branches — if so, reuse the same dropdown pattern as the reference's "Mall of GRW" selector.
- **Content area**: `bg` background, cards/tables/forms floating on it with `surface` background and the shared card shadow/radius tokens.
- **Small helper widget** bottom-left of sidebar (optional, like the reference's "Cloud storage" box) — repurposed here as a quick tip or "This month's margin so far" mini-stat, collapsible, not critical.

### 2.2 Layout Preference: Topbar vs Sidebar Navigation (User-Configurable)
Per your request, the system supports **both** navigation styles, switchable per-user from **Settings → Appearance**:

- **Sidebar mode (default)** — left vertical nav as described in 2.1.
- **Topbar mode** — the same nav items collapse into a horizontal menu bar under the header (dropdowns for grouped sections like OPERATIONS/ACCOUNTS), and the content area becomes full-width.

**Implementation approach:**
- One `AppLayout` shell component reads a `nav_position` preference (`sidebar` | `topbar`) from the logged-in user's settings (stored in `users.nav_position` or a `user_preferences` table).
- The actual nav **items/links are defined once**, in a single shared nav config/array (icon, label, route, permission-gate) — both the Sidebar component and the Topbar component simply render that same list differently. This avoids maintaining two separate, drifting menus.
- Toggling the setting updates the preference via an instant AJAX call and re-renders the shell — no full page reload needed.
- Both modes use the exact same design tokens (colors, radius, shadow) — only the arrangement changes, never the theme.

---

## 3. Dashboard Page

Mirrors the reference's dashboard rhythm: greeting → KPI cards → attention panel → charts.

1. **Greeting bar**: "Good [morning/afternoon/evening], {user's first name}" with a small icon, subtitle like "Here's how dispatch is moving today."
2. **KPI Cards row** (4 cards, colored fill like the reference, each with an icon top-right and a "% vs yesterday/last period" line):
   - **Today's Bookings** (count)
   - **Active Loads** (Booked+Loaded+Dispatched count)
   - **This Month's Margin** (Rs / $ amount, from Commission module)
   - **Pending Settlements** (Rs amount owed to drivers on unpaid-but-delivered loads)
   - Color mapping: use `brand-primary`, `brand-navy-light`, `status-paid` (green), `brand-gold` for these four cards respectively — consistent every time, not randomized.
3. **"Needs Attention" panel** (equivalent of the reference's "Low stock" panel): loads with pickup overdue, loads stuck in one status too long, or drop-off date passed without moving to Paid. Empty state message in the same friendly tone as the reference ("Nothing needs attention right now 🎉").
4. **Charts row** (two side-by-side, same rhythm as reference: one line/area chart + one donut):
   - **Left — "Revenue & Margin — last 14 days"**: dual-line/area chart (Revenue line + Margin line), smooth curved lines, soft gradient fill under the primary line (same visual style as the reference chart), small "This period: Rs X" badge top-right of the card, x-axis = dates.
   - **Right — "Loads by Status"**: donut chart, segments colored using the **status tokens** from Section 1.2 (Booked=navy, Loaded=navy-light, Dispatched=gold, Paid=green, Cancelled=red), center shows total load count for the period, legend below with colored dots + counts (matching the reference's payment-methods donut layout).
5. Role scoping: a Dispatcher's dashboard is auto-filtered to their own loads/margin; Admin/Manager see company-wide totals with an optional "filter by dispatcher" control.

### Chart Library & Consistency Rule
- Use **one charting library app-wide** (e.g. ApexCharts or Chart.js) — never mix libraries across pages.
- Define a single "chart theme" config (colors pulled from the design tokens, font, gridline style, tooltip style) once, and every chart on every page (Dashboard, Reports) imports that same config. A chart on the Reports page must look like a sibling of the Dashboard chart, not a different tool entirely.
- All charts get the same card treatment: title top-left, optional period badge top-right, generous padding, no harsh borders — matching the soft, airy feel of the reference dashboard.

---

## 4. Module Screens — Consistency Rules

These apply identically to Loads, Drivers, Brokers, Expenses, Accounts, Journal Vouchers, Users:

- **List view**: Page header (title + short description line, like the reference's "Point of Sale / Pick a product..." pattern) + a primary action button top-right (`+ New Load`, `+ New Driver`, etc.) in `brand-primary`.
- **Filters bar**: sits directly under the header, pill-style dropdowns/search (status, date range, dispatcher, broker) — same input styling as the rest of the app.
- **Table**: consistent column padding, muted header row (`text-muted`, uppercase, small), zebra-free (clean white rows with a bottom hairline border, like the Florenza table), status shown as a colored pill badge (never plain text), row-hover subtle background tint, action icons (view/edit/delete) right-aligned.
- **Forms** (Create/Edit — e.g. the Load form, Expense form): use the same input, select, date-picker, and button components everywhere. A two-column responsive grid for forms with many fields (like the Load form), single-column for simple ones (like Broker). Section grouping with subtle dividers when a form is long (e.g. Load form: "Route & Load Info" → "Schedule & Rate" → "Assignment" → "Documents").
- **Point-of-Sale-style split screen** (borrowed from the reference's POS layout) is reused specifically for the **Load creation form**: left side = the load details form (route, weight, dates, rate), right side = a sticky "Summary" panel showing live-calculated Rate/Mile, estimated margin, and a prominent primary button at the bottom ("Save & Book Load"), exactly like the reference's Checkout panel with its bold total + button.
- **Empty states** everywhere use the same friendly, illustrated-icon-plus-text pattern as the reference ("Nothing below threshold 🎉") rather than a bare "No data."
- **Loading states**: skeleton shimmer placeholders (card-shaped and row-shaped grey blocks pulsing) shown while data fetches — no generic spinner-only screens — matching how the reference dashboard feels instant and modern.

---

## 5. Login Page (New Design)

Split-screen layout:

- **Left panel (branding side, ~45% width)** — `brand-navy` background:
  - Dreamelect Logistics logo (light version) top-left.
  - Large headline echoing the site's own hero copy: *"Reliable Loads, Anytime You Need."*
  - Tagline underneath: *"We Drive Your Dreams!"*
  - Subtle background treatment: a darkened/overlaid version of the truck-on-bridge hero image from the website, or a simple road-line graphic motif in `brand-gold` at low opacity — kept subtle so it doesn't compete with the form side.
  - Small trust line near the bottom: e.g. "Trusted dispatch operations, one load at a time."
- **Right panel (form side, ~55% width)** — `surface`/white background:
  - Small logo mark (icon-only) above the form on smaller screens.
  - "Welcome back" heading + "Log in to your dispatch dashboard" subtext.
  - Email field, Password field (with show/hide toggle), "Remember me" checkbox + "Forgot password?" link inline.
  - Primary **Log In** button, full-width, `brand-primary` fill, rounded per the button token.
  - On submit: button shows an inline spinner + "Signing you in…" state (button stays same size, no layout jump).
  - Error state: a soft red banner above the form ("Invalid email or password"), not a harsh browser alert.
  - Footer micro-text: "© Dreamelect Logistics LLC · dreamelectlogistics.com"
- Fully responsive: on mobile, the branding panel collapses to a short top banner (logo + tagline only) and the form takes the full remaining screen.

---

## 6. Settings Page

- **Appearance section**:
  - **Navigation style**: Sidebar / Topbar toggle (Section 2.2) — shown as two small illustrated preview cards (a mini sidebar-layout thumbnail vs a mini topbar-layout thumbnail) the user clicks to choose, not a plain dropdown — makes the choice visual and obvious.
  - **Theme mode**: Light / Dark / System — same toggle style as the topbar sun/moon icon, just exposed here too for discoverability.
- **Profile section**: name, phone, password change.
- **Notifications section**: toggle which events trigger email/in-app alerts (load booked, status changed, cancelled, payment received — tied to the Events built in the backend plan).
- Admin-only additional tabs: **Users & Roles**, **Commission Defaults**, **Company Info** (MC number, logo, contact info shown on documents/reports).

---

## 7. Component Inventory (Build Once, Reuse Everywhere)

A shared component library must exist before module screens are built, so nothing is redesigned per-page:

| Component | Notes |
|---|---|
| `Button` | Variants: primary, secondary, ghost, danger — all pull from color tokens, consistent height/radius |
| `Card` | Base container for every panel/KPI/chart wrapper |
| `Badge` | Used for load status, account type, etc. — color prop maps to the semantic tokens in 1.2 |
| `Table` | Header style, row style, pagination footer — one implementation |
| `Input / Select / DatePicker / FileUpload` | Shared form fields, consistent focus ring in `brand-primary` |
| `Modal / Drawer` | For quick actions (e.g. "Change Status", "Cancel Load" confirmation with reason field) |
| `Toast/Alert` | Success/error/warning banners, consistent placement (top-right) |
| `EmptyState` | Icon + message + optional CTA, reused across every list view |
| `SkeletonLoader` | Card-skeleton and row-skeleton variants for loading states |
| `ChartCard` | Wraps any chart (line/donut/bar) with the standard title/period-badge/padding treatment |
| `AppShell` | The layout wrapper that renders either Sidebar-mode or Topbar-mode based on user preference (Section 2.2), from the same nav config |

**Rule for the developer:** before building any module screen, check this component list first — if a component already exists, reuse it; never create a one-off styled element inside a single module's form or page.

---

## 8. Icons

- One icon set for the whole app — **Lucide** or **Heroicons**, outline style, matching the minimal look in the Florenza reference (thin-stroke icons, not filled/glyph style).
- Icon sizing standardized: 16px (inline/table actions), 20px (nav items), 24px (KPI card icons).

---

## Summary for the Developer

1. Build the design-token file (colors, radius, shadow, typography) **first**, before any screen — this file is the only place color values are ever written.
2. Build the shared component library (Section 7) second.
3. Build `AppShell` with both Sidebar and Topbar render modes reading from one nav config (Section 2.2).
4. Build the Login page (Section 5).
5. Build the Dashboard (Section 3), including the chart theme config (Section 3's "Chart Library & Consistency Rule") — this chart config will be reused by every future report chart.
6. Then build each module screen (Loads, Drivers, Brokers, Expenses, Accounts, JV, Reports, Users) strictly using the components and tokens already established — no new colors, no new button styles, no new table styles introduced along the way.
