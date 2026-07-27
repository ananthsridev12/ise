# ISE — Full App Design Reference

A complete design reference for the ISE (Intent Signal Engine) PHP app. Use this to replicate the same architecture, page structure, UI patterns, and KB module design in another app.

---

## Stack

| Layer | Choice |
|---|---|
| Language | PHP 8.1 |
| Database | MySQL 5.7 |
| Frontend | Vanilla JS — no framework |
| CSS | Single file `assets/css/app.css` — custom design system |
| Auth | Session-based, httpOnly cookie, bcrypt passwords |
| AI | Gemini / Claude / OpenAI — user-configurable per tenant |
| Hosting | cPanel shared hosting, deploy via `php-app` branch |

---

## Folder Structure

```
/
├── sql/                          ← one file per DB migration, sequential
│   ├── 001_initial_schema.sql
│   ├── 002_knowledge_base.sql
│   ├── 003_email_threads.sql
│   ├── 004_auth.sql
│   └── 006_generation_mode.sql
├── lib/                          ← PHP classes, no framework
│   ├── DB.php                    ← PDO wrapper
│   ├── Auth.php                  ← session, roles, tenant isolation
│   ├── KBMatcher.php             ← signal → service matching (deterministic)
│   ├── AIEmailDrafter.php        ← AI prompt assembly + API calls
│   ├── EmailGenerator.php        ← shared generate logic (lite + full mode)
│   ├── NewsFetcher.php
│   ├── TechExtractor.php
│   ├── Scorer.php
│   └── EmailDrafter.php          ← template fallback (no AI)
├── api/                          ← JSON endpoints
│   ├── enrich.php
│   ├── upload.php
│   ├── extract_tech.php
│   ├── kb.php                    ← CRUD for all KB entities
│   ├── kb_import.php             ← CSV import
│   ├── kb_template.php           ← CSV template download
│   ├── generate_email.php
│   ├── email.php
│   ├── companies.php
│   └── test_ai.php
├── platform/                     ← platform super-admin (separate auth)
│   ├── setup.php                 ← one-time super-admin creation
│   ├── login.php
│   ├── logout.php
│   ├── tenants.php               ← create + manage tenants
│   └── users.php                 ← manage users per tenant
├── admin/                        ← tenant admin only
│   └── users.php                 ← team management + KB access assignment
├── assets/
│   └── css/app.css               ← entire design system
├── docs/                         ← design docs
├── config.php                    ← loads config.local.php, DB, Auth, session
├── config.local.php              ← credentials (never in git)
├── layout.php                    ← HTML head + sidebar + auth guard
├── layout_end.php                ← closes main + body
├── install.sql                   ← all tables combined (fresh install)
├── index.php
├── companies.php
├── detail.php
├── upload.php
├── outreach.php
├── knowledge.php
├── settings.php
├── login.php
├── logout.php
├── seed_admin.php                ← one-time first-user creation (delete after use)
└── .cpanel.yml
```

---

## Design System (`assets/css/app.css`)

All CSS lives in one file. Never use inline styles for colours or spacing — use CSS variables and existing classes.

### CSS Variables

```css
:root {
  --bg:           #0f1117;   /* page background */
  --card:         #1a1d27;   /* card / sidebar background */
  --border:       #2a2d3a;   /* all borders */
  --text:         #e8eaf0;   /* primary text */
  --muted:        #6b7280;   /* secondary text, labels */
  --accent:       #6366f1;   /* indigo — primary interactive */
  --accent-hover: #4f52d1;
  --success:      #10b981;   /* green */
  --warning:      #f59e0b;   /* amber */
  --danger:       #ef4444;   /* red */
  --sidebar-w:    220px;
}
```

### Layout Classes

| Class | What it does |
|---|---|
| `.main` | Page content area (margin-left: sidebar width) |
| `.page-header` | Flex row: title left, actions right |
| `.page-title` | 20px bold heading |
| `.page-sub` | 13px muted subtitle |
| `.card` | White/dark card container |
| `.card-body` | 20px padding inside card |
| `.metric-grid` | Auto-fit grid of metric cards |
| `.metric-card` | Single stat card |
| `.metric-value` | Big number — add `.high`, `.medium`, `.low` for colour |
| `.metric-label` | 11px uppercase label above number |

### Table Classes

```html
<div class="card">
  <div class="table-wrap">          <!-- overflow-x: auto -->
    <table>
      <thead><tr><th>...</th></tr></thead>
      <tbody><tr><td>...</td></tr></tbody>
    </table>
  </div>
</div>
```

### Badge Classes

```html
<span class="badge badge-high">High</span>      <!-- green -->
<span class="badge badge-medium">Medium</span>   <!-- amber -->
<span class="badge badge-low">Low</span>         <!-- grey -->
<span class="badge badge-pending">Pending</span> <!-- indigo -->
<span class="badge badge-enriched">Enriched</span>
<span class="badge badge-tech">SAP ECC</span>
```

### Button Classes

```html
<button class="btn btn-primary">Save</button>
<button class="btn btn-secondary">Cancel</button>
<button class="btn btn-danger">Delete</button>
<button class="btn btn-ghost">...</button>
<button class="btn btn-sm">Small</button>        <!-- combine with above -->
```

### Form Classes

```html
<div class="form-group">
  <label>Field Name</label>
  <input type="text" name="field">
</div>

<div class="form-row">    <!-- 2-column grid -->
  <div class="form-group">...</div>
  <div class="form-group">...</div>
</div>
```

### Modal Pattern

```html
<div class="modal-backdrop" id="myModal">
  <div class="modal">
    <div class="modal-title">Title</div>
    <!-- content -->
    <div style="display:flex;gap:8px;margin-top:20px">
      <button class="btn btn-primary" onclick="save()">Save</button>
      <button class="btn btn-secondary" onclick="closeModal('myModal')">Cancel</button>
    </div>
  </div>
</div>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
</script>
```

### Toast Notifications

```html
<div id="toast"></div>
<script>
function showToast(msg, type='success') {
  const t = document.createElement('div');
  t.className = 'toast-item ' + type;
  t.textContent = msg;
  document.getElementById('toast').appendChild(t);
  setTimeout(() => t.remove(), 3500);
}
</script>
```

### Score Bar

```html
<div class="score-bar">
  <div class="score-track">
    <div class="score-fill high" style="width:82%"></div>
  </div>
  <span>82</span>
</div>
```

### Sidebar (from `layout.php`)

Fixed left sidebar, 220px wide. Always built via `layout.php`. Active state determined by `basename($_SERVER['PHP_SELF'], '.php')`.

```
Logo / Tenant name
─────────────────────
Dashboard
Companies
Upload
Outreach
  ─── Configure ───
Knowledge
Settings      (admin only)
Users         (admin only)
─────────────────────
[display_name]
[role badge]
Sign out
```

Mobile: hamburger button at top-left, sidebar slides in as overlay.

---

## Page Template

Every page follows this exact structure:

```php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/layout.php';
// Auth::requireAdmin(); // add for admin-only pages

// --- page logic ---
$data = DB::fetchAll('SELECT * FROM some_table WHERE tenant_id = ?', [Auth::tenantId()]);
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Page Title</h1>
    <p class="page-sub">Subtitle</p>
  </div>
  <button class="btn btn-primary">Action</button>
</div>

<!-- page content -->

<?php require_once __DIR__ . '/layout_end.php'; ?>
```

For subdirectory pages (`admin/`, `platform/`): `require_once __DIR__ . '/../config.php'`

---

## API Endpoint Template

Every file in `api/` follows this:

```php
<?php
require_once __DIR__ . '/../config.php';
ob_start();

Auth::requireLogin(); // or Auth::requireAdmin() for admin-only
$tenantId = Auth::tenantId();

header('Content-Type: application/json');

try {
    // --- logic ---
    $result = ['ok' => true, 'data' => []];
    ob_end_clean();
    echo json_encode($result);
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
```

---

## DB Layer (`lib/DB.php`)

Never use raw PDO. Always use these methods:

```php
// Read multiple rows
$rows = DB::fetchAll('SELECT * FROM kb_services WHERE tenant_id = ?', [$tenantId]);

// Read one row
$row = DB::fetchOne('SELECT * FROM users WHERE id = ? AND tenant_id = ?', [$id, $tenantId]);

// Insert — returns new ID
$newId = DB::insert('kb_verticals', [
    'tenant_id'  => $tenantId,
    'name'       => $name,
    'created_at' => date('Y-m-d H:i:s'),
]);

// Update
DB::update('kb_verticals', ['name' => $newName], 'id = ? AND tenant_id = ?', [$id, $tenantId]);

// Delete
DB::query('DELETE FROM kb_verticals WHERE id = ? AND tenant_id = ?', [$id, $tenantId]);
```

**Rule**: every query on a tenant-owned table must include `tenant_id = ?`. No exceptions.

---

## Auth Layer (`lib/Auth.php`)

```php
Auth::requireLogin();             // redirects to /login.php if not signed in
Auth::requireAdmin();             // redirects if not admin
Auth::requirePlatformLogin();     // redirects to /platform/login.php if not platform admin

$user    = Auth::user();          // full session row
$tid     = Auth::tenantId();
$isAdmin = Auth::isAdmin();
$vertIds = Auth::allowedVerticalIds();   // admin: all; member: assigned only
$svcIds  = Auth::allowedServiceIds();

Auth::hashPassword($plain);       // bcrypt cost 12
Auth::login($email, $password);   // returns bool
Auth::logout();
```

### Two-tier auth

| Tier | Table | Cookie | Expiry | Used for |
|---|---|---|---|---|
| Platform admin | `platform_admins` | `ise_platform` | 8 hours | Create/manage tenants |
| Tenant user | `users` | `ise_session` | 30 days | App access |

---

## Multi-Tenancy

Every tenant's data is fully isolated by `tenant_id`.

**Tenant-owned tables:**
`companies`, `email_drafts`, `email_threads`, `kb_company`, `kb_verticals`, `kb_services`, `kb_icps`, `kb_personas`, `kb_tone`, `kb_senders`, `kb_proof`, `kb_documents`, `ai_settings`, `users`, `sessions`

**Member scoping** (admin assigns per-member):
- `user_kb_verticals` — which verticals a member can access
- `user_kb_services` — which services a member can access
- Admin has implicit access to all (no rows checked)

---

## Pages

### Dashboard (`index.php`)

**Auth**: all users  
**Layout**: metric-grid (6 tiles) + top companies table + "Enrich pending" prompt  
**Metrics**: Total, Enriched, High Intent, Medium Intent, Pending, Email Drafts  
**Pattern**: read-only, no forms

---

### Companies (`companies.php`)

**Auth**: all users  
**Layout**: filters bar + card with sortable table + per-row actions  
**Features**:
- Search by name (client-side JS filter)
- Filter by priority, status
- Per-row: score bar, badges, "Enrich" button
- Bulk select + "Enrich All Pending" batch
- Progress bar during batch enrichment
- Each row links to `detail.php?id=N`

**Table columns**: checkbox, Company, Industry, Country, Score, Priority, Signals, Tech Stack, Top Signal, Status, Actions

---

### Company Detail (`detail.php`)

**Auth**: all users  
**Layout**: score banner + 4-stat grid + signals card + tech card + email drafts section  
**Features**:
- Score banner: intent score, matched service, matched vertical (if KB mode)
- Lite mode banner if KB is empty
- Signals list (news, job signals)
- Tech stack badges
- Email drafts per touch (1, 2, 3)
- Per-email: subject, body, AI provider badge, generation mode badge (LITE / KB-MATCHED)
- Buttons: Generate Follow-up, Mark Sent, Mark Replied, Next Action Date
- "Generate email" triggers `api/generate_email.php`

---

### Upload (`upload.php`)

**Auth**: all users  
**Layout**: drag-drop CSV area + column mapping + preview table  
**CSV columns**: name (required), url, industry, country  
**Flow**: parse CSV → preview → POST to `api/upload.php` → redirect to companies

---

### Outreach (`outreach.php`)

**Auth**: all users  
**Layout**: filters + table of email drafts across all companies  
**Filters**: status (draft/sent/replied), touch number, date range  
**Columns**: Company, Subject, Touch, Status, Sent Date, Next Action, Actions

---

### Knowledge Hub (`knowledge.php`)

**Auth**: all users (admin edits, members read)  
**Layout**: tab bar across the top, content below

#### Tab structure

```
Company | Verticals | Services | ICPs | Personas | Tone & Voice | Senders | Proof Points | Documents
```

Each tab is a `?tab=tabname` GET parameter. Active tab highlighted with bottom border in `--accent`.

#### Tab: Company (Block 1)

Single card, `max-width: 760px`. One form, saves via `api/kb.php?action=save_company`.

**Fields** (2-column grid):
- Company Name, Tagline
- Website, Founded Year
- Company Size, HQ Location

**Fields** (full-width):
- Mission, Vision, Company Story
- Credibility Statement *(label notes: "used in AI prompts")*
- Notable Clients, Awards

---

#### Tab: Verticals (Block 2)

**Layout**: 2-column grid — left: add/edit form, right: list of existing verticals

**Add/Edit form fields**:
- Name (required), Focus (textarea)
- Industries Served, Priority (select: Core / Growth / Emerging)
- Differentiators, Head/Lead, Positioning

**List panel**:
- Each row: name + priority badge, industries, Edit + Delete buttons
- Edit pre-fills the left form via JS
- Bottom: CSV import input + Import button + Download Template link

**CSV template** columns: `name, focus, industries, priority, differentiators, head_name, positioning`

---

#### Tab: Services (Block 3)

**Layout**: 2-column grid — left: form, right: list grouped by vertical

**Add/Edit form fields**:
- Name (required), Vertical (select)
- One-liner, Industries, ICP Size
- Buyer Titles, Engagement Model

*Signal mapping section (used by KBMatcher):*
- Signal Keywords *(comma-separated, e.g. "merger, erp upgrade")*
- Signal Types *(comma-separated, e.g. "M&A, ERP, Expansion")*
- Tech Triggers *(comma-separated, e.g. "SAP ECC, Oracle EBS")*
- Competing Tools

*Pitch context section (injected into AI prompts):*
- Description, Problem Statement, Outcomes, Differentiators, Proof Points

**List panel**: grouped by vertical, each row shows name, one-liner, signal count

**CSV template** columns: `name, vertical_name, one_liner, industries, icp_size, buyer_titles, signal_keywords, signal_types, tech_triggers, problem_statement, outcomes`

---

#### Tab: ICPs (Block 4)

**Layout**: 2-column grid — left: form, right: list

**Form fields**:
- Name (required), Vertical (select), Service (select)
- Size Range, Revenue Range
- Industries, Geographies
- Trigger Events *(what causes them to buy now)*
- Perfect Fit, Poor Fit, Disqualifiers, Buying Process

---

#### Tab: Personas (Block 5)

**Layout**: 2-column grid — left: form, right: list

**Form fields**:
- Name (required), Title, Department
- Seniority (select: C-Suite / VP / Director / Manager / IC)
- Vertical (select), Service (select), Reporting To
- Goals, Pain Points, Objections, KPIs
- Decision Role (select: Economic Buyer / Champion / Technical Buyer / End User / Influencer / Blocker)
- Communication Style, Preferred Content, Watering Holes
- Email Hook *(best opening angle for this persona)*

---

#### Tab: Tone & Voice (Block 6)

Single card. One form per tenant.

**Fields**:
- Tone Descriptors *(e.g. "Direct, authoritative, no jargon")*
- Anti-Tone *(e.g. "Never salesy, never vague")*
- Words Always Use, Words Never Use
- Email Opening Style *(free text — describe how to open)*
- CTA Style
- Email Length (select: Short / Medium / Long)
- Paragraph Style (select: One-liners / Full paragraphs / Bullet-heavy)
- Good Example *(paste a real email the AI should sound like)*
- Bad Example *(paste one it should never write like)*

---

#### Tab: Senders (Block 7)

**Layout**: 2-column grid — left: form, right: list

**Form fields**:
- Full Name, Title, Email
- LinkedIn URL, Years Experience
- Background, Credibility
- Individual Tone *(how this specific person writes)*
- Email Opening Style, Email Closing Style
- Verticals Covered, Calendar Link
- Signature, Example Emails *(paste 1–2 real examples)*
- Is Default (checkbox)

**List panel**: name, title, email, default badge, Edit + Delete

---

#### Tab: Proof Points (Block 8)

**Layout**: 2-column grid — left: form, right: list

**Form fields**:
- Client Name (required), Client Industry, Client Size
- Vertical (select), Service (select)
- Challenge, Solution, Outcomes
- Metrics *(e.g. "40% cost reduction, $2M saved")*
- Quote, Quote Attribution
- Is Public (checkbox)

---

#### Tab: Documents (Block 9)

**Layout**: 2-column grid — left: form, right: list

**Form fields**:
- Title (required)
- Doc Type (select: Case Study / Whitepaper / Brochure / Deck / One Pager / ROI Calculator / Video / Other)
- URL, Description, Use Case
- Vertical (select), Service (select)
- Is Public (checkbox)

---

#### KB save pattern (AJAX, all tabs)

All saves go through one endpoint: `POST api/kb.php` with JSON body `{action, entity fields...}`.

```js
async function kbSave(action, formId, btn) {
  const data = Object.fromEntries(new FormData(document.getElementById(formId)));
  data.action = action;
  btn.disabled = true;
  const res = await fetch('api/kb.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(data)
  });
  const json = await res.json();
  showSaveMsg(json.ok ? 'Saved.' : json.error, json.ok ? 'ok' : 'error');
  btn.disabled = false;
  if (json.ok) location.reload(); // refresh list
}
```

---

### Settings (`settings.php`)

**Auth**: admin only  
**Layout**: single column, `max-width: 720px`

**Cards**:
1. **AI Provider** — select active provider (Gemini / Claude / OpenAI), API key per provider, model selector (dynamic options per provider), Test Connection button
2. **Email Preferences** — email length, number of touches, touch intervals (comma-sep days), custom instructions

**Test button**: POST to `api/test_ai.php`, shows success/error toast

---

### Admin Users (`admin/users.php`)

**Auth**: admin only  
**Layout**: 2-column — left: user list, right: permission panel (appears on member click)

**User list** (left):
- Each row: display name, role badge, email, active/inactive badge, Activate/Deactivate toggle, Delete button
- "Add User" button opens inline form or modal: email, password, display name, role

**Permission panel** (right, visible when a member is selected):
- Heading: "Access for [member name]"
- Checkboxes: Verticals (all tenant verticals listed)
- Checkboxes: Services (all tenant services listed, grouped by vertical)
- Save Permissions button → POST `action=save_permissions` with `user_id`, `vertical_ids[]`, `service_ids[]`
- On save: DELETE + re-INSERT rows in `user_kb_verticals` and `user_kb_services`

---

### Platform: Tenants (`platform/tenants.php`)

**Auth**: platform admin only (separate 8-hour `$_SESSION` auth)  
**Layout**: no sidebar, custom header with "ISE Platform Admin" + Sign out link  
**Style**: inline CSS (dark theme), does not depend on `app.css`

**Content**:
- List of all tenants: name, slug, user count, active/inactive badge, Deactivate/Activate toggle, Manage Users link
- "Create New Tenant" card: Company Name, Slug, Admin Email, Admin Password, Admin Display Name
- On submit: creates tenant row + first admin user row + seeds `ai_settings`

---

### Platform: Users (`platform/users.php`)

**Auth**: platform admin only  
**URL param**: `?tenant_id=N`

**Content**:
- Back link to tenants
- Tenant name + slug + id header
- User list: display name, role badge, email, active/inactive, Deactivate/Activate, Delete
- "Add User" card: Display Name, Role, Email, Password

---

## Two-Mode Email Generation

The app auto-selects based on KB completeness:

```
KB has ≥1 vertical AND ≥1 service  →  Full Mode (KBMatcher + rich prompt)
KB empty (no verticals or services) →  Lite Mode (signals-only prompt)
```

**Lite mode prompt sources** (falls back gracefully):
- kb_company.credibility_statement (or generic if missing)
- kb_senders default row (or "Our Team" fallback)
- kb_tone descriptors (or "Professional, concise" fallback)
- Prospect signals + tech stack + industry from the `companies` row

**Full mode prompt sources**:
- All of the above + matched service (problem_statement, outcomes, differentiators) + matched vertical

**UI indicators**:
- Amber banner on detail.php when in lite mode: "Running in Lite Mode — Fill your Knowledge Hub..."
- Per-email badge: `LITE MODE` (amber) or `KB-MATCHED` (green)

---

## Signal → Service Matching (`lib/KBMatcher.php`)

Deterministic scoring — no AI used:

```
+3 pts  per matching signal_type  (e.g. company has "M&A", service targets "M&A")
+3 pts  per matching tech_trigger (e.g. company uses "SAP ECC", service targets "SAP ECC")
+2 pts  per matching industry
Min 2 pts to qualify. Highest score wins.
```

---

## AI Prompt Assembly Order

```
1. Sender identity        — Block 7 (who is writing)
2. Company credibility    — Block 1 (who we are)
3. Service being pitched  — Block 3 (what we offer)
4. Tone rules             — Block 6 (how to write)
5. Target company context — signals, tech, industry
6. Touch context          — touch number, prior subject if follow-up
```

Missing blocks are silently omitted. Lite mode omits 3.

---

## DB Migration Convention

- Files in `sql/` named `NNN_description.sql`, zero-padded, sequential
- Never rename or renumber existing files
- New tables: `CREATE TABLE IF NOT EXISTS`
- New columns on MySQL 5.7: plain `ADD COLUMN` (no `IF NOT EXISTS`)
- After any migration, append the same SQL to `install.sql`
- Every schema-changing commit ships a new `sql/NNN_*.sql` file

---

## Adapting This Design for Another App

The following can be reused verbatim:

| Component | Reuse |
|---|---|
| `assets/css/app.css` | Copy as-is. Change `--accent` colour if desired. |
| `lib/DB.php` | Copy as-is. Update credentials in `config.local.php`. |
| `lib/Auth.php` | Copy as-is. Works for any multi-tenant PHP app. |
| `sql/004_auth.sql` | Copy as-is for multi-tenant auth tables. |
| KB SQL tables (002+) | Copy as-is. Add app-specific columns. |
| `layout.php` / `layout_end.php` | Copy, update nav links for your pages. |
| `platform/` pages | Copy as-is for platform super-admin. |
| `admin/users.php` | Copy, adjust permission tables for your entities. |
| Page template structure | Copy the require_once + layout.php pattern. |
| API endpoint template | Copy the ob_start + try/catch + JSON pattern. |

**What to change per app**:
- Nav links in `layout.php`
- Core feature tables (companies → posts, outreach → schedules, etc.)
- `lib/KBMatcher.php` — replace signal matching with your trigger logic
- `lib/AIEmailDrafter.php` → rename and rewrite prompt for your content type
- `ai_settings` — add/rename fields for your content preferences (e.g. `post_length` instead of `email_length`)
