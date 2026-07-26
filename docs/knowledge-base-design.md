# Knowledge Base Design — ISE Pattern

A reusable pattern for building a structured, AI-ready company knowledge base in a PHP + MySQL app. Designed so an AI (or human) can pull any slice of this KB and write high-quality, context-aware content — emails, LinkedIn posts, ads, proposals — without hallucinating company facts.

---

## Why This Structure

Most apps dump company info into a single settings table. That works for display, but it fails for AI content generation because:

- AI needs **context hierarchy**: who we are → what we sell → who we sell to → how we talk → who's talking
- Different content types need different KB slices (a LinkedIn post needs tone + company story; a cold email needs service + ICP + sender)
- Multi-tenant SaaS needs every KB row **scoped to a tenant** so Company Y's KB never leaks into Company Z's content

The pattern below solves all three.

---

## The 9 Blocks

Every piece of company GTM knowledge maps to one of 9 blocks. Not all are required — the system degrades gracefully with whatever is filled.

| Block | Table | What it holds | Required for AI? |
|---|---|---|---|
| 1 | `kb_company` | Identity, story, credibility | Recommended |
| 2 | `kb_verticals` | Business units / practice areas | Yes (full mode) |
| 3 | `kb_services` | Service offerings + signal mapping | Yes (full mode) |
| 4 | `kb_icps` | Ideal customer profiles | Recommended |
| 5 | `kb_personas` | Buyer personas per role | Optional |
| 6 | `kb_tone` | Voice, language rules, style | Recommended |
| 7 | `kb_senders` | People who send/post content | Recommended |
| 8 | `kb_proof` | Case studies + client outcomes | Optional |
| 9 | `kb_documents` | Assets: decks, whitepapers, URLs | Optional |

---

## Block 1 — Company Identity (`kb_company`)

Single row per tenant. The "about us" context injected into every AI prompt.

```sql
CREATE TABLE IF NOT EXISTS `kb_company` (
  `id`                    INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id`             INT NOT NULL DEFAULT 1,
  `name`                  VARCHAR(255),
  `tagline`               VARCHAR(500),
  `website`               VARCHAR(255),
  `founded_year`          VARCHAR(10),
  `size`                  VARCHAR(100),          -- e.g. "200-500"
  `hq`                    VARCHAR(255),
  `mission`               TEXT,
  `vision`                TEXT,
  `story`                 TEXT,
  `credibility_statement` TEXT,                  -- injected into AI prompts verbatim
  `notable_clients`       TEXT,                  -- comma-separated or prose
  `awards`                TEXT,
  `updated_at`            DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

**Key field for AI**: `credibility_statement` — a 2–3 sentence paragraph the AI uses as-is. Write it once, use everywhere.

---

## Block 2 — Verticals / Business Units (`kb_verticals`)

A vertical is a practice area or business unit. Services hang off verticals. This is the top of the hierarchy.

```sql
CREATE TABLE IF NOT EXISTS `kb_verticals` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id`       INT NOT NULL DEFAULT 1,
  `name`            VARCHAR(255) NOT NULL,        -- "ERP Practice", "SCM Division"
  `focus`           TEXT,                         -- what this BU specialises in
  `industries`      TEXT,                         -- comma-separated: "Manufacturing, Retail"
  `priority`        ENUM('core','growth','emerging') DEFAULT 'core',
  `differentiators` TEXT,
  `head_name`       VARCHAR(255),
  `positioning`     TEXT,
  `created_at`      DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

**For LinkedIn**: a vertical maps to a content pillar. Posts tagged to a vertical stay on-topic for that BU's audience.

---

## Block 3 — Services (`kb_services`)

The most important table for signal-matching. Each service has keywords and triggers that let the system detect which service to pitch to which prospect.

```sql
CREATE TABLE IF NOT EXISTS `kb_services` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id`         INT NOT NULL DEFAULT 1,
  `vertical_id`       INT,
  `name`              VARCHAR(255) NOT NULL,
  `one_liner`         VARCHAR(500),              -- single sentence pitch
  `industries`        TEXT,
  `icp_size`          VARCHAR(255),              -- "500-5000 employees"
  `buyer_titles`      TEXT,                      -- "CFO, VP Finance, ..."
  `engagement_model`  VARCHAR(100),              -- "Fixed fee", "Retainer", "T&M"
  `signal_keywords`   TEXT,                      -- comma-sep keywords to detect in news/JDs
  `signal_types`      TEXT,                      -- comma-sep: "M&A,ERP,Expansion"
  `tech_triggers`     TEXT,                      -- comma-sep tech: "SAP ECC,Oracle EBS"
  `competing_tools`   TEXT,
  `description`       TEXT,
  `problem_statement` TEXT,                      -- "Companies using X struggle with Y..."
  `outcomes`          TEXT,                      -- measurable results
  `differentiators`   TEXT,
  `proof_points`      TEXT,
  `created_at`        DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`vertical_id`) REFERENCES `kb_verticals`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;
```

**Signal matching logic** (from `lib/KBMatcher.php`):
- +3 pts per matching `signal_type`
- +3 pts per matching `tech_trigger`
- +2 pts per matching industry
- Service with highest score (min 2) wins — no AI used for matching, it's deterministic

**For LinkedIn**: `signal_keywords` can trigger which service's content to post when a trending topic is detected.

---

## Block 4 — Ideal Customer Profiles (`kb_icps`)

Who the perfect customer is. Scoped per service/vertical.

```sql
CREATE TABLE IF NOT EXISTS `kb_icps` (
  `id`                 INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id`          INT NOT NULL DEFAULT 1,
  `name`               VARCHAR(255) NOT NULL,    -- "Mid-market Manufacturing CFO"
  `vertical_id`        INT,
  `service_id`         INT,
  `size_range`         VARCHAR(255),             -- "500-5000 employees"
  `revenue_range`      VARCHAR(255),             -- "$50M-$500M ARR"
  `industries`         TEXT,
  `geographies`        TEXT,
  `tech_stack_signals` TEXT,
  `trigger_events`     TEXT,                     -- "just raised Series B, hiring SAP roles"
  `perfect_fit`        TEXT,
  `poor_fit`           TEXT,
  `disqualifiers`      TEXT,
  `buying_process`     TEXT,
  `created_at`         DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`vertical_id`) REFERENCES `kb_verticals`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`service_id`) REFERENCES `kb_services`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;
```

---

## Block 5 — Buyer Personas (`kb_personas`)

Individual roles within an ICP company. Useful for personalising tone and hook per title.

```sql
CREATE TABLE IF NOT EXISTS `kb_personas` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id`           INT NOT NULL DEFAULT 1,
  `name`                VARCHAR(255) NOT NULL,   -- "The Cautious CFO"
  `title`               VARCHAR(255),
  `department`          VARCHAR(255),
  `seniority`           ENUM('C-Suite','VP','Director','Manager','Individual Contributor') DEFAULT 'Director',
  `vertical_id`         INT,
  `service_id`          INT,
  `reporting_to`        VARCHAR(255),
  `goals`               TEXT,
  `pain_points`         TEXT,
  `objections`          TEXT,
  `kpis`                VARCHAR(500),
  `decision_role`       ENUM('Economic Buyer','Champion','Technical Buyer','End User','Influencer','Blocker') DEFAULT 'Champion',
  `communication_style` TEXT,
  `preferred_content`   VARCHAR(500),            -- "long-form reports, ROI calculators"
  `watering_holes`      VARCHAR(500),            -- "LinkedIn, Gartner, CFO forums"
  `email_hook`          TEXT,                    -- best opening hook for this persona
  `created_at`          DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`vertical_id`) REFERENCES `kb_verticals`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`service_id`) REFERENCES `kb_services`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;
```

**For LinkedIn**: `preferred_content` and `watering_holes` tell you what format to post (carousel, article, short post) and which hashtags/communities to target.

---

## Block 6 — Tone & Voice (`kb_tone`)

Single row per tenant. Rules the AI must follow when writing.

```sql
CREATE TABLE IF NOT EXISTS `kb_tone` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id`           INT NOT NULL DEFAULT 1,
  `tone_descriptors`    VARCHAR(500),            -- "Direct, authoritative, no jargon"
  `anti_tone`           VARCHAR(500),            -- "Never salesy, never vague"
  `words_always`        TEXT,                    -- words to use: "outcomes, measurable, ..."
  `words_never`         TEXT,                    -- words to ban: "synergy, leverage, ..."
  `email_opening_style` TEXT,                    -- "Lead with a relevant observation, not a pitch"
  `cta_style`           TEXT,                    -- "One soft CTA only. No 'schedule a demo'."
  `email_length`        ENUM('short','medium','long') DEFAULT 'medium',
  `paragraph_style`     ENUM('one-liners','full-paragraphs','bullet-heavy') DEFAULT 'full-paragraphs',
  `good_example`        TEXT,                    -- paste a real example the AI should mirror
  `bad_example`         TEXT,                    -- paste an example the AI should never write like
  `updated_at`          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

**Key fields for LinkedIn**: `words_never`, `paragraph_style`, `good_example` — these are the fastest way to stop AI from writing generic corporate posts.

---

## Block 7 — Senders / Authors (`kb_senders`)

People who sign/post content. For LinkedIn, this is the personal brand profile.

```sql
CREATE TABLE IF NOT EXISTS `kb_senders` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id`           INT NOT NULL DEFAULT 1,
  `full_name`           VARCHAR(255),
  `title`               VARCHAR(255),
  `email`               VARCHAR(255),
  `linkedin_url`        VARCHAR(500),
  `background`          TEXT,                    -- career summary for AI context
  `credibility`         TEXT,                    -- why this person is worth listening to
  `years_experience`    INT,
  `individual_tone`     TEXT,                    -- how this person specifically writes
  `email_opening_style` TEXT,
  `email_closing_style` TEXT,
  `verticals`           TEXT,                    -- which BUs this person covers
  `calendar_link`       VARCHAR(500),
  `signature`           TEXT,
  `example_emails`      TEXT,                    -- paste 1-2 real examples of their writing
  `is_default`          TINYINT(1) DEFAULT 0,
  `created_at`          DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

**Critical for LinkedIn**: `individual_tone` + `example_emails` are what make a post sound like the actual person, not a generic AI. Paste 2–3 real posts as examples.

---

## Block 8 — Proof Points / Case Studies (`kb_proof`)

Real client outcomes. Injected as social proof into content.

```sql
CREATE TABLE IF NOT EXISTS `kb_proof` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id`         INT NOT NULL DEFAULT 1,
  `client_name`       VARCHAR(255) NOT NULL,
  `client_industry`   VARCHAR(255),
  `client_size`       VARCHAR(255),
  `vertical_id`       INT,
  `service_id`        INT,
  `challenge`         TEXT,
  `solution`          TEXT,
  `outcomes`          TEXT,
  `metrics`           VARCHAR(500),              -- "Reduced close time by 40%, saved $2M"
  `quote`             TEXT,
  `quote_attribution` VARCHAR(255),
  `is_public`         TINYINT(1) DEFAULT 1,
  `created_at`        DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`vertical_id`) REFERENCES `kb_verticals`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`service_id`) REFERENCES `kb_services`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;
```

---

## Block 9 — Documents & Assets (`kb_documents`)

Links to existing content assets the AI can reference or share.

```sql
CREATE TABLE IF NOT EXISTS `kb_documents` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id`   INT NOT NULL DEFAULT 1,
  `title`       VARCHAR(255) NOT NULL,
  `doc_type`    ENUM('case_study','whitepaper','brochure','deck','one_pager','roi_calculator','video','other') DEFAULT 'other',
  `url`         VARCHAR(1000),
  `description` TEXT,
  `use_case`    TEXT,                            -- "Share in follow-up emails after demo"
  `vertical_id` INT,
  `service_id`  INT,
  `is_public`   TINYINT(1) DEFAULT 1,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`vertical_id`) REFERENCES `kb_verticals`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`service_id`) REFERENCES `kb_services`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;
```

---

## AI Settings (`ai_settings`)

One row per tenant. Stores provider keys and content preferences.

```sql
CREATE TABLE IF NOT EXISTS `ai_settings` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id`           INT NOT NULL DEFAULT 1,
  `provider`            ENUM('gemini','claude','openai') DEFAULT 'gemini',
  `gemini_key`          VARCHAR(500),
  `claude_key`          VARCHAR(500),
  `openai_key`          VARCHAR(500),
  `model`               VARCHAR(100),
  `email_length`        ENUM('short','medium','long') DEFAULT 'medium',
  `num_touches`         INT DEFAULT 3,
  `touch_intervals`     VARCHAR(255) DEFAULT '0,3,7',
  `custom_instructions` TEXT,                   -- free-form extra instructions for every prompt
  `updated_at`          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

---

## Two-Mode Generation

The system auto-detects which mode to use based on KB completeness:

| Mode | Condition | Quality |
|---|---|---|
| **Lite** | No verticals OR no services | Good — uses signals + partial KB |
| **Full** | ≥1 vertical AND ≥1 service | Best — service-matched, fully personalised |

```php
// Detection logic (lib/EmailGenerator.php)
public static function detectMode(int $tenantId): string {
    $v = DB::fetchOne('SELECT COUNT(*) as c FROM kb_verticals WHERE tenant_id = ?', [$tenantId]);
    $s = DB::fetchOne('SELECT COUNT(*) as c FROM kb_services WHERE tenant_id = ?', [$tenantId]);
    return ($v['c'] > 0 && $s['c'] > 0) ? 'full' : 'lite';
}
```

---

## Prompt Assembly Pattern

When calling the AI, assemble context in this order (most → least important):

```
1. Sender identity       (Block 7) — who is writing
2. Company credibility   (Block 1) — who we are
3. Service being pitched (Block 3) — what we offer
4. Tone rules            (Block 6) — how to write
5. Target context                  — prospect signals, industry, tech
6. Proof point           (Block 8) — optional, if service matches a case study
7. Touch context                   — touch number, prior subject if follow-up
```

The AI only receives what exists. Missing blocks are silently omitted.

---

## CSV Import Templates

### kb_verticals.csv
```
name,focus,industries,priority,differentiators,head_name,positioning
ERP Practice,"SAP and Oracle ERP transformation","Manufacturing,Retail",core,"15 years SAP delivery",Jane Smith,"We implement ERP faster with fewer change requests"
```

### kb_services.csv
```
name,vertical_name,one_liner,industries,icp_size,buyer_titles,signal_keywords,signal_types,tech_triggers,problem_statement,outcomes
SAP S/4 Migration,ERP Practice,"We move companies from SAP ECC to S4HANA in 9 months","Manufacturing,Logistics","500-5000","CIO,VP IT,SAP Program Manager","sap ecc,legacy erp,s4hana,ecc upgrade","ERP,Digital Transformation","SAP ECC,R3,ECC 6.0","Companies on SAP ECC face end of maintenance in 2027 with no clear path forward","Live on S/4HANA in 9 months. 30% lower TCO. Zero business disruption."
```

### kb_senders.csv
```
full_name,title,email,linkedin_url,years_experience,individual_tone,verticals,is_default
Jane Smith,Managing Director,jane@solidpro.com,https://linkedin.com/in/janesmith,18,"Direct and data-led. Short sentences. Never uses buzzwords. Always references a specific number or trend.",ERP Practice,1
```

### kb_icps.csv
```
name,vertical_name,service_name,size_range,revenue_range,industries,geographies,trigger_events,perfect_fit,poor_fit,disqualifiers
Mid-market Manufacturing CIO,ERP Practice,SAP S/4 Migration,"500-5000","$100M-$1B","Manufacturing,Industrial","US,UK,Germany","ECC end of maintenance announcement,merger,new CIO hire","On SAP ECC, under 5000 employees, has internal IT team","Greenfield with no ERP, <100 employees","Already on S/4HANA,using Oracle,no budget cycle"
```

---

## Multi-Tenant Rules

Every KB table must have `tenant_id`. Rules:

1. **Every SELECT** on a KB table: `WHERE tenant_id = Auth::tenantId()`
2. **Every INSERT**: include `'tenant_id' => Auth::tenantId()`
3. **Every UPDATE/DELETE**: `AND tenant_id = ?` as a second condition — never trust just `id`
4. Admin sees all rows for their tenant. Members see only rows within their assigned verticals/services.

---

## Adapting for LinkedIn Auto Poster

The tables you need are the same. The only differences:

| ISE field | LinkedIn equivalent |
|---|---|
| `kb_tone.email_opening_style` | `kb_tone.post_opening_style` |
| `kb_tone.email_length` | `kb_tone.post_length` (short/thread/article) |
| `kb_senders.email_opening_style` | `kb_senders.post_voice` |
| `kb_senders.calendar_link` | `kb_senders.linkedin_url` |
| `ai_settings.num_touches` | `ai_settings.posts_per_week` |
| `ai_settings.touch_intervals` | `ai_settings.post_schedule` |

Add these LinkedIn-specific fields to `kb_tone`:

```sql
ALTER TABLE `kb_tone`
  ADD COLUMN `post_format`     ENUM('short','carousel','thread','article') DEFAULT 'short',
  ADD COLUMN `hook_style`      TEXT,      -- "Start with a contrarian statement or a number"
  ADD COLUMN `hashtag_strategy` TEXT,     -- "3 max, always include #SAP and one niche tag"
  ADD COLUMN `post_frequency`  VARCHAR(50), -- "3x per week, Mon/Wed/Fri"
  ADD COLUMN `cta_linkedin`    TEXT;      -- "End with a question to drive comments"
```

Add to `kb_senders`:

```sql
ALTER TABLE `kb_senders`
  ADD COLUMN `linkedin_headline` VARCHAR(300),
  ADD COLUMN `linkedin_about`    TEXT,
  ADD COLUMN `example_posts`     TEXT,   -- paste 2-3 real LinkedIn posts as style examples
  ADD COLUMN `post_topics`       TEXT;   -- comma-sep: "ERP migration, SAP tips, leadership"
```

Everything else — company identity, verticals, services, ICPs, personas, proof, documents — is identical. The same SQL files can be imported as-is.

---

## Minimum Viable KB (start here)

Fill these first, everything else is optional:

1. **kb_company**: just `name` + `credibility_statement`
2. **kb_tone**: `tone_descriptors` + `words_never` + one `good_example`
3. **kb_senders**: one row with `full_name`, `title`, `individual_tone`, `example_posts`

With just these three, the AI can generate decent content. Add blocks 2–9 progressively to improve quality.
