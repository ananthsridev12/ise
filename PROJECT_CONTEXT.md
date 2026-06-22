# Intent Signal Engine (ISE) — Complete Project Context

## 1. What This Is

ISE is a **Google Apps Script project** bound to a Google Spreadsheet.  
It monitors Google News RSS feeds and external RSS sources, classifies articles by buying-intent signals, scores them, matches them against Ideal Customer Profiles (ICPs), and populates an enriched outreach queue for B2B cold outreach.

**Owner / Company:** SolidPro Engineering Support Pvt Ltd  
**Purpose:** Signal-based cold outreach — find companies showing real intent (ERP rollout, plant expansion, hiring, M&A) before reaching out, instead of random prospecting.  
**Platform:** Google Sheets + Google Apps Script (container-bound, no web app deployment needed)

---

## 2. Complete Pipeline Flow

```
Search Queries sheet
        ↓
SearchQueryManager.getActiveQueries()
        ↓
NewsFetcher.fetchRss(query)        ← one query at a time for progress toasts
        ↓
RSSParser.parse(xml, query)
        ↓
DuplicateChecker.filterNew(items)  ← MD5 hash deduplication
        ↓
RawNewsManager.insertBatch(items)  → Raw News sheet
        ↓
_processItems() — for each new item:
  ├── KeywordMatcher.match(item)
  ├── SignalClassifier.classify(item, kwMatches)
  ├── CompanyExtractor.extract(item)       ← name + domain matching
  │       └── if no match → CompanyCandidateManager.record()  → Company Candidates sheet
  ├── ICPMatcher.match(item, coMatch, classification)
  ├── IntentScoreEngine.compute(item, classification, kwMatches, icpMatch)
  ├── ProcessedNewsManager.insert()        → Processed News sheet
  ├── IntentScoreEngine.record()           → Intent Score Engine sheet
  ├── ICPMatcher.record()                  → ICP Matching sheet
  ├── RawNewsManager.markProcessed()
  ├── ActionQueue.promote()                → Action Queue sheet
  ├── PitchPlaybook.lookup(signal)
  └── OutreachQueue.promote()              → Outreach Queue sheet
        ↓
Dashboard.refresh()                        → Dashboard sheet
AccountIntelligence.refresh()              → Account Intelligence sheet
```

---

## 3. All 26 Script Files

| File | Purpose |
|---|---|
| `Core.gs` | Central config: `SHEETS`, `COL`, `CONST` maps and all spreadsheet helper functions |
| `Main.gs` | All entry points, `_runPipeline()`, `_processItems()`, `_progress()` |
| `CustomMenu.gs` | `onOpen()` — builds ISE custom menu in Google Sheets |
| `Logger.gs` | `logInfo()`, `logWarn()`, `logError()`, `logDebug()` wrappers |
| `SettingsManager.gs` | Reads Settings sheet key/value store |
| `TriggerManager.gs` | Installs/removes hourly + daily time-based triggers |
| `SyncLog.gs` | Pipeline run log: `startRun()`, `endRun()`, `failRun()`, `purgeLogs()` |
| `SearchQueryManager.gs` | Reads Search Queries, builds RSS URLs, marks LastRun timestamps |
| `NewsFetcher.gs` | `fetchRss(query)` — fetches RSS with 3 retries + back-off; `fetchAll()` for batch |
| `RSSParser.gs` | Parses raw RSS XML into structured news item objects |
| `DuplicateChecker.gs` | MD5-based deduplication against Duplicate Index sheet |
| `RawNewsManager.gs` | Inserts into / reads from Raw News; `markProcessed()`, `getUnprocessed()` |
| `KeywordMatcher.gs` | Matches article text against Keywords sheet; returns hits + scores |
| `SignalClassifier.gs` | Picks the top signal for an article from keyword matches + Signals sheet |
| `CompanyExtractor.gs` | Matches articles to Companies/Aliases by name and source domain |
| `CompanyCandidateManager.gs` | Records unknown companies for manual review; `approveMarked()` imports to Companies |
| `ICPMatcher.gs` | Scores each article against ICP Master profiles; returns best match |
| `IntentScoreEngine.gs` | Computes weighted 0–100 intent score; applies Priority Rules adjustments |
| `ProcessedNewsManager.gs` | Writes enriched item to Processed News; `getById()`, `getAll()` |
| `ActionQueue.gs` | Promotes high-scoring items to Action Queue; deduplicates by NewsID |
| `SignalPack.gs` | Pre-built data: 8 categories, 36 signals, 230+ keywords — loads into sheets |
| `PitchPlaybook.gs` | 37 signal→pitch mappings for SolidPro services; `setup()` + `lookup()` |
| `OutreachQueue.gs` | 23-column enriched queue; `promote()`, `getAll()`, `getStats()`, `updateStatus()` |
| `AccountIntelligence.gs` | Per-company signal aggregation; `refresh()` runs after every pipeline |
| `TargetAccounts.gs` | Watch list; `generateWatchQueries()` auto-creates Google News queries |
| `Dashboard.gs` | Refreshes all dashboard metric rows at end of pipeline |

---

## 4. All Sheets — Complete Schemas

### Input / Master Sheets (manually maintained)

#### Settings
```
Key | Value | Description
```
Key settings:
```
SPREADSHEET_NAME        Intent Signal Engine
MAX_NEWS_AGE_DAYS       7
FETCH_INTERVAL_MINUTES  60
MIN_INTENT_SCORE        30
MAX_RESULTS_PER_QUERY   20
LOG_LEVEL               INFO
ENABLE_DUPLICATE_CHECK  TRUE
ENABLE_ICP_MATCHING     TRUE
DEFAULT_COUNTRY         US
DEFAULT_LANGUAGE        en
LOG_RETAIN_DAYS         30
TEST_QUERY_NAME         (optional — for testFetchSingleQuery)
```

#### Search Queries (15 columns)
```
QueryID | QueryName | GoogleQuery | CategoryID | SignalID | IndustryID |
Country | Language | Frequency | Priority | Enabled | LastRun | LastSuccess |
Remarks | SourceType
```
- `SourceType` = `GoogleNews` (default, builds Google News RSS URL) or `RSS` (GoogleQuery cell is a direct feed URL)
- `Frequency` = `Hourly` | `Daily` | `Weekly`
- `Enabled` = TRUE/FALSE
- `Country` can be blank — falls back to `DEFAULT_COUNTRY` setting

#### Categories
```
CategoryID | Category | Description
```

#### Signals
```
SignalID | Signal | CategoryID | DefaultScore | BuyingIntent | Active
```
- `DefaultScore` = base 0–100 score for this signal
- `BuyingIntent` = TRUE/FALSE (TRUE signals get 15% score boost)

#### Keywords
```
KeywordID | Keyword | SignalID | Weight | MatchType | Active
```
- `Weight` = 1–3 (importance of keyword match)
- `MatchType` = `exact` (whole phrase) or `contains` (substring)

#### Industries
```
IndustryID | Industry | Vertical | ParentIndustry | Active
```

#### Sources
```
SourceID | Source | Domain | Type | Priority | Active
```
- `Priority` = `High` (100 pts) | `Medium` (60 pts) | `Low` (30 pts)

#### Companies (14 columns)
```
CompanyID | CompanyName | Website | IndustryID | Country | State | City |
Notes | Active | Domain | LinkedIn URL | Employee Range | Revenue Range | Target Account
```
- Columns 10–14 added via `ISE > Companies > Migrate Companies Schema`
- `Domain` used for source URL matching (e.g., `siemens.com`)
- `Target Account` = TRUE/FALSE — flags priority accounts

#### Company Aliases
```
AliasID | CompanyID | Alias
```

#### ICP Master (12 columns)
```
ICPID | Vertical | ICP Code | Segment | Sub Segment | Product/Service |
PRIMARY Services to Pitch | Category (Who They Are) | Tier | Geo | Employees | Revenue
```
- Rows active if `ICPID` is present (no separate Active column)
- `Tier` = `Large` gives a small scoring boost after a core match
- `Geo` values: `US`, `EU`, `UK`, `IN`, or country name

#### Priority Rules
```
RuleID | Signal | Condition | Adjustment | Remarks
```
- `Signal` must match classified signal text (case-insensitive)
- `Condition` keyword must appear in article title/snippet
- `Adjustment` = -10 to +15 applied to final score

#### Lookup Tables
```
Type | Value
```
General-purpose reference data.

---

### Output / System Sheets (filled by the script)

#### Raw News
```
NewsID | FetchTime | PublishedDate | Title | Snippet | URL | SourceID | QueryID | Hash | Processed
```

#### Processed News
```
NewsID | Title | Summary | Company | Industry | Category | Signal |
KeywordMatched | IntentScore | Priority | PublishedDate | URL | Status
```

#### Intent Score Engine
```
NewsID | SignalScore | KeywordScore | FreshnessScore | SourceScore | ICPScore | FinalScore
```

#### ICP Matching
```
MatchID | NewsID | ICPID | Confidence | Reason
```

#### Duplicate Index
```
Hash | NewsID | URL | Created
```

#### Sync Log
```
RunID | StartTime | EndTime | Query | RecordsFetched | RecordsInserted | Status | Error
```

#### Action Queue
```
QueueID | NewsID | Company | Headline | Signal | Category | IntentScore |
ICP | PublishedDate | URL | ActionStatus | Owner | Notes
```
- `ActionStatus`: New → Completed
- Promoted when `IntentScore >= MIN_INTENT_SCORE`

#### Company Candidates
```
CandidateID | CompanyName | SourceNewsID | Title | URL | Confidence |
SuggestedIndustry | Status | Created | Notes
```
- `Status`: Pending → Approved → Imported

#### Dashboard
```
Metric | Value
```
Metrics tracked:
```
Total News | Today's News | High Intent | Medium Intent | Low Intent |
Top Signal | Top Category | Last Sync | Action Queue | Unprocessed |
Outreach Queue – New | Accounts Ready | Target Accounts
```

---

### Outreach Intelligence Sheets (created via ISE menu)

#### Pitch Playbook
```
PlaybookID | Signal | Category | WhyItMatters | PitchAngle | KeyServices |
TalkTrack1 | TalkTrack2 | TalkTrack3 | Priority
```
- Pre-populated with 37 signal-to-pitch mappings via `setupPitchPlaybook()`
- Human-editable after setup

#### Outreach Queue (23 columns)
```
OutreachID | NewsID | CompanyName | Domain | Industry | Headline | Signal |
Category | IntentScore | ICPMatch | PitchAngle | KeyServices | TalkTrack |
PublishedDate | URL | OutreachStatus | Owner | ContactName | ContactTitle |
ContactEmail | OutreachDate | FollowUpDate | Notes
```
- `OutreachStatus`: New → Draft → Sent → Replied → Won / Closed / Snoozed
- `PitchAngle`, `KeyServices`, `TalkTrack` auto-filled from Pitch Playbook
- `ContactName/Title/Email` filled manually after finding the right contact

#### Account Intelligence (14 columns)
```
AccountID | CompanyName | Industry | ICPMatch | TotalSignals | SignalTypes |
LastSignalDate | HighestScore | TopSignal | TopPitchAngle | KeyServices |
OutreachReady | LastOutreachDate | Notes
```
- Rebuilt from Outreach Queue after every pipeline run
- `OutreachReady = YES` when `HighestScore >= MIN_INTENT_SCORE` AND `LastOutreachDate` is blank or >30 days ago
- `LastOutreachDate` is manually entered (the system reads it but does not auto-fill)

#### Target Accounts (8 columns)
```
AccountID | CompanyName | Domain | Industry | ICPMatch | WatchPriority | AutoSearch | Notes
```
- `AutoSearch = TRUE` → `generateWatchQueries()` creates 3 Google News queries per company:
  - `"{name}" hiring OR expansion OR "new plant"` — Growth signals
  - `"{name}" ERP OR SAP OR automation OR "digital transformation"` — Technology signals
  - `"{name}" acquisition OR merger OR investment OR funding` — M&A/Financial signals

---

## 5. Intent Scoring Formula

Final score = weighted sum of 5 sub-scores, then Priority Rules adjustments applied.

| Sub-Score | Weight | How Calculated |
|---|---|---|
| Signal | 30% | `DefaultScore` from Signals sheet × 1.15 if BuyingIntent=TRUE |
| Keyword | 25% | Total keyword weights from KeywordMatcher matches |
| Freshness | 20% | Age-based: ≤1 day=100, ≤2=85, ≤3=70, ≤5=50, ≤7=30, older=10 |
| Source | 10% | Source priority: High=100, Medium=60, Low=30, unknown=50 |
| ICP | 15% | ICP match confidence (0–100) |

Priority thresholds:
- `High` = finalScore ≥ 70
- `Medium` = finalScore ≥ 40
- `Low` = finalScore < 40

Promotion to queues requires `finalScore >= MIN_INTENT_SCORE` (default: 30).

---

## 6. ICP Matching Logic

`ICPMatcher.match()` scores each article against all ICP rows. Key rules:

1. **Company match** → +15 pts (news is about a known company in Companies sheet)
2. **Signal match** in ICP profile text → +25 pts (core match required)
3. **Product/Service phrase hits** → up to +30 pts (core match required)
4. **Segment/Sub-Segment/Category hits** → up to +25 pts (core match required)
5. **Geo match** → +20 pts (only if core match already made)
6. **Large tier** → +10 pts (only if core match already made)

Minimum confidence to report a match: **35**  
`Geo` and `Tier` alone cannot create a match — a core match from signal/product/segment is required first.

ICP name displayed in queues = `Segment || ICP Code`

---

## 7. Signal Pack — Pre-built Data

Loaded via `ISE > Setup > Load Signal Pack`. Additive — safe to run multiple times.

**8 Categories:**
```
Growth & Expansion | Technology Adoption | Financial Events | Hiring & Talent |
M&A Activity | Market Activity | Regulatory & Compliance | Supply Chain & Logistics
```

**36 Signals:**

| Signal | Score | BuyingIntent |
|---|---|---|
| Capacity Expansion | 70 | ✓ |
| New Facility | 75 | ✓ |
| Greenfield Project | 80 | ✓ |
| Plant Upgrade | 65 | ✓ |
| Warehouse Expansion | 65 | ✓ |
| Export Expansion | 50 | — |
| New Market Entry | 55 | — |
| ERP Implementation | 80 | ✓ |
| SAP Implementation | 80 | ✓ |
| Digital Transformation | 70 | ✓ |
| Factory Automation | 75 | ✓ |
| AI Adoption | 65 | ✓ |
| Smart Factory | 70 | ✓ |
| Cloud Migration | 60 | ✓ |
| IoT Adoption | 65 | ✓ |
| Funding | 55 | — |
| PE Investment | 60 | — |
| IPO | 50 | — |
| Manufacturing Hiring | 65 | ✓ |
| Technology Hiring | 60 | ✓ |
| Leadership Hiring | 45 | — |
| Bulk Hiring | 65 | ✓ |
| Acquisition | 55 | — |
| Merger | 50 | — |
| Joint Venture | 65 | ✓ |
| Divestiture | 45 | — |
| Product Launch | 50 | — |
| Partnership | 50 | — |
| New Contract | 65 | ✓ |
| Procurement Expansion | 75 | ✓ |
| Leadership Change | 40 | — |
| Regulatory Approval | 50 | — |
| Quality Certification | 55 | ✓ |
| Environmental Compliance | 50 | ✓ |
| Supply Chain Change | 60 | ✓ |
| New Supplier | 65 | ✓ |
| Logistics Expansion | 60 | ✓ |

---

## 8. Pitch Playbook — SolidPro Signal-to-Service Mapping

37 entries pre-loaded via `setupPitchPlaybook()`. All map to SolidPro's service portfolio.

**SolidPro Services Referenced:**
- CAD/CAM/CAE (SolidWorks, CATIA, Creo, NX) / PLM/PDM (Windchill, Teamcenter, Vault)
- ERP Integration (SAP S/4HANA, Epicor, NetSuite, Infor, Microsoft Dynamics)
- CPQ / Industrial CPQ / Salesforce CPQ
- Digital Thread Enablement
- ALM (Application Lifecycle Management)
- Engineering Change Management (ECM)
- MDM (Master Data Management)
- Workflow Automation
- AI-enabled Engineering Solutions
- Cloud Transformation
- Embedded Software / Firmware Development
- Electronics Engineering / Hardware Design / IC Testing
- Special Purpose Machines & Automation
- Technical Publications: S1000D Authoring, DITA Publishing, IETP, AR/VR Work Instructions
- Operator Manuals, Parts Catalogues, Translation & Localization
- NetZeroHub (Sustainability Platform)
- IC Remote Monitoring
- NPD (New Product Development)
- DFM/DFA (Design for Manufacturability/Assembly)
- DFMEA / PFMEA
- Sustenance Engineering / Retrofit Engineering
- Value Engineering

Key pitch mappings by signal:

| Signal | Key Services to Lead With |
|---|---|
| Greenfield Project | PLM, Digital Thread, NPD |
| ERP Implementation | ERP Integration, CAD Integration, Engineering Data Migration, CPQ |
| SAP Implementation | SAP/ERP Integration, Industrial CPQ, Engineering Data Migration |
| Digital Transformation | CPQ, PLM, Digital Thread, AI-enabled Engineering, Workflow Automation |
| Factory Automation | Special Purpose Machines, Embedded Software, SCADA/MES, AR/VR Work Instructions |
| Smart Factory | Digital Thread, IIoT Integration, AI-enabled Engineering |
| Cloud Migration | Cloud Transformation, PLM Migration, ERP Integration |
| IoT Adoption | IC Remote Monitoring, Firmware, Embedded Software, Hardware Design |
| Manufacturing Hiring | AR/VR Work Instructions, Operator Manuals, IETP |
| Acquisition/Merger | PLM Migration, Engineering Data Migration, MDM |
| Environmental Compliance | NetZeroHub, Sustainability Engineering, ESG Reporting |
| Quality Certification | S1000D, DITA Publishing, DFMEA/PFMEA |
| Supply Chain Change | ERP Integration, CPQ, Industrial CPQ |

---

## 9. Company Extraction Logic

`CompanyExtractor.extract(item)` tries two methods:

1. **Text matching** — article title/snippet scanned for known company names and aliases. Strips common suffixes (Ltd, Inc, Corp, etc.) for fuzzy matching.
2. **Domain matching** (fallback) — article URL root domain matched against `Companies.Domain` column.

If neither matches, `CompanyExtractor.guessCandidate(item)` attempts to extract a company name from headline patterns:
```
"[Company] announces / opens / launches / expands / invests / acquires /
  raises / partners / appoints / plans / to open / to expand / gets approval"
```

The guess is logged to Company Candidates for manual review — not automatically added to Companies.

---

## 10. Company Candidates Workflow

1. Pipeline runs → unknown companies → written to `Company Candidates` with `Status = Pending`
2. User reviews `Company Candidates` sheet
3. User sets `Status = Approved` for valid entries; optionally fills `SuggestedIndustry`
4. User runs `ISE > Companies > Import Approved Candidates`
5. `CompanyCandidateManager.approveMarked()` imports to `Companies` + creates aliases
6. Imported rows marked `Status = Imported`

---

## 11. RSS Source Types

The `SourceType` column (col 15) in Search Queries controls how the URL is built:

| SourceType | Behavior |
|---|---|
| `GoogleNews` | Builds `https://news.google.com/rss/search?q={encoded_query}&hl=en&gl=US&ceid=US:en` |
| `RSS` | Uses the `GoogleQuery` cell value as the direct feed URL unchanged |

`RSS` source type supports any RSS/Atom feed:
- Indeed jobs: `https://www.indeed.com/rss?q=SAP+implementation&l=United+States`
- PR Newswire: `https://www.prnewswire.com/rss/news-releases-list.rss`
- Business Wire: `https://feeds.businesswire.com/rss/home/?rss=G1&rssid=6`
- IndustryWeek: `https://www.industryweek.com/rss`

Default RSS feeds added via `ISE > Setup > Add Default RSS Feed Queries`.

---

## 12. Menu Structure (CustomMenu.gs)

```
ISE
├── Run Full Pipeline
├── Run Hourly Pipeline
├── Run Daily Pipeline
├── ── (separator)
├── Refresh Dashboard
├── ── (separator)
├── Companies ▶
│   ├── Setup Company Candidates Sheet
│   ├── Import Approved Candidates
│   └── Migrate Companies Schema (add new columns)
├── Outreach ▶
│   ├── Setup Pitch Playbook
│   ├── Setup Outreach Queue
│   ├── Setup Account Intelligence
│   ├── Setup Target Accounts
│   ├── ── (separator)
│   ├── Generate Watch Queries
│   └── Refresh Account Intelligence
└── Setup ▶
    ├── Load Signal Pack
    ├── Add Default RSS Feed Queries
    ├── Setup Triggers
    └── Remove Triggers
```

**Critical:** `onOpen()` only works when the spreadsheet opens naturally. Running `onOpen()` from the Apps Script editor gives `Cannot call SpreadsheetApp.getUi()`. To see the ISE menu: close the sheet tab, reopen from Google Drive.

---

## 13. All Entry Points

### Pipeline
```js
runFullPipeline()        // all enabled queries regardless of frequency
runHourlyPipeline()      // Frequency = Hourly queries only
runDailyPipeline()       // Frequency = Daily queries only
processUnprocessed()     // re-process existing Raw News items (after updating Keywords/Signals)
refreshDashboard()       // update Dashboard sheet only
```

### Setup (run once, in order)
```js
setupSignalPack()              // loads 8 categories, 36 signals, 230+ keywords
migrateCompaniesSchema()       // adds columns 10-14 to Companies sheet header
setupPitchPlaybook()           // creates + pre-populates Pitch Playbook sheet
setupOutreachQueue()           // creates Outreach Queue sheet
setupAccountIntelligence()     // creates Account Intelligence sheet
setupTargetAccounts()          // creates Target Accounts sheet
setupCompanyCandidates()       // creates Company Candidates sheet
setupDefaultRssFeeds()         // adds 4 default RSS feed queries
setupTriggers()                // installs hourly + daily triggers
```

### Ongoing
```js
importApprovedCompanyCandidates()  // imports Approved candidates to Companies
generateWatchQueries()              // creates Google News queries for Target Accounts with AutoSearch=TRUE
refreshAccountIntelligence()        // manual refresh of Account Intelligence
removeTriggers()                    // removes all ISE triggers
testFetchSingleQuery()              // test a single query (set TEST_QUERY_NAME in Settings first)
```

---

## 14. Triggers

Installed by `setupTriggers()`:
- **Hourly trigger** → `runHourlyPipeline()` — fetches queries with `Frequency = Hourly`
- **Daily trigger** → `runDailyPipeline()` — fetches queries with `Frequency = Daily`
- **Daily cleanup trigger** → `runDailyCleanup()` — purges Sync Log and Duplicate Index older than `LOG_RETAIN_DAYS`

---

## 15. Real-Time Progress Toasts

`_progress(msg, secs)` calls both `logInfo()` and `SpreadsheetApp.toast()`. Toast appears in the sheet corner during execution. Pipeline shows:
1. `Starting: N active queries to fetch...`
2. `[1/N] Fetching: Query Name` — per query
3. `Fetch done: X articles found, Y new.`
4. `Classifying N new articles...`
5. `Classifying X/N articles...` — every 5 items
6. `Classified: N articles, M promoted to queues.`
7. `Updating Dashboard and Account Intelligence...`
8. `Full Pipeline complete: X fetched, Y new, Z classified.`

---

## 16. First-Time Deployment Steps

1. Open Google Sheets → Extensions → Apps Script
2. Create one `.gs` file per source file — paste content (do not rename the default `Code.gs`, rename it to `Main.gs` or add Main.gs alongside)
3. Close and reopen the Google Sheet from Google Drive → ISE menu appears
4. Run in order:
   - `ISE > Setup > Load Signal Pack`
   - `ISE > Companies > Migrate Companies Schema`
   - `ISE > Outreach > Setup Pitch Playbook`
   - `ISS > Outreach > Setup Outreach Queue`
   - `ISE > Outreach > Setup Account Intelligence`
   - `ISE > Outreach > Setup Target Accounts`
   - `ISE > Setup > Add Default RSS Feed Queries`
5. Fill in `Search Queries` sheet with your Google News queries (Enabled=TRUE)
6. Fill in `ICP Master` with your ICP profiles
7. Fill in `Companies` with known target companies
8. Run `ISE > Run Full Pipeline`
9. Review `Processed News`, `Outreach Queue`, `Account Intelligence`
10. Run `ISE > Setup > Setup Triggers` to automate

---

## 17. Retesting Cleanly

To retest scoring/matching after logic changes — clear these sheets:
```
Processed News | Intent Score Engine | ICP Matching | Action Queue | Outreach Queue | Account Intelligence
```

To also re-fetch:
```
Raw News | Duplicate Index | Sync Log
```

Then run `runFullPipeline()`.

---

## 18. Search Query Examples (Good Queries for SolidPro's ICPs)

```
manufacturing company capacity expansion
new plant manufacturing
manufacturing ERP OR SAP implementation
factory automation manufacturing
industrial automation plant
digital transformation manufacturing
SAP S/4HANA implementation manufacturing
CPQ implementation manufacturer
PLM implementation engineering
greenfield manufacturing plant
smart factory industry 4.0
aerospace defense ERP implementation
```

For hiring signals via Indeed RSS (SourceType = RSS):
```
https://www.indeed.com/rss?q=SAP+implementation+manager&l=United+States
https://www.indeed.com/rss?q=PLM+engineer+windchill+teamcenter&l=United+States
https://www.indeed.com/rss?q=CPQ+administrator&l=United+States
https://www.indeed.com/rss?q=digital+transformation+manufacturing&l=United+States
https://www.indeed.com/rss?q=factory+automation+engineer&l=United+States
```

---

## 19. Known Issues and Fixes Applied

| Issue | Fix Applied |
|---|---|
| `ISE menu not showing` | Run `onOpen()` only works on spreadsheet open — close and reopen from Drive |
| `CompanyCandidateManager is not defined` | File was missing from Apps Script project — add `CompanyCandidateManager.gs` |
| Search Queries: `TRUE` in CategoryID column | Only `Enabled` column (col 11) should be TRUE; CategoryID (col 4) should be blank or a category ID |
| ICP over-matching on Geo/Tier alone | Fixed in `ICPMatcher.gs`: Geo and Tier can only boost score after a core match |
| India hardcoded as default country | Fixed in `SearchQueryManager.buildRssUrl()` — now reads `DEFAULT_COUNTRY` setting (set to `US`) |
| Google News returns news articles, NOT job postings | Use Indeed RSS (SourceType=RSS) for actual job listing signals |

---

## 20. External Data Sources

| Source | Method | Cost |
|---|---|---|
| Google News | RSS via UrlFetchApp | Free |
| Indeed Jobs | Public RSS via UrlFetchApp (SourceType=RSS) | Free |
| PR Newswire | RSS via UrlFetchApp | Free |
| Business Wire | RSS via UrlFetchApp | Free |
| IndustryWeek | RSS via UrlFetchApp | Free |
| LinkedIn Sales Navigator | **Not accessible** — browser-only, no API, ToS prohibits scraping | N/A |
| LinkedIn Jobs API | **Shut down** — no longer available | N/A |
| JSearch API (RapidAPI) | REST API — aggregates LinkedIn + Indeed + Glassdoor | ~$10–50/month |
| Apollo.io | REST API — company + contact data | Paid |
| SerpAPI (Google Jobs) | REST API — scrapes Google Jobs results | ~$50/month |

---

## 21. File Dependency Order (for Apps Script)

Files have no module system — all are global. Load order does not matter in GAS, but logical reading order:

```
Core.gs → Logger.gs → SettingsManager.gs → TriggerManager.gs → SyncLog.gs
→ SearchQueryManager.gs → NewsFetcher.gs → RSSParser.gs → DuplicateChecker.gs
→ RawNewsManager.gs → KeywordMatcher.gs → SignalClassifier.gs
→ CompanyExtractor.gs → CompanyCandidateManager.gs → ICPMatcher.gs
→ IntentScoreEngine.gs → ProcessedNewsManager.gs → ActionQueue.gs
→ SignalPack.gs → PitchPlaybook.gs → OutreachQueue.gs
→ AccountIntelligence.gs → TargetAccounts.gs → Dashboard.gs
→ CustomMenu.gs → Main.gs
```

---

## 22. Architecture Notes

- **No module system** — all files are global JavaScript scope in GAS. Wrapped modules use IIFE pattern: `var ModuleName = (function() { ... return { publicFunctions }; })();`
- **Lazy caching** — `ICPMatcher`, `SignalClassifier`, `KeywordMatcher`, `PitchPlaybook` all load sheet data once per script execution into `_cache` or `_icps` variables. Call `.invalidate()` after sheet data changes.
- **Column maps** — all column numbers are 1-based and centralized in `COL` object in `Core.gs`. Never hardcode column numbers.
- **Sheet access** — always use `getSheet('SHEET_KEY')` (throws descriptive error if missing) or `getSpreadsheet().getSheetByName(SHEETS.SHEET_NAME)` (returns null if missing, used for optional sheets).
- **Backward compatibility** — `CompanyExtractor` checks `r.length >= COL.COMPANIES.DOMAIN` before reading domain column, so it works on pre-migration sheets with only 9 columns.
- **Toast limitations** — `SpreadsheetApp.toast()` works during active script execution but not in trigger context (silently ignored via try/catch in `_progress()`).

---

## 23. Recommended Next Improvements

1. **JSearch API integration** — new `JobFetcher.gs` that calls RapidAPI JSearch and feeds results into the same pipeline (covers LinkedIn + Indeed + Glassdoor job signals)
2. **Apollo.io enrichment** — auto-enrich approved Company Candidates with employee count, revenue, contact names via Apollo API
3. **Email draft generation** — use `PitchPlaybook` talk tracks to auto-draft outreach emails via Gmail API
4. **Outreach Queue filters** — add ISE menu items to filter Outreach Queue by signal category, intent score threshold, or company
5. **CRM sync** — push Outreach Queue items to HubSpot or Pipedrive via their REST APIs using `UrlFetchApp`
6. **Webhook alerts** — POST to Slack when a high-intent item is detected for a Target Account
7. **Multi-language keyword support** — additional Keywords rows for German, French, Japanese industrial terminology
