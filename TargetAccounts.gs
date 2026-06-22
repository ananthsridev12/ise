// ============================================================
// TargetAccounts.gs — Watch list of priority accounts.
//   When AutoSearch = TRUE, the system auto-generates Google News
//   search queries for that company so it appears in pipeline runs.
//
//   Use ISE > Outreach > Setup Target Accounts to create the sheet.
//   Use ISE > Outreach > Generate Watch Queries to create queries.
// ============================================================

var TargetAccounts = (function() {

  var HEADERS = [
    'AccountID', 'CompanyName', 'Domain', 'Industry',
    'ICPMatch', 'WatchPriority', 'AutoSearch', 'Notes'
  ];

  // Three query templates generated per watched account
  var QUERY_TEMPLATES = [
    { suffix: ' – Hiring',     template: '"{name}" hiring OR expansion OR "new plant"',           remarks: 'Auto: growth and hiring signals' },
    { suffix: ' – Technology', template: '"{name}" ERP OR SAP OR automation OR "digital transformation"', remarks: 'Auto: technology adoption signals' },
    { suffix: ' – M&A',        template: '"{name}" acquisition OR merger OR investment OR funding', remarks: 'Auto: M&A and financial signals' }
  ];

  // ----------------------------------------------------------
  // PUBLIC
  // ----------------------------------------------------------

  /**
   * Creates the Target Accounts sheet if it does not exist.
   */
  function ensureSheet() {
    _getOrCreateSheet();
  }

  /**
   * Returns the count of accounts in the Target Accounts sheet.
   */
  function getCount() {
    var ws = getSpreadsheet().getSheetByName(SHEETS.TARGET_ACCOUNTS);
    if (!ws || ws.getLastRow() < 2) return 0;
    return ws.getLastRow() - 1;
  }

  /**
   * For every Target Account with AutoSearch = TRUE, creates up to
   * 3 Google News search queries if they don't already exist.
   *
   * @returns {number}  Number of new queries created.
   */
  function generateWatchQueries() {
    var ws = getSpreadsheet().getSheetByName(SHEETS.TARGET_ACCOUNTS);
    if (!ws || ws.getLastRow() < 2) {
      logInfo('TargetAccounts.generateWatchQueries: no accounts found');
      return 0;
    }

    var rows    = ws.getRange(2, 1, ws.getLastRow() - 1, ws.getLastColumn()).getValues();
    var created = 0;

    // Build existing query name set for duplicate detection
    var existingQueryNames = {};
    SearchQueryManager.getAll().forEach(function(q) {
      existingQueryNames[q.queryName.toLowerCase()] = true;
    });

    rows.forEach(function(r) {
      if (!isTruthy(r[COL.TARGET_ACCOUNTS.AUTO_SEARCH - 1])) return;

      var name = String(r[COL.TARGET_ACCOUNTS.COMPANY_NAME - 1] || '').trim();
      if (!name) return;

      QUERY_TEMPLATES.forEach(function(tmpl) {
        var queryName = name + tmpl.suffix;
        if (existingQueryNames[queryName.toLowerCase()]) return;

        var googleQuery = tmpl.template.replace('{name}', name);
        SearchQueryManager.add({
          queryName:   queryName,
          googleQuery: googleQuery,
          frequency:   'Daily',
          priority:    'High',
          remarks:     tmpl.remarks
        });

        existingQueryNames[queryName.toLowerCase()] = true;
        created++;
        logInfo('TargetAccounts: created query "' + queryName + '"');
      });
    });

    logInfo('TargetAccounts.generateWatchQueries: created ' + created + ' queries');
    return created;
  }

  // ----------------------------------------------------------
  // PRIVATE
  // ----------------------------------------------------------

  function _getOrCreateSheet() {
    var ss = getSpreadsheet();
    var ws = ss.getSheetByName(SHEETS.TARGET_ACCOUNTS);
    if (!ws) {
      ws = ss.insertSheet(SHEETS.TARGET_ACCOUNTS);
      ws.getRange(1, 1, 1, HEADERS.length).setValues([HEADERS]);
      ws.setFrozenRows(1);
      // Add a sample row as a guide
      ws.appendRow([1, 'Example Corp', 'example.com', 'Manufacturing', '', 'High', 'TRUE', 'Sample entry — replace with real accounts']);
    }
    return ws;
  }

  return { ensureSheet: ensureSheet, getCount: getCount, generateWatchQueries: generateWatchQueries };

})();
