// ============================================================
// RawNewsManager.gs — Persists raw (un-processed) news items
//   to the Raw News sheet and provides read helpers.
// ============================================================

var RawNewsManager = (function() {

  // ----------------------------------------------------------
  // PUBLIC
  // ----------------------------------------------------------

  /**
   * Writes an array of new (deduplicated) news items to Raw News.
   * Also registers each item in the Duplicate Index.
   *
   * @param {Object[]} items   Items from RSSParser (after duplicate filter).
   * @param {string}   sourceId  Optional source ID to stamp.
   * @returns {number}         Count of items written.
   */
  function insertBatch(items, sourceId) {
    var written = 0;
    items.forEach(function(item) {
      try {
        var id = _insert(item, sourceId);
        item.newsId = id;  // stamp the ID back onto the item for downstream use
        DuplicateChecker.register(item.hash, id, item.url);
        written++;
      } catch(e) {
        logError('RawNewsManager.insertBatch: failed to insert item "' +
                 item.title + '": ' + e.message);
      }
    });
    logInfo('RawNewsManager: inserted ' + written + ' of ' + items.length + ' items');
    return written;
  }

  /**
   * Returns all unprocessed raw news rows as objects.
   */
  function getUnprocessed() {
    var rows = getDataRows('RAW_NEWS');
    return rows
      .filter(function(r) { return !isTruthy(r[COL.RAW_NEWS.PROCESSED - 1]); })
      .map(_rowToItem);
  }

  /**
   * Returns a raw news item by NewsID, or null.
   */
  function getById(newsId) {
    var rows = getDataRows('RAW_NEWS');
    for (var i = 0; i < rows.length; i++) {
      if (String(rows[i][COL.RAW_NEWS.NEWS_ID - 1]) === String(newsId)) {
        return _rowToItem(rows[i]);
      }
    }
    return null;
  }

  /**
   * Marks a raw news item as processed.
   */
  function markProcessed(newsId) {
    var ws   = getSheet('RAW_NEWS');
    var rows = ws.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][COL.RAW_NEWS.NEWS_ID - 1]) === String(newsId)) {
        ws.getRange(i + 1, COL.RAW_NEWS.PROCESSED).setValue(true);
        return;
      }
    }
    logWarn('RawNewsManager.markProcessed: newsId not found: ' + newsId);
  }

  /**
   * Returns count of total and unprocessed items.
   */
  function getCounts() {
    var rows   = getDataRows('RAW_NEWS');
    var unproc = rows.filter(function(r) {
      return !isTruthy(r[COL.RAW_NEWS.PROCESSED - 1]);
    }).length;
    return { total: rows.length, unprocessed: unproc };
  }

  // ----------------------------------------------------------
  // PRIVATE
  // ----------------------------------------------------------

  function _insert(item, sourceId) {
    var id = getNextId('RAW_NEWS', COL.RAW_NEWS.NEWS_ID);
    appendRow('RAW_NEWS', [
      id,
      isoDateTime(new Date()),          // FetchTime
      item.publishedDate || '',         // PublishedDate
      item.title         || '',
      item.snippet       || '',
      item.url           || '',
      sourceId           || _resolveSourceId(item.sourceName),
      item.queryId       || '',
      item.hash          || '',
      false                             // Processed
    ]);
    return id;
  }

  function _rowToItem(r) {
    return {
      newsId:        r[COL.RAW_NEWS.NEWS_ID - 1],
      fetchTime:     r[COL.RAW_NEWS.FETCH_TIME - 1],
      publishedDate: r[COL.RAW_NEWS.PUBLISHED_DATE - 1],
      title:         r[COL.RAW_NEWS.TITLE - 1],
      snippet:       r[COL.RAW_NEWS.SNIPPET - 1],
      url:           r[COL.RAW_NEWS.URL - 1],
      sourceId:      r[COL.RAW_NEWS.SOURCE_ID - 1],
      queryId:       r[COL.RAW_NEWS.QUERY_ID - 1],
      hash:          r[COL.RAW_NEWS.HASH - 1],
      processed:     isTruthy(r[COL.RAW_NEWS.PROCESSED - 1])
    };
  }

  /**
   * Attempts to look up a SourceID from the Sources sheet by domain/name.
   */
  function _resolveSourceId(sourceName) {
    if (!sourceName) return '';
    var rows = getDataRows('SOURCES');
    for (var i = 0; i < rows.length; i++) {
      if (String(rows[i][COL.SOURCES.SOURCE - 1]).toLowerCase() ===
          sourceName.toLowerCase()) {
        return rows[i][COL.SOURCES.SOURCE_ID - 1];
      }
    }
    return '';
  }

  return { insertBatch: insertBatch, getUnprocessed: getUnprocessed,
           getById: getById, markProcessed: markProcessed, getCounts: getCounts };

})();
