// ============================================================
// Core.gs — Centralized configuration: sheet names, column maps,
//            constants, and low-level sheet helpers.
// ============================================================

// ----------------------------------------------------------
// SHEET NAMES  (single source of truth — never hardcode elsewhere)
// ----------------------------------------------------------
var SHEETS = {
  SETTINGS:        'Settings',
  SEARCH_QUERIES:  'Search Queries',
  CATEGORIES:      'Categories',
  SIGNALS:         'Signals',
  KEYWORDS:        'Keywords',
  INDUSTRIES:      'Industries',
  SOURCES:         'Sources',
  COMPANIES:       'Companies',
  COMPANY_ALIASES: 'Company Aliases',
  COMPANY_CANDIDATES: 'Company Candidates',
  ICP_MASTER:      'ICP Master',
  RAW_NEWS:        'Raw News',
  PROCESSED_NEWS:  'Processed News',
  INTENT_SCORE:    'Intent Score Engine',
  ICP_MATCHING:    'ICP Matching',
  PRIORITY_RULES:  'Priority Rules',
  DUPLICATE_INDEX: 'Duplicate Index',
  SYNC_LOG:        'Sync Log',
  ACTION_QUEUE:    'Action Queue',
  LOOKUP_TABLES:   'Lookup Tables',
  DASHBOARD:       'Dashboard',

  // Outreach Intelligence layer
  PITCH_PLAYBOOK:       'Pitch Playbook',
  OUTREACH_QUEUE:       'Outreach Queue',
  ACCOUNT_INTELLIGENCE: 'Account Intelligence',
  TARGET_ACCOUNTS:      'Target Accounts'
};

// ----------------------------------------------------------
// COLUMN MAPS  (1-based, matching the xlsx template)
// ----------------------------------------------------------
var COL = {
  SETTINGS: {
    KEY: 1, VALUE: 2, DESCRIPTION: 3
  },
  SEARCH_QUERIES: {
    QUERY_ID: 1, QUERY_NAME: 2, GOOGLE_QUERY: 3, CATEGORY_ID: 4,
    SIGNAL_ID: 5, INDUSTRY_ID: 6, COUNTRY: 7, LANGUAGE: 8,
    FREQUENCY: 9, PRIORITY: 10, ENABLED: 11, LAST_RUN: 12,
    LAST_SUCCESS: 13, REMARKS: 14,
    // 'GoogleNews' (default) builds Google News RSS. 'RSS' treats GoogleQuery as a direct feed URL.
    SOURCE_TYPE: 15
  },
  CATEGORIES: {
    CATEGORY_ID: 1, CATEGORY: 2, DESCRIPTION: 3
  },
  SIGNALS: {
    SIGNAL_ID: 1, SIGNAL: 2, CATEGORY_ID: 3,
    DEFAULT_SCORE: 4, BUYING_INTENT: 5, ACTIVE: 6
  },
  KEYWORDS: {
    KEYWORD_ID: 1, KEYWORD: 2, SIGNAL_ID: 3,
    WEIGHT: 4, MATCH_TYPE: 5, ACTIVE: 6
  },
  INDUSTRIES: {
    INDUSTRY_ID: 1, INDUSTRY: 2, VERTICAL: 3,
    PARENT_INDUSTRY: 4, ACTIVE: 5
  },
  SOURCES: {
    SOURCE_ID: 1, SOURCE: 2, DOMAIN: 3,
    TYPE: 4, PRIORITY: 5, ACTIVE: 6
  },
  COMPANIES: {
    COMPANY_ID: 1, COMPANY_NAME: 2, WEBSITE: 3, INDUSTRY_ID: 4,
    COUNTRY: 5, STATE: 6, CITY: 7, NOTES: 8, ACTIVE: 9,
    // Extended profile columns (added via ISE > Companies > Migrate Companies Schema)
    DOMAIN: 10, LINKEDIN_URL: 11, EMPLOYEE_RANGE: 12, REVENUE_RANGE: 13, TARGET_ACCOUNT: 14
  },
  COMPANY_ALIASES: {
    ALIAS_ID: 1, COMPANY_ID: 2, ALIAS: 3
  },
  COMPANY_CANDIDATES: {
    CANDIDATE_ID: 1, COMPANY_NAME: 2, NEWS_ID: 3, TITLE: 4,
    URL: 5, CONFIDENCE: 6, SUGGESTED_INDUSTRY: 7, STATUS: 8,
    CREATED: 9, NOTES: 10
  },
  ICP_MASTER: {
    ICP_ID: 1, VERTICAL: 2, ICP_CODE: 3, SEGMENT: 4,
    SUB_SEGMENT: 5, PRODUCT_SERVICE: 6, PRIMARY_SERVICES_TO_PITCH: 7,
    CATEGORY: 8, TIER: 9, GEO: 10, EMPLOYEES: 11, REVENUE: 12
  },
  RAW_NEWS: {
    NEWS_ID: 1, FETCH_TIME: 2, PUBLISHED_DATE: 3, TITLE: 4,
    SNIPPET: 5, URL: 6, SOURCE_ID: 7, QUERY_ID: 8,
    HASH: 9, PROCESSED: 10
  },
  PROCESSED_NEWS: {
    NEWS_ID: 1, TITLE: 2, SUMMARY: 3, COMPANY: 4, INDUSTRY: 5,
    CATEGORY: 6, SIGNAL: 7, KEYWORD_MATCHED: 8, INTENT_SCORE: 9,
    PRIORITY: 10, PUBLISHED_DATE: 11, URL: 12, STATUS: 13
  },
  INTENT_SCORE: {
    NEWS_ID: 1, SIGNAL_SCORE: 2, KEYWORD_SCORE: 3,
    FRESHNESS_SCORE: 4, SOURCE_SCORE: 5, ICP_SCORE: 6, FINAL_SCORE: 7
  },
  ICP_MATCHING: {
    MATCH_ID: 1, NEWS_ID: 2, ICP_ID: 3, CONFIDENCE: 4, REASON: 5
  },
  PRIORITY_RULES: {
    RULE_ID: 1, SIGNAL: 2, CONDITION: 3, ADJUSTMENT: 4, REMARKS: 5
  },
  DUPLICATE_INDEX: {
    HASH: 1, NEWS_ID: 2, URL: 3, CREATED: 4
  },
  SYNC_LOG: {
    RUN_ID: 1, START_TIME: 2, END_TIME: 3, QUERY: 4,
    RECORDS_FETCHED: 5, RECORDS_INSERTED: 6, STATUS: 7, ERROR: 8
  },
  ACTION_QUEUE: {
    QUEUE_ID: 1, NEWS_ID: 2, COMPANY: 3, HEADLINE: 4, SIGNAL: 5,
    CATEGORY: 6, INTENT_SCORE: 7, ICP: 8, PUBLISHED_DATE: 9,
    URL: 10, ACTION_STATUS: 11, OWNER: 12, NOTES: 13
  },
  LOOKUP_TABLES: {
    TYPE: 1, VALUE: 2
  },
  DASHBOARD: {
    METRIC: 1, VALUE: 2
  },

  // Outreach Intelligence layer
  PITCH_PLAYBOOK: {
    PLAYBOOK_ID: 1, SIGNAL: 2, CATEGORY: 3, WHY_IT_MATTERS: 4,
    PITCH_ANGLE: 5, KEY_SERVICES: 6,
    TALK_TRACK_1: 7, TALK_TRACK_2: 8, TALK_TRACK_3: 9, PRIORITY: 10
  },
  OUTREACH_QUEUE: {
    OUTREACH_ID: 1, NEWS_ID: 2, COMPANY_NAME: 3, DOMAIN: 4, INDUSTRY: 5,
    HEADLINE: 6, SIGNAL: 7, CATEGORY: 8, INTENT_SCORE: 9, ICP_MATCH: 10,
    PITCH_ANGLE: 11, KEY_SERVICES: 12, TALK_TRACK: 13,
    PUBLISHED_DATE: 14, URL: 15,
    OUTREACH_STATUS: 16, OWNER: 17,
    CONTACT_NAME: 18, CONTACT_TITLE: 19, CONTACT_EMAIL: 20,
    OUTREACH_DATE: 21, FOLLOW_UP_DATE: 22, NOTES: 23
  },
  ACCOUNT_INTELLIGENCE: {
    ACCOUNT_ID: 1, COMPANY_NAME: 2, INDUSTRY: 3, ICP_MATCH: 4,
    TOTAL_SIGNALS: 5, SIGNAL_TYPES: 6, LAST_SIGNAL_DATE: 7,
    HIGHEST_SCORE: 8, TOP_SIGNAL: 9, TOP_PITCH_ANGLE: 10, KEY_SERVICES: 11,
    OUTREACH_READY: 12, LAST_OUTREACH_DATE: 13, NOTES: 14
  },
  TARGET_ACCOUNTS: {
    ACCOUNT_ID: 1, COMPANY_NAME: 2, DOMAIN: 3, INDUSTRY: 4,
    ICP_MATCH: 5, WATCH_PRIORITY: 6, AUTO_SEARCH: 7, NOTES: 8
  }
};

// ----------------------------------------------------------
// CONSTANTS
// ----------------------------------------------------------
var CONST = {
  VERSION:              '1.0.0',
  GOOGLE_NEWS_RSS_BASE: 'https://news.google.com/rss/search?q=',
  MAX_RSS_ITEMS:        20,
  HASH_ALGO:            'MD5',
  ACTION_STATUS_NEW:    'New',
  ACTION_STATUS_DONE:   'Completed',
  PRIORITY_HIGH:        'High',
  PRIORITY_MEDIUM:      'Medium',
  PRIORITY_LOW:         'Low',
  SCORE_HIGH_THRESHOLD: 70,
  SCORE_MED_THRESHOLD:  40,
  TRUE_VALUES:          ['TRUE', 'true', '1', 'yes', 'YES', true, 1],
  SOURCE_TYPE_GOOGLE:   'GoogleNews',
  SOURCE_TYPE_RSS:      'RSS'
};

// ----------------------------------------------------------
// SPREADSHEET HELPERS
// ----------------------------------------------------------

/**
 * Returns the active spreadsheet.
 */
function getSpreadsheet() {
  return SpreadsheetApp.getActiveSpreadsheet();
}

/**
 * Returns a sheet by logical name (from SHEETS map).
 * Throws a descriptive error if the sheet is missing.
 */
function getSheet(sheetKey) {
  var ss   = getSpreadsheet();
  var name = SHEETS[sheetKey];
  if (!name) throw new Error('Unknown sheet key: ' + sheetKey);
  var ws = ss.getSheetByName(name);
  if (!ws) throw new Error('Sheet not found: "' + name + '". Check your spreadsheet.');
  return ws;
}

/**
 * Returns all data rows (excludes header row 1) as a 2-D array.
 * Returns [] when the sheet has only a header.
 */
function getDataRows(sheetKey) {
  var ws = getSheet(sheetKey);
  var last = ws.getLastRow();
  if (last < 2) return [];
  return ws.getRange(2, 1, last - 1, ws.getLastColumn()).getValues();
}

/**
 * Appends a single row array to the bottom of a sheet.
 */
function appendRow(sheetKey, rowArray) {
  getSheet(sheetKey).appendRow(rowArray);
}

/**
 * Reads a specific cell (1-based row/col).
 */
function readCell(sheetKey, row, col) {
  return getSheet(sheetKey).getRange(row, col).getValue();
}

/**
 * Writes a value to a specific cell (1-based row/col).
 */
function writeCell(sheetKey, row, col, value) {
  getSheet(sheetKey).getRange(row, col).setValue(value);
}

/**
 * Updates specific columns in a given row.
 * colValueMap: { colNumber: value, ... }
 */
function updateRowCells(sheetKey, row, colValueMap) {
  var ws = getSheet(sheetKey);
  for (var col in colValueMap) {
    ws.getRange(row, parseInt(col)).setValue(colValueMap[col]);
  }
}

/**
 * Returns the next available ID for a sheet by finding the max
 * existing ID in colIndex (1-based) and adding 1.
 */
function getNextId(sheetKey, colIndex) {
  var rows = getDataRows(sheetKey);
  if (rows.length === 0) return 1;
  var ids = rows.map(function(r) {
    var v = parseInt(r[colIndex - 1]);
    return isNaN(v) ? 0 : v;
  });
  return Math.max.apply(null, ids) + 1;
}

/**
 * Checks whether a value is truthy per ISE convention.
 */
function isTruthy(val) {
  return CONST.TRUE_VALUES.indexOf(val) !== -1;
}

/**
 * Converts a column number to A1 letter notation.
 */
function colLetter(n) {
  var s = '';
  while (n > 0) {
    var m = (n - 1) % 26;
    s = String.fromCharCode(65 + m) + s;
    n = Math.floor((n - 1) / 26);
  }
  return s;
}

/**
 * Returns today's date as YYYY-MM-DD string.
 */
function todayString() {
  return Utilities.formatDate(new Date(), Session.getScriptTimeZone(), 'yyyy-MM-dd');
}

/**
 * Formats a Date object as ISO 8601 datetime string.
 */
function isoDateTime(d) {
  if (!d || !(d instanceof Date)) d = new Date();
  return Utilities.formatDate(d, Session.getScriptTimeZone(), "yyyy-MM-dd'T'HH:mm:ss");
}

/**
 * Safely parses a date from a string or returns null.
 */
function parseDate(str) {
  if (!str) return null;
  try {
    var d = new Date(str);
    return isNaN(d.getTime()) ? null : d;
  } catch(e) {
    return null;
  }
}

/**
 * Returns age of a date in days relative to now.
 */
function ageInDays(date) {
  if (!date) return 9999;
  var now = new Date();
  return (now - date) / (1000 * 60 * 60 * 24);
}

/**
 * Generates an MD5 hash string (hex) for duplicate detection.
 */
function md5Hash(input) {
  var raw  = Utilities.computeDigest(Utilities.DigestAlgorithm.MD5, String(input));
  var hex  = raw.map(function(b) {
    var h = (b < 0 ? b + 256 : b).toString(16);
    return h.length === 1 ? '0' + h : h;
  });
  return hex.join('');
}

/**
 * Strips HTML tags from a string.
 */
function stripHtml(str) {
  if (!str) return '';
  return String(str).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
}

/**
 * Truncates a string to maxLen, appending '…' if needed.
 */
function truncate(str, maxLen) {
  str = String(str || '');
  return str.length > maxLen ? str.substring(0, maxLen - 1) + '…' : str;
}
