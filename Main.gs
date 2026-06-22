// ============================================================
// Main.gs — Top-level entry points.
//
//   These are the only functions called by triggers or manually
//   from the Apps Script editor.  All logic lives in modules.
//
//   MANUAL ENTRY POINTS (run from the editor):
//     runFullPipeline()       — fetch + process all queries now
//     runHourlyPipeline()     — fetch + process 'Hourly' queries
//     runDailyPipeline()      — fetch + process 'Daily' queries
//     processUnprocessed()    — (re)process existing raw news
//     refreshDashboard()      — update Dashboard sheet only
//     runDailyCleanup()       — purge old logs/hashes
//     setupTriggers()         — install time-based triggers
//     removeTriggers()        — remove all ISE triggers
//     testFetchSingleQuery()  — test a single query by name
// ============================================================

// ----------------------------------------------------------
// TRIGGER HANDLERS
// ----------------------------------------------------------

/**
 * Called every hour by the hourly trigger.
 */
function runHourlyPipeline() {
  _runPipeline('Hourly');
}

/**
 * Called every day by the daily trigger.
 */
function runDailyPipeline() {
  _runPipeline('Daily');
}

/**
 * Called every day by the cleanup trigger.
 */
function runDailyCleanup() {
  var ctx = SyncLog.startRun('Daily Cleanup');
  try {
    var retainDays = SettingsManager.getInt('LOG_RETAIN_DAYS', 30);
    SyncLog.purgeLogs(retainDays);
    DuplicateChecker.purgeOld(retainDays);
    Dashboard.refresh();
    SyncLog.endRun(ctx, 0, 0);
  } catch(e) {
    logError('runDailyCleanup failed: ' + e.message);
    SyncLog.failRun(ctx, e);
  }
}

// ----------------------------------------------------------
// MANUAL ENTRY POINTS
// ----------------------------------------------------------

/**
 * Runs the full pipeline for ALL enabled queries regardless of frequency.
 */
function runFullPipeline() {
  _runPipeline(null);  // null = all frequencies
}

/**
 * Re-processes all raw news items that are marked as unprocessed.
 * Useful after updating Keywords, Signals, or ICP Master.
 */
function processUnprocessed() {
  var ctx = SyncLog.startRun('Reprocess Unprocessed');
  try {
    var items    = RawNewsManager.getUnprocessed();
    var inserted = _processItems(items);
    Dashboard.refresh();
    SyncLog.endRun(ctx, items.length, inserted);
  } catch(e) {
    logError('processUnprocessed failed: ' + e.message);
    SyncLog.failRun(ctx, e);
  }
}

/**
 * Refreshes the Dashboard sheet manually.
 */
function refreshDashboard() {
  Dashboard.refresh();
  SpreadsheetApp.getActiveSpreadsheet().toast(
    'Dashboard refreshed at ' + isoDateTime(new Date()), 'ISE', 3);
}

/**
 * Sets up all ISE time-based triggers.
 */
function setupTriggers() {
  TriggerManager.setupTriggers();
  SpreadsheetApp.getActiveSpreadsheet().toast('Triggers installed.', 'ISE', 3);
}

/**
 * Removes all ISE triggers.
 */
function removeTriggers() {
  TriggerManager.removeAllIseTriggers();
  SpreadsheetApp.getActiveSpreadsheet().toast('Triggers removed.', 'ISE', 3);
}

/**
 * Creates the Company Candidates review sheet if it does not exist.
 */
function setupCompanyCandidates() {
  CompanyCandidateManager.ensureSheet();
  SpreadsheetApp.getActiveSpreadsheet().toast('Company Candidates sheet ready.', 'ISE', 3);
}

/**
 * Imports Company Candidates rows marked Status = Approved into Companies.
 */
function importApprovedCompanyCandidates() {
  var created = CompanyCandidateManager.approveMarked();
  SpreadsheetApp.getActiveSpreadsheet().toast(
    'Imported ' + created + ' approved company candidates.', 'ISE', 5);
}

/**
 * Loads the Signal Pack: adds all standard Categories, Signals, and
 * Keywords to the master sheets.  Safe to run more than once — it
 * skips entries that already exist by name.
 */
function setupSignalPack() {
  SignalPack.setup();
}

/**
 * Adds new profile columns (Domain, LinkedIn URL, Employee Range,
 * Revenue Range, Target Account) to the Companies sheet header row.
 * Does not touch existing data rows.
 */
function migrateCompaniesSchema() {
  var ws      = getSheet('COMPANIES');
  var lastCol = ws.getLastColumn();

  var newHeaders = {
    10: 'Domain',
    11: 'LinkedIn URL',
    12: 'Employee Range',
    13: 'Revenue Range',
    14: 'Target Account'
  };

  var added = [];
  for (var col in newHeaders) {
    col = parseInt(col);
    if (col > lastCol || ws.getRange(1, col).getValue() === '') {
      ws.getRange(1, col).setValue(newHeaders[col]);
      added.push(newHeaders[col]);
    }
  }

  CompanyExtractor.invalidate();
  var msg = added.length
    ? 'Added columns: ' + added.join(', ')
    : 'Companies schema already up to date.';
  SpreadsheetApp.getActiveSpreadsheet().toast(msg, 'ISE', 5);
  logInfo('migrateCompaniesSchema: ' + msg);
}

/**
 * Adds example custom RSS feed queries to the Search Queries sheet.
 * Uses SourceType = RSS so the GoogleQuery cell is treated as a
 * direct feed URL.  Edit the URLs as needed for your sources.
 */
function setupDefaultRssFeeds() {
  var feeds = [
    {
      queryName:   'PR Newswire – Manufacturing',
      googleQuery: 'https://www.prnewswire.com/rss/news-releases-list.rss',
      sourceType:  'RSS',
      frequency:   'Daily',
      remarks:     'Press releases – manufacturing and industrial sector'
    },
    {
      queryName:   'Business Wire – Industry News',
      googleQuery: 'https://feeds.businesswire.com/rss/home/?rss=G1&rssid=6',
      sourceType:  'RSS',
      frequency:   'Daily',
      remarks:     'Business Wire general industry news feed'
    },
    {
      queryName:   'IndustryWeek – Latest',
      googleQuery: 'https://www.industryweek.com/rss',
      sourceType:  'RSS',
      frequency:   'Daily',
      remarks:     'IndustryWeek manufacturing news'
    },
    {
      queryName:   'Reuters – Business News',
      googleQuery: 'https://feeds.reuters.com/reuters/businessNews',
      sourceType:  'RSS',
      frequency:   'Daily',
      remarks:     'Reuters top business headlines'
    }
  ];

  var added = 0;
  var existing = SearchQueryManager.getAll().map(function(q) {
    return q.queryName.toLowerCase();
  });

  feeds.forEach(function(f) {
    if (existing.indexOf(f.queryName.toLowerCase()) === -1) {
      SearchQueryManager.add(f);
      added++;
    }
  });

  var msg = added + ' RSS feed queries added (0 skipped: already exist).';
  SpreadsheetApp.getActiveSpreadsheet().toast(msg, 'ISE', 5);
  logInfo('setupDefaultRssFeeds: ' + msg);
}

// ----------------------------------------------------------
// OUTREACH INTELLIGENCE SETUP
// ----------------------------------------------------------

/**
 * Creates and pre-populates the Pitch Playbook sheet with
 * SolidPro service-to-signal mappings.
 */
function setupPitchPlaybook() {
  PitchPlaybook.setup();
}

/**
 * Creates the Outreach Queue sheet if it does not exist.
 */
function setupOutreachQueue() {
  OutreachQueue.ensureSheet();
  SpreadsheetApp.getActiveSpreadsheet().toast('Outreach Queue sheet ready.', 'ISE', 3);
}

/**
 * Creates the Account Intelligence sheet if it does not exist.
 */
function setupAccountIntelligence() {
  AccountIntelligence.ensureSheet();
  SpreadsheetApp.getActiveSpreadsheet().toast('Account Intelligence sheet ready.', 'ISE', 3);
}

/**
 * Creates the Target Accounts watch list sheet if it does not exist.
 */
function setupTargetAccounts() {
  TargetAccounts.ensureSheet();
  SpreadsheetApp.getActiveSpreadsheet().toast('Target Accounts sheet ready.', 'ISE', 3);
}

/**
 * Generates Google News search queries for all Target Accounts
 * with AutoSearch = TRUE.
 */
function generateWatchQueries() {
  var created = TargetAccounts.generateWatchQueries();
  SpreadsheetApp.getActiveSpreadsheet().toast(
    created + ' watch queries created.', 'ISE', 5);
}

/**
 * Manually refreshes the Account Intelligence aggregation.
 */
function refreshAccountIntelligence() {
  AccountIntelligence.refresh();
  SpreadsheetApp.getActiveSpreadsheet().toast(
    'Account Intelligence refreshed.', 'ISE', 3);
}

/**
 * Tests fetching a single query by name (enter name in Settings or
 * pass it directly).  Writes results to Raw News but does NOT process.
 */
function testFetchSingleQuery() {
  var testQueryName = SettingsManager.get('TEST_QUERY_NAME', '');
  if (!testQueryName) {
    SpreadsheetApp.getUi().alert(
      'Set TEST_QUERY_NAME in Settings first, then run this function again.');
    return;
  }

  var queries = SearchQueryManager.getActiveQueries().filter(function(q) {
    return q.queryName === testQueryName;
  });

  if (!queries.length) {
    SpreadsheetApp.getUi().alert('Query "' + testQueryName + '" not found or not enabled.');
    return;
  }

  var ctx = SyncLog.startRun('Test: ' + testQueryName);
  try {
    var fetched  = NewsFetcher.fetchAll(queries);
    var total    = 0;
    var inserted = 0;

    fetched.forEach(function(result) {
      var parsed = RSSParser.parse(result.xml, result.query);
      var newItems = DuplicateChecker.filterNew(parsed);
      inserted += RawNewsManager.insertBatch(newItems);
      total    += parsed.length;
      SearchQueryManager.markRun(result.query.queryId, true);
    });

    SyncLog.endRun(ctx, total, inserted);
    SpreadsheetApp.getActiveSpreadsheet().toast(
      'Fetched ' + total + ' items, ' + inserted + ' new.', 'ISE Test', 5);
  } catch(e) {
    SyncLog.failRun(ctx, e);
    SpreadsheetApp.getActiveSpreadsheet().toast('Error: ' + e.message, 'ISE Test', 5);
  }
}

// ----------------------------------------------------------
// CORE PIPELINE
// ----------------------------------------------------------

/**
 * Shows a toast notification AND logs the message.
 * Called throughout the pipeline to give real-time status.
 *
 * @param {string} msg       Message to display.
 * @param {number} [secs=3]  Toast duration in seconds.
 */
function _progress(msg, secs) {
  logInfo(msg);
  try {
    SpreadsheetApp.getActiveSpreadsheet().toast(msg, 'ISE Pipeline', secs || 3);
  } catch(e) { /* ignore if spreadsheet not available (trigger context) */ }
}

/**
 * Internal pipeline runner.
 * Fetches queries one at a time so progress can be shown after each.
 *
 * @param {string|null} frequency  'Hourly', 'Daily', or null for all.
 */
function _runPipeline(frequency) {
  var label = frequency ? frequency + ' Pipeline' : 'Full Pipeline';
  var ctx   = SyncLog.startRun(label);
  var totalFetched  = 0;
  var totalInserted = 0;

  try {
    logInfo('=== ISE Pipeline Start: ' + label + ' ===');

    // ---- PHASE 1: LOAD QUERIES ----
    var queries = SearchQueryManager.getActiveQueries(frequency);
    if (!queries.length) {
      _progress('No active queries found. Enable rows in Search Queries sheet.', 5);
      SyncLog.endRun(ctx, 0, 0);
      return;
    }
    _progress('Starting: ' + queries.length + ' active quer' +
              (queries.length === 1 ? 'y' : 'ies') + ' to fetch...');

    // ---- PHASE 2: FETCH — one query at a time for live progress ----
    var allNewItems = [];

    queries.forEach(function(q, idx) {
      _progress('[' + (idx + 1) + '/' + queries.length + '] Fetching: ' + q.queryName);

      var xml      = NewsFetcher.fetchRss(q);
      var parsed   = RSSParser.parse(xml, q);
      var newItems = DuplicateChecker.filterNew(parsed);

      totalFetched  += parsed.length;
      var ins        = RawNewsManager.insertBatch(newItems);
      totalInserted += ins;
      allNewItems    = allNewItems.concat(newItems);

      SearchQueryManager.markRun(q.queryId, xml !== null);
      logInfo('Query "' + q.queryName + '": ' + parsed.length +
              ' fetched, ' + ins + ' new');
    });

    _progress('Fetch done: ' + totalFetched + ' articles found, ' +
              totalInserted + ' new.');

    // ---- PHASE 3: CLASSIFICATION & SCORING ----
    if (allNewItems.length > 0) {
      _progress('Classifying ' + allNewItems.length + ' new article' +
                (allNewItems.length === 1 ? '' : 's') + '...');
      var promoted = _processItems(allNewItems);
      _progress('Classified: ' + allNewItems.length + ' articles, ' +
                promoted + ' promoted to queues.');
    } else {
      _progress('No new articles to classify.');
    }

    // ---- PHASE 4: DASHBOARD + ACCOUNT INTELLIGENCE ----
    _progress('Updating Dashboard and Account Intelligence...');
    Dashboard.refresh();
    AccountIntelligence.refresh();

    SyncLog.endRun(ctx, totalFetched, totalInserted);

    var summary = label + ' complete: ' + totalFetched + ' fetched, ' +
                  totalInserted + ' new, ' + allNewItems.length + ' classified.';
    _progress(summary, 7);
    logInfo('=== ISE Pipeline End: ' + summary + ' ===');

  } catch(e) {
    logError('Pipeline error: ' + e.message + '\n' + e.stack);
    _progress('Pipeline error: ' + e.message, 8);
    SyncLog.failRun(ctx, e);
  }
}

/**
 * Classifies, scores, and queues a batch of raw news items.
 * Shows a progress toast every 5 items.
 * Returns count of items promoted to Action Queue.
 */
function _processItems(items) {
  var promoted = 0;
  var total    = items.length;

  items.forEach(function(item, idx) {
    // Progress toast every 5 items
    if (idx > 0 && idx % 5 === 0) {
      _progress('Classifying ' + idx + '/' + total + ' articles...');
    }

    try {
      if (!item.newsId) {
        logWarn('_processItems: item missing newsId, skipping: ' + item.title);
        return;
      }

      // Classification
      var kwMatches      = KeywordMatcher.match(item);
      var classification = SignalClassifier.classify(item, kwMatches);
      var coMatch        = CompanyExtractor.extract(item);
      if (!coMatch) {
        CompanyCandidateManager.record(item, CompanyExtractor.guessCandidate(item), classification);
      }

      // Scoring
      var icpMatch = ICPMatcher.match(item, coMatch, classification);
      var scoreObj = IntentScoreEngine.compute(item, classification, kwMatches, icpMatch);

      // Persist
      ProcessedNewsManager.insert(item, coMatch, classification, kwMatches, scoreObj, icpMatch);
      IntentScoreEngine.record(item.newsId, scoreObj);
      if (icpMatch) ICPMatcher.record(item.newsId, icpMatch);

      // Mark raw item as processed
      RawNewsManager.markProcessed(item.newsId);

      // Promote to Action Queue and Outreach Queue
      var processedItem = ProcessedNewsManager.getById(item.newsId);
      if (processedItem) {
        if (icpMatch) processedItem.icpName = icpMatch.icpName;
        var qId = ActionQueue.promote(processedItem);
        if (qId) promoted++;

        var pitchContext = PitchPlaybook.lookup(classification.signal);
        OutreachQueue.promote(processedItem, pitchContext);
      }

    } catch(e) {
      logError('_processItems: error on newsId ' + item.newsId + ': ' + e.message);
    }
  });

  logInfo('_processItems: processed ' + total + ' items, ' +
          promoted + ' promoted to Action Queue');
  return promoted;
}
