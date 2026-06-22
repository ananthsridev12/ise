// ============================================================
// SyncLog.gs — Per-run tracking.  Each automated or manual run
//               gets one row in the Sync Log sheet.
// ============================================================

var SyncLog = (function() {

  // ----------------------------------------------------------
  // PUBLIC
  // ----------------------------------------------------------

  /**
   * Opens a new run record and returns a context object.
   * Call this at the very beginning of a pipeline run.
   *
   * @param {string} queryName  Human-readable name for what's running.
   * @returns {Object} ctx  — pass to endRun() / failRun().
   */
  function startRun(queryName) {
    var id  = getNextId('SYNC_LOG', COL.SYNC_LOG.RUN_ID);
    var now = new Date();
    var ctx = {
      runId:      id,
      query:      queryName || 'manual',
      startTime:  now,
      rowIndex:   null   // filled after append
    };

    var ws = getSheet('SYNC_LOG');
    ws.appendRow([
      id,
      isoDateTime(now),
      '',                       // EndTime — filled on close
      ctx.query,
      0,                        // RecordsFetched
      0,                        // RecordsInserted
      'Running',
      ''                        // Error
    ]);

    ctx.rowIndex = ws.getLastRow();
    setLogContext(ctx);
    logInfo('Run started: ' + queryName, { runId: id });
    return ctx;
  }

  /**
   * Closes a run as successful.
   *
   * @param {Object} ctx          Context from startRun().
   * @param {number} fetched      Total records fetched from RSS.
   * @param {number} inserted     Total records written to Raw News.
   */
  function endRun(ctx, fetched, inserted) {
    if (!ctx || !ctx.rowIndex) return;
    var ws  = getSheet('SYNC_LOG');
    var now = new Date();
    updateRowCells('SYNC_LOG', ctx.rowIndex, {
      [COL.SYNC_LOG.END_TIME]:         isoDateTime(now),
      [COL.SYNC_LOG.RECORDS_FETCHED]:  fetched  || 0,
      [COL.SYNC_LOG.RECORDS_INSERTED]: inserted || 0,
      [COL.SYNC_LOG.STATUS]:           'Success'
    });
    logInfo('Run ended successfully', { runId: ctx.runId, fetched: fetched, inserted: inserted });
    clearLogContext();
  }

  /**
   * Closes a run as failed.
   *
   * @param {Object} ctx    Context from startRun().
   * @param {Error}  err    The caught error object.
   */
  function failRun(ctx, err) {
    if (!ctx || !ctx.rowIndex) return;
    var msg = err ? err.message : 'Unknown error';
    updateRowCells('SYNC_LOG', ctx.rowIndex, {
      [COL.SYNC_LOG.END_TIME]: isoDateTime(new Date()),
      [COL.SYNC_LOG.STATUS]:   'Error',
      [COL.SYNC_LOG.ERROR]:    truncate(msg, 500)
    });
    logError('Run failed: ' + msg, { runId: ctx.runId });
    clearLogContext();
  }

  /**
   * Returns the last N log rows as objects.
   */
  function getRecentRuns(n) {
    n = n || 20;
    var rows = getDataRows('SYNC_LOG');
    return rows.slice(-n).map(function(r) {
      return {
        runId:     r[COL.SYNC_LOG.RUN_ID - 1],
        startTime: r[COL.SYNC_LOG.START_TIME - 1],
        endTime:   r[COL.SYNC_LOG.END_TIME - 1],
        query:     r[COL.SYNC_LOG.QUERY - 1],
        fetched:   r[COL.SYNC_LOG.RECORDS_FETCHED - 1],
        inserted:  r[COL.SYNC_LOG.RECORDS_INSERTED - 1],
        status:    r[COL.SYNC_LOG.STATUS - 1],
        error:     r[COL.SYNC_LOG.ERROR - 1]
      };
    });
  }

  /**
   * Purges log rows older than retainDays (default 30).
   */
  function purgeLogs(retainDays) {
    retainDays = retainDays || 30;
    var ws   = getSheet('SYNC_LOG');
    var rows = getDataRows('SYNC_LOG');
    var cutoff = new Date();
    cutoff.setDate(cutoff.getDate() - retainDays);
    var toDelete = [];
    rows.forEach(function(r, i) {
      var d = parseDate(r[COL.SYNC_LOG.START_TIME - 1]);
      if (d && d < cutoff) toDelete.push(i + 2); // +2 for header + 0-index
    });
    // Delete from bottom up to preserve row indices
    for (var i = toDelete.length - 1; i >= 0; i--) {
      ws.deleteRow(toDelete[i]);
    }
    logInfo('Purged ' + toDelete.length + ' old log rows');
  }

  return { startRun: startRun, endRun: endRun, failRun: failRun,
           getRecentRuns: getRecentRuns, purgeLogs: purgeLogs };

})();
