// ============================================================
// Dashboard.gs — Refreshes the Dashboard sheet with the latest
//   metrics.  Called at end of each pipeline run and on demand.
// ============================================================

var Dashboard = (function() {

  // ----------------------------------------------------------
  // PUBLIC
  // ----------------------------------------------------------

  /**
   * Refreshes all dashboard metrics.
   */
  function refresh() {
    logInfo('Dashboard: refreshing...');
    try {
      var ws   = getSheet('DASHBOARD');
      var data = _collectMetrics();

      // Write each metric to its row (find by metric name)
      var rows = ws.getDataRange().getValues();
      var metricRow = {};  // metricName -> rowIndex (1-based)
      for (var i = 1; i < rows.length; i++) {
        metricRow[rows[i][COL.DASHBOARD.METRIC - 1]] = i + 1;
      }

      data.forEach(function(entry) {
        if (metricRow[entry.metric]) {
          ws.getRange(metricRow[entry.metric], COL.DASHBOARD.VALUE).setValue(entry.value);
        }
      });

      logInfo('Dashboard: refresh complete');
    } catch(e) {
      logError('Dashboard.refresh failed: ' + e.message);
    }
  }

  /**
   * Returns the current dashboard metrics as an array of { metric, value }.
   */
  function getMetrics() {
    return _collectMetrics();
  }

  // ----------------------------------------------------------
  // PRIVATE
  // ----------------------------------------------------------

  function _collectMetrics() {
    var rawCounts   = RawNewsManager.getCounts();
    var priCounts   = ProcessedNewsManager.getPriorityCounts();
    var queueStats  = ActionQueue.getStats();
    var recentRuns  = SyncLog.getRecentRuns(1);
    var lastSync    = recentRuns.length ? recentRuns[recentRuns.length - 1].endTime : '';

    // Top signal
    var processedAll = ProcessedNewsManager.getAll();
    var signalTally  = {};
    var catTally     = {};
    processedAll.forEach(function(item) {
      if (item.signal)   signalTally[item.signal]   = (signalTally[item.signal]   || 0) + 1;
      if (item.category) catTally[item.category]    = (catTally[item.category]    || 0) + 1;
    });

    var topSignal = _topKey(signalTally);
    var topCat    = _topKey(catTally);

    // Today's news
    var today      = todayString();
    var todayCount = 0;
    getDataRows('RAW_NEWS').forEach(function(r) {
      var ft = String(r[COL.RAW_NEWS.FETCH_TIME - 1]);
      if (ft.indexOf(today) === 0) todayCount++;
    });

    var outreachStats  = _safeOutreachStats();
    var accountsReady  = _safeAccountsReady();
    var targetCount    = _safeTargetCount();

    return [
      { metric: 'Total News',           value: rawCounts.total },
      { metric: "Today's News",         value: todayCount },
      { metric: 'High Intent',          value: priCounts.high },
      { metric: 'Medium Intent',        value: priCounts.medium },
      { metric: 'Low Intent',           value: priCounts.low },
      { metric: 'Top Signal',           value: topSignal },
      { metric: 'Top Category',         value: topCat },
      { metric: 'Last Sync',            value: lastSync },
      { metric: 'Action Queue',         value: queueStats.new + ' new / ' + queueStats.total + ' total' },
      { metric: 'Unprocessed',          value: rawCounts.unprocessed },
      { metric: 'Outreach Queue – New', value: outreachStats.new + ' new / ' + outreachStats.total + ' total' },
      { metric: 'Accounts Ready',       value: accountsReady },
      { metric: 'Target Accounts',      value: targetCount }
    ];
  }

  function _topKey(tally) {
    var best = '', bestCount = 0;
    for (var k in tally) {
      if (tally[k] > bestCount) { bestCount = tally[k]; best = k; }
    }
    return best;
  }

  function _safeOutreachStats() {
    try { return OutreachQueue.getStats(); } catch(e) { return { new: 0, total: 0 }; }
  }

  function _safeAccountsReady() {
    try { return AccountIntelligence.getReadyCount(); } catch(e) { return 0; }
  }

  function _safeTargetCount() {
    try { return TargetAccounts.getCount(); } catch(e) { return 0; }
  }

  return { refresh: refresh, getMetrics: getMetrics };

})();
