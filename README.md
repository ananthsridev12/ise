# ISE — Intent Signal Engine

A PHP web app that monitors company intent signals (news, job postings, tech stack changes) and generates AI-powered cold outreach emails. Supports multiple tenants (companies) from a single deployment, with role-based access control.

---

## Deployment

| Item | Value |
|---|---|
| Hosting | cPanel shared hosting at `intel.easi7.in` |
| Deploy branch | `php-app` — the **only** branch cPanel tracks |
| Deploy path | `public_html/intel/` (set in `.cpanel.yml`) |
| PHP | 8.1+ |
| Database | MySQL 5.7+ |

**Deploy flow**: push to `php-app` → cPanel → Git Version Control → pull.

Never push deploy changes to any other branch — cPanel will not pick them up.

---

## First-time Setup (new server)

### 1. Clone repo in cPanel
In cPanel → Git Version Control → clone the repo, set branch to `php-app`.

### 2. Create `config.local.php`
Create this file in the app root on the server. It is **never committed to git**.

```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('ADZUNA_APP_ID', 'your_adzuna_app_id');
define('ADZUNA_APP_KEY', 'your_adzuna_app_key');
```

### 3. Import SQL migrations (in order)
In phpMyAdmin, run these files against your database **in sequence**:

| File | What it creates |
|---|---|
| `sql/001_initial_schema.sql` | Core tables: `companies`, `signals`, `company_tech`, `email_drafts` |
| `sql/002_knowledge_base.sql` | KB tables: `kb_company`, `kb_verticals`, `kb_services`, `kb_icps`, `kb_tone`, `kb_senders`, `ai_settings`; adds columns to `email_drafts` |
| `sql/003_email_threads.sql` | `email_threads` table for AI conversation memory; adds `thread_id`, `next_action_date` to `email_drafts` |
| `sql/004_auth.sql` | Auth tables: `tenants`, `users`, `sessions`, `platform_admins`, `user_kb_verticals`, `user_kb_services`; adds `tenant_id` to all KB and company tables |

> **Tip**: `install.sql` in the repo root combines all of the above into one file for a completely fresh install.

### 4. Create the platform super-admin (one-time)
Visit `https://your-domain/platform/setup.php` in a browser.
Fill in an email and password → submit → **delete this file immediately** from cPanel File Manager.

This creates the platform-level super-admin account used to create and manage tenants.

### 5. Log in to the platform and create your first tenant
Visit `/platform/login.php` → sign in with the credentials you just set.

On the Tenants page:
- A default **SolidPro** tenant (id=1) is already seeded by the migration.
- Use **"Create New Tenant"** to provision Company Y, Company Z, etc.
- Each tenant gets a slug (URL-safe ID), name, and a first admin user (email + password).

### 6. Log in as tenant admin
Visit `/login.php` → sign in with the tenant admin credentials created in step 5.

### 7. Configure AI + Knowledge Base
- Go to `/settings.php` → add your AI provider key (Gemini / Claude / OpenAI), choose model, set email length and touch intervals.
- Go to `/knowledge.php` → fill in your company profile, verticals, services, ICPs, tone guidelines, and sender profiles.

---

## Adding a New Company (Tenant)

Only the platform super-admin can create new tenants.

1. Visit `/platform/login.php`
2. On the Tenants page, fill in:
   - **Company Name** — display name (e.g. "Company Y")
   - **Slug** — URL-safe unique ID (e.g. `company-y`)
   - **Admin Email + Password** — first admin user for that tenant
3. Click **Create Tenant + Admin**
4. The new company's admin can now log in at `/login.php` and set up their own KB, users, and AI settings — completely isolated from other tenants.

To deactivate a tenant (blocks all their users from logging in): click **Deactivate** on the tenant row.

---

## User Roles

| Role | Access |
|---|---|
| **Platform admin** | Can create/deactivate tenants; separate login at `/platform/login.php` |
| **Tenant admin** | Full access within their tenant: companies, KB, settings, user management |
| **Member** | Dashboard, Companies, Upload, Outreach — scoped to verticals/services assigned by admin |

### Managing users (tenant admin)
Go to `/admin/users.php`:
- **Add user** → set email, password, display name, role
- **Activate / Deactivate** → blocks login without deleting data
- **Manage access** → click a member row to assign which verticals and services they can work with

---

## Pages

| URL | Who | Description |
|---|---|---|
| `/login.php` | All | Sign in |
| `/logout.php` | All | Sign out |
| `/index.php` | All | Dashboard |
| `/companies.php` | All | Company list with intent scores |
| `/detail.php?id=N` | All | Company detail — signals, tech stack, AI email drafts |
| `/upload.php` | All | Bulk CSV upload of companies |
| `/outreach.php` | All | Email outreach tracker (sent / replied status) |
| `/knowledge.php` | All (admin edits) | Knowledge Hub — 6 tabs: Company, Verticals, Services, ICPs, Tone, Senders |
| `/settings.php` | Admin | AI provider config + email preferences |
| `/admin/users.php` | Admin | Team user management + KB access assignment |
| `/platform/login.php` | Platform admin | Super-admin sign in |
| `/platform/tenants.php` | Platform admin | Create and manage tenants |

---

## API Endpoints

| Endpoint | Method | Purpose |
|---|---|---|
| `api/enrich.php?id=N` | POST | Fetch signals, score company, auto-generate touch 1 email |
| `api/upload.php` | POST | Process CSV upload |
| `api/extract_tech.php` | POST | Extract tech stack from pasted job description text |
| `api/kb.php` | POST | CRUD for all KB entities (company, verticals, services, ICPs, tone, senders) |
| `api/generate_email.php` | GET | Generate on-demand AI email for a given company + touch number |
| `api/email.php` | POST | Update email draft status (sent / replied) or next action date |
| `api/companies.php` | GET/POST | Company data API |
| `api/test_ai.php` | POST | Test AI provider connection (used by settings page) |

---

## How Signals → Service Matching Works

`lib/KBMatcher.php` runs a deterministic scoring algorithm — no AI is used for matching:

- **+3 points** per matching signal type (e.g. company has "M&A" signal, service targets "M&A")
- **+3 points** per matching tech trigger (e.g. company uses "SAP ECC", service targets "SAP ECC")
- **+2 points** per matching industry

The service with the highest score (minimum 2 to qualify) is selected. AI is only called **after** a service is matched — to write the email using that service's context.

---

## AI Email Generation

`lib/AIEmailDrafter.php` + `lib/EmailGenerator.php`:

- Supported providers: **Gemini**, **Claude**, **OpenAI** (configured in `/settings.php`)
- **Touch 1** (cold email): auto-generated after company enrichment if an AI key is configured
- **Touch 2 / 3** (follow-ups): use conversation threads — the AI sees what it wrote before and builds on it
- **Prompt caching** (Claude only): the static KB context (service description, sender profile, tone) is cached at the API level, reducing token costs by ~70%
- Generated emails are saved to `email_drafts`; conversation history is stored in `email_threads`

---

## Folder Structure

```
/
├── sql/
│   ├── 001_initial_schema.sql      ← core tables
│   ├── 002_knowledge_base.sql      ← KB tables
│   ├── 003_email_threads.sql       ← conversation memory
│   └── 004_auth.sql                ← multi-tenant auth
├── lib/
│   ├── Auth.php                    ← session auth, roles, KB permissions
│   ├── DB.php                      ← PDO wrapper
│   ├── NewsFetcher.php
│   ├── TechExtractor.php
│   ├── Scorer.php
│   ├── EmailDrafter.php            ← template-based fallback
│   ├── KBMatcher.php               ← signal → service matching
│   ├── AIEmailDrafter.php          ← AI email generation (Gemini/Claude/OpenAI)
│   └── EmailGenerator.php          ← shared generate logic used by enrich + generate_email
├── api/
│   ├── enrich.php
│   ├── upload.php
│   ├── extract_tech.php
│   ├── kb.php
│   ├── kb_import.php
│   ├── kb_template.php
│   ├── generate_email.php
│   ├── email.php
│   ├── companies.php
│   └── test_ai.php
├── platform/
│   ├── setup.php                   ← one-time super-admin creation (delete after use)
│   ├── login.php
│   ├── logout.php
│   └── tenants.php                 ← create & manage tenants
├── admin/
│   └── users.php                   ← tenant user management + KB access
├── assets/css/app.css
├── companies.php
├── detail.php
├── index.php
├── knowledge.php
├── login.php
├── logout.php
├── outreach.php
├── settings.php
├── upload.php
├── install.sql                     ← all tables combined (fresh-install reference)
├── config.php                      ← loads config.local.php, starts session, loads Auth
├── config.local.php                ← DB credentials (server only, never in git)
├── layout.php                      ← HTML head + sidebar (includes auth guard)
├── layout_end.php                  ← closes main + body tags
├── .cpanel.yml                     ← cPanel deploy config
└── .gitignore
```

---

## DB Migration Rule

**Every commit that changes the database schema must ship a new file in `sql/`.**

- Naming: `NNN_short_description.sql` — zero-padded, sequential (e.g. `005_add_personas.sql`)
- New tables: use `CREATE TABLE IF NOT EXISTS`
- New columns on MySQL 5.7: use plain `ADD COLUMN` (no `IF NOT EXISTS` — not supported until MySQL 8.0)
- Also update `install.sql` at repo root to keep it current as the single-file fresh-install reference

---

## Configuration Files

| File | Location | In git? | Purpose |
|---|---|---|---|
| `config.local.php` | Server root | No | DB credentials, Adzuna API keys |
| `.cpanel.yml` | Repo root | Yes | cPanel deploy path |
| `.gitignore` | Repo root | Yes | Excludes secrets and runtime files |

AI keys and provider settings are stored in the `ai_settings` DB table (per tenant), managed via `/settings.php`.
