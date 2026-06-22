// ============================================================
// AccountIntelligence.gs — Per-company signal aggregation view.
//   Reads the Outreach Queue, groups rows by CompanyName, and
//   writes/updates one row per company in Account Intelligence.
//
//   Called automatically at the end of every pipeline run.
//   Run manually via ISE > Outreach > Refresh Account Intelligence.
// ============================================================

var AccountIntelligence = (function() {

  var HEADERS = [
    'AccountID', 'CompanyName', 'Industry', 'ICPMatch',
    'TotalSignals', 'SignalTypes', 'LastSignalDate',
    'HighestScore', 'TopSignal', 'TopPitchAngle', 'KeyServices',
    'OutreachReady', 'LastOutreachDate', 'Notes'
  ];

  var OUTREACH_COOLDOWN_DAYS = 30;

  // ----------------------------------------------------------
  // PUBLIC
  // ----------------------------------------------------------

  /**
   * Rebuilds the Account Intelligence sheet from the current
   * Outreach Queue.  Existing rows are updated in place;
   * new companies are appended.
   */
  function refresh() {
    try {
      var items = OutreachQueue.getAll();
      if (!items.length) {
        logInfo('AccountIntelligence.refresh: Outreach Queue is empty — nothing to aggregate');
        return;
      }

      var ws      = _getOrCreateSheet();
      var minScore = SettingsManager.getInt('MIN_INTENT_SCORE', 30);

      // Build per-company aggregates from Outreach Queue
      var companyMap = _aggregate(items);

      // Load existing rows to support in-place updates
      var existingRows = ws.getLastRow() > 1
        ? ws.getRange(2, 1, ws.getLastRow() - 1, ws.getLastColumn()).getValues()
        : [];
      var rowIndex = {};  // companyKey -> sheetRowNumber (1-based)
      existingRows.forEach(function(r, i) {
        var key = _key(r[COL.ACCOUNT_INTELLIGENCE.COMPANY_NAME - 1]);
        if (key) rowIndex[key] = i + 2;
      });

      var nextId = _nextId(ws);

      // Determine last outreach dates from existing rows (manually entered in sheet)
      var lastOutreachMap = {};
      existingRows.forEach(function(r) {
        var key = _key(r[COL.ACCOUNT_INTELLIGENCE.COMPANY_NAME - 1]);
        var lastOut = r[COL.ACCOUNT_INTELLIGENCE.LAST_OUTREACH_DATE - 1];
        if (key && lastOut) lastOutreachMap[key] = lastOut;
      });

      // Write or update each company
      for (var coKey in companyMap) {
        var agg  = companyMap[coKey];
        var lastOut = lastOutreachMap[coKey] || '';
        var ready   = _isOutreachReady(agg.highestScore, minScore, lastOut);

        var rowData = [
          '',                           // AccountID — filled below
          agg.companyName,
          agg.industry,
          agg.icpMatch,
          agg.totalSignals,
          agg.signalTypes.join(', '),
          agg.lastSignalDate,
          agg.highestScore,
          agg.topSignal,
          agg.topPitchAngle,
          agg.keyServices,
          ready ? 'YES' : 'NO',
          lastOut,
          ''                            // Notes (preserve existing)
        ];

        if (rowIndex[coKey]) {
          // Update existing row in-place (preserve AccountID and Notes)
          var existingRow = existingRows[rowIndex[coKey] - 2];
          rowData[0] = existingRow[COL.ACCOUNT_INTELLIGENCE.ACCOUNT_ID - 1];
          rowData[13] = existingRow[COL.ACCOUNT_INTELLIGENCE.NOTES - 1];
          ws.getRange(rowIndex[coKey], 1, 1, rowData.length).setValues([rowData]);
        } else {
          // Append new row
          rowData[0] = nextId++;
          ws.appendRow(rowData);
        }
      }

      logInfo('AccountIntelligence.refresh: ' + Object.keys(companyMap).length + ' companies updated');
    } catch(e) {
      logError('AccountIntelligence.refresh failed: ' + e.message);
    }
  }

  /**
   * Returns the count of companies marked OutreachReady = YES.
   */
  function getReadyCount() {
    var ws = getSpreadsheet().getSheetByName(SHEETS.ACCOUNT_INTELLIGENCE);
    if (!ws || ws.getLastRow() < 2) return 0;
    var vals = ws.getRange(2, COL.ACCOUNT_INTELLIGENCE.OUTREACH_READY,
                           ws.getLastRow() - 1, 1).getValues();
    var count = 0;
    vals.forEach(function(r) {
      if (String(r[0] || '').toUpperCase() === 'YES') count++;
    });
    return count;
  }

  /**
   * Creates the Account Intelligence sheet if it does not exist.
   */
  function ensureSheet() {
    _getOrCreateSheet();
  }

  // ----------------------------------------------------------
  // PRIVATE
  // ----------------------------------------------------------

  function _aggregate(items) {
    var map = {};

    items.forEach(function(item) {
      var name = String(item.companyName || '').trim();
      if (!name) return;
      var key = _key(name);

      if (!map[key]) {
        map[key] = {
          companyName:   name,
          industry:      item.industry || '',
          icpMatch:      item.icpMatch || '',
          totalSignals:  0,
          signalTypes:   [],
          lastSignalDate: '',
          highestScore:  0,
          topSignal:     '',
          topPitchAngle: '',
          keyServices:   ''
        };
      }

      var entry = map[key];
      entry.totalSignals++;

      var sig = String(item.signal || '').trim();
      if (sig && entry.signalTypes.indexOf(sig) === -1) {
        entry.signalTypes.push(sig);
      }

      var score = parseFloat(item.intentScore) || 0;
      if (score > entry.highestScore) {
        entry.highestScore  = score;
        entry.topSignal     = sig;
        entry.topPitchAngle = String(item.pitchAngle || '');
        entry.keyServices   = String(item.keyServices || '');
      }

      if (item.industry && !entry.industry)  entry.industry = item.industry;
      if (item.icpMatch  && !entry.icpMatch) entry.icpMatch = item.icpMatch;

      var pubDate = item.publishedDate ? String(item.publishedDate) : '';
      if (pubDate && (!entry.lastSignalDate || pubDate > entry.lastSignalDate)) {
        entry.lastSignalDate = pubDate;
      }
    });

    return map;
  }

  function _isOutreachReady(highestScore, minScore, lastOutreachDate) {
    if (highestScore < minScore) return false;
    if (!lastOutreachDate) return true;
    var lastOut = new Date(lastOutreachDate);
    if (isNaN(lastOut.getTime())) return true;
    return ageInDays(lastOut) > OUTREACH_COOLDOWN_DAYS;
  }

  function _getOrCreateSheet() {
    var ss = getSpreadsheet();
    var ws = ss.getSheetByName(SHEETS.ACCOUNT_INTELLIGENCE);
    if (!ws) {
      ws = ss.insertSheet(SHEETS.ACCOUNT_INTELLIGENCE);
      ws.getRange(1, 1, 1, HEADERS.length).setValues([HEADERS]);
      ws.setFrozenRows(1);
    }
    return ws;
  }

  function _nextId(ws) {
    if (ws.getLastRow() < 2) return 1;
    var ids = ws.getRange(2, COL.ACCOUNT_INTELLIGENCE.ACCOUNT_ID,
                          ws.getLastRow() - 1, 1).getValues();
    var max = 0;
    ids.forEach(function(r) {
      var v = parseInt(r[0]);
      if (!isNaN(v) && v > max) max = v;
    });
    return max + 1;
  }

  function _key(name) {
    return String(name || '').toLowerCase().trim().replace(/\s+/g, ' ');
  }

  return { refresh: refresh, getReadyCount: getReadyCount, ensureSheet: ensureSheet };

})();
