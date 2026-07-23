# ISE — Intent Signal Engine

A PHP web app that monitors company intent signals (news, job postings, tech stack) and generates AI-powered cold outreach emails for SolidPro.

---

## Deployment

- **Hosting**: cPanel shared hosting at `intel.easi7.in`
- **Deploy branch**: `php-app` — this is the **only** branch cPanel's Git Version Control tracks
- **Deploy path**: `public_html/intel/` (defined in `.cpanel.yml`)
- **Never push deploy changes to any other branch** — cPanel will not pick them up

To deploy: push to `php-app`, then in cPanel → Git Version Control → pull.

---

## First-time Setup

1. In cPanel → Git Version Control, clone the repo and point it at the `php-app` branch
2. In phpMyAdmin, select your database and import:
   - `sql/001_initial_schema.sql` (core tables)
   - `sql/002_knowledge_base.sql` (KB tables + email_drafts columns)
3. Create `config.local.php` in the app root with your credentials (never committed to git):

```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('ADZUNA_APP_ID', 'your_adzuna_app_id');
define('ADZUNA_APP_KEY', 'your_adzuna_app_key');
```

4. Go to `/settings.php` and configure your AI provider (Gemini / Claude / OpenAI) + API key
5. Go to `/knowledge.php` and fill in SolidPro's company profile, verticals, services, ICPs, tone

---

## Pages

| URL | Description |
|---|---|
| `/` or `/companies.php` | Company list with intent scores |
| `/detail.php?id=N` | Company detail — signals, tech, email drafts |
| `/upload.php` | Bulk upload companies via CSV |
| `/outreach.php` | Email outreach tracker |
| `/knowledge.php` | Knowledge Hub (6 tabs: Company, Verticals, Services, ICPs, Tone, Senders) |
| `/settings.php` | AI provider config + email preferences |

### API Endpoints

| Endpoint | Purpose |
|---|---|
| `POST api/enrich.php?id=N` | Fetch signals, score, generate email |
| `POST api/upload.php` | Process CSV upload |
| `POST api/extract_tech.php` | Extract tech stack from pasted job description |
| `POST api/kb.php` | CRUD for all KB entities |
| `GET api/generate_email.php` | Generate on-demand follow-up email |

---

## DB Migration Rule

**Every commit that changes the database schema must include a new file in `sql/`.**

- File naming: `NNN_short_description.sql` (zero-padded, sequential — e.g. `003_add_personas.sql`)
- The file must be safe to re-run: use `IF NOT EXISTS` for new tables, `ADD COLUMN IF NOT EXISTS` for new columns
- Also update `install.sql` in the repo root to reflect all tables combined (single-file fresh-install reference)

---

## Folder Structure

```
/
├── sql/
│   ├── 001_initial_schema.sql    ← original 4 tables
│   └── 002_knowledge_base.sql    ← KB tables + email_drafts columns
├── lib/
│   ├── DB.php
│   ├── NewsFetcher.php
│   ├── TechExtractor.php
│   ├── Scorer.php
│   ├── EmailDrafter.php
│   ├── KBMatcher.php
│   └── AIEmailDrafter.php
├── api/
│   ├── enrich.php
│   ├── upload.php
│   ├── extract_tech.php
│   ├── kb.php
│   └── generate_email.php
├── assets/css/app.css
├── knowledge.php
├── settings.php
├── install.sql                   ← full fresh-install reference (all tables)
├── README.md
├── .cpanel.yml
├── config.php
└── layout.php
```

---

## Configuration

- **AI settings**: `/settings.php` — provider, API keys, email length, multi-touch intervals
- **Knowledge base**: `/knowledge.php` — SolidPro company info, verticals, services, ICPs, tone, senders
- **DB credentials + Adzuna keys**: `config.local.php` on the server only (not in git)
