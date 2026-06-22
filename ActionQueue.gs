// ============================================================
// ActionQueue.gs — Promotes high-intent processed news items
//   to the Action Queue sheet for sales/outreach action.
// ============================================================

var ActionQueue = (function() {

  // ----------------------------------------------------------
  // PUBLIC
  // ----------------------------------------------------------

  /**
   * Adds a processed item to the Action Queue if it meets
   * the minimum intent score threshold and is not already queued.
   *
   * @param {Object} processedItem  From ProcessedNewsManager.
   * @returns {number|null}  New QueueID, or null if not added.
   */
  function promote(processedItem) {
    var minScore = SettingsManager.getInt('MIN_INTENT_SCORE', 30);
    var score    = parseFloat(processedItem.intentScore) || 0;

    if (score < minScore) {
      logDebug('ActionQueue.promote: score ' + score + ' below threshold ' + minScore +
               ' for newsId ' + processedItem.newsId);
      return null;
    }

    if (_alreadyQueued(processedItem.newsId)) {
      logDebug('ActionQueue.promote: newsId ' + processedItem.newsId + ' already in queue');
      return null;
    }

    var id = getNextId('ACTION_QUEUE', COL.ACTION_QUEUE.QUEUE_ID);
    appendRow('ACTION_QUEUE', [
      id,
      processedItem.newsId,
      processedItem.company      || '',
      processedItem.title        || '',
      processedItem.signal       || '',
      processedItem.category     || '',
      processedItem.intentScore  || 0,
      processedItem.icpName      || '',   // populated if available
      processedItem.publishedDate || '',
      processedItem.url          || '',
      CONST.ACTION_STATUS_NEW,
      '',   // Owner — to be assigned manually
      ''    // Notes
    ]);

    logInfo('ActionQueue: promoted newsId ' + processedItem.newsId +
            ' (score ' + score + ', company: ' + (processedItem.company || 'unknown') + ')');
    return id;
  }

  /**
   * Bulk-promotes an array of processed items.
   * Returns count of items actually added.
   */
  function promoteBatch(processedItems) {
    var count = 0;
    processedItems.forEach(function(item) {
      var id = promote(item);
      if (id !== null) count++;
    });
    logInfo('ActionQueue.promoteBatch: added ' + count + ' of ' +
            processedItems.length + ' items');
    return count;
  }

  /**
   * Returns all action queue items, optionally filtered by status.
   */
  function getAll(statusFilter) {
    var rows = getDataRows('ACTION_QUEUE');
    return rows
      .map(_rowToItem)
      .filter(function(item) {
        if (!statusFilter) return true;
        return item.actionStatus === statusFilter;
      });
  }

  /**
   * Returns only new (unactioned) queue items.
   */
  function getNew() {
    return getAll(CONST.ACTION_STATUS_NEW);
  }

  /**
   * Updates the status and optionally owner/notes for a queue item.
   */
  function updateItem(queueId, status, owner, notes) {
    var ws   = getSheet('ACTION_QUEUE');
    var rows = ws.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][COL.ACTION_QUEUE.QUEUE_ID - 1]) === String(queueId)) {
        if (status) ws.getRange(i + 1, COL.ACTION_QUEUE.ACTION_STATUS).setValue(status);
        if (owner)  ws.getRange(i + 1, COL.ACTION_QUEUE.OWNER).setValue(owner);
        if (notes)  ws.getRange(i + 1, COL.ACTION_QUEUE.NOTES).setValue(notes);
        return;
      }
    }
    logWarn('ActionQueue.updateItem: queueId ' + queueId + ' not found');
  }

  /**
   * Returns queue stats: new, total, by company (top 5).
   */
  function getStats() {
    var rows     = getDataRows('ACTION_QUEUE');
    var total    = rows.length;
    var newCount = rows.filter(function(r) {
      return r[COL.ACTION_QUEUE.ACTION_STATUS - 1] === CONST.ACTION_STATUS_NEW;
    }).length;

    var companyCounts = {};
    rows.forEach(function(r) {
      var co = String(r[COL.ACTION_QUEUE.COMPANY - 1]);
      if (co) companyCounts[co] = (companyCounts[co] || 0) + 1;
    });

    var topCompanies = Object.keys(companyCounts)
      .sort(function(a, b) { return companyCounts[b] - companyCounts[a]; })
      .slice(0, 5)
      .map(function(co) { return { company: co, count: companyCounts[co] }; });

    return { total: total, new: newCount, topCompanies: topCompanies };
  }

  // ----------------------------------------------------------
  // PRIVATE
  // ----------------------------------------------------------

  function _alreadyQueued(newsId) {
    var rows = getDataRows('ACTION_QUEUE');
    for (var i = 0; i < rows.length; i++) {
      if (String(rows[i][COL.ACTION_QUEUE.NEWS_ID - 1]) === String(newsId)) return true;
    }
    return false;
  }

  function _rowToItem(r) {
    return {
      queueId:       r[COL.ACTION_QUEUE.QUEUE_ID - 1],
      newsId:        r[COL.ACTION_QUEUE.NEWS_ID - 1],
      company:       r[COL.ACTION_QUEUE.COMPANY - 1],
      headline:      r[COL.ACTION_QUEUE.HEADLINE - 1],
      signal:        r[COL.ACTION_QUEUE.SIGNAL - 1],
      category:      r[COL.ACTION_QUEUE.CATEGORY - 1],
      intentScore:   r[COL.ACTION_QUEUE.INTENT_SCORE - 1],
      icp:           r[COL.ACTION_QUEUE.ICP - 1],
      publishedDate: r[COL.ACTION_QUEUE.PUBLISHED_DATE - 1],
      url:           r[COL.ACTION_QUEUE.URL - 1],
      actionStatus:  r[COL.ACTION_QUEUE.ACTION_STATUS - 1],
      owner:         r[COL.ACTION_QUEUE.OWNER - 1],
      notes:         r[COL.ACTION_QUEUE.NOTES - 1]
    };
  }

  return { promote: promote, promoteBatch: promoteBatch,
           getAll: getAll, getNew: getNew, updateItem: updateItem, getStats: getStats };

})();
