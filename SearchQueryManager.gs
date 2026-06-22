// ============================================================
// SearchQueryManager.gs — Manages the Search Queries sheet.
//   Provides retrieval of active queries and tracks last-run
//   timestamps.
// ============================================================

var SearchQueryManager = (function() {

  // ----------------------------------------------------------
  // PUBLIC
  // ----------------------------------------------------------

  /**
   * Returns all active (Enabled = TRUE) search query objects.
   * Optionally filtered by frequency (e.g. 'Hourly', 'Daily').
   *
   * @param {string} [frequency]  Optional frequency filter.
   * @returns {Object[]}
   */
  function getActiveQueries(frequency) {
    var rows = getDataRows('SEARCH_QUERIES');
    return rows
      .map(_rowToQuery)
      .filter(function(q) {
        if (!q.enabled) return false;
        if (frequency && q.frequency !== frequency) return false;
        return true;
      });
  }

  /**
   * Returns a single query object by ID, or null.
   */
  function getById(queryId) {
    var rows = getDataRows('SEARCH_QUERIES');
    for (var i = 0; i < rows.length; i++) {
      if (String(rows[i][COL.SEARCH_QUERIES.QUERY_ID - 1]) === String(queryId)) {
        return _rowToQuery(rows[i]);
      }
    }
    return null;
  }

  /**
   * Returns all query objects (enabled and disabled).
   */
  function getAll() {
    return getDataRows('SEARCH_QUERIES').map(_rowToQuery);
  }

  /**
   * Marks a query's LastRun timestamp to now.
   * Also sets LastSuccess if successful = true.
   */
  function markRun(queryId, successful) {
    var ws   = getSheet('SEARCH_QUERIES');
    var rows = ws.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][COL.SEARCH_QUERIES.QUERY_ID - 1]) === String(queryId)) {
        var now = isoDateTime(new Date());
        ws.getRange(i + 1, COL.SEARCH_QUERIES.LAST_RUN).setValue(now);
        if (successful) {
          ws.getRange(i + 1, COL.SEARCH_QUERIES.LAST_SUCCESS).setValue(now);
        }
        return;
      }
    }
    logWarn('SearchQueryManager.markRun: queryId not found: ' + queryId);
  }

  /**
   * Adds a new search query row.
   * @param {Object} q  Query fields.
   * @returns {number}  New QueryID.
   */
  function add(q) {
    var id = getNextId('SEARCH_QUERIES', COL.SEARCH_QUERIES.QUERY_ID);
    appendRow('SEARCH_QUERIES', [
      id,
      q.queryName    || '',
      q.googleQuery  || '',
      q.categoryId   || '',
      q.signalId     || '',
      q.industryId   || '',
      q.country      || SettingsManager.get('DEFAULT_COUNTRY', 'US'),
      q.language     || SettingsManager.get('DEFAULT_LANGUAGE', 'en'),
      q.frequency    || 'Daily',
      q.priority     || CONST.PRIORITY_MEDIUM,
      q.enabled !== undefined ? q.enabled : true,
      '',
      '',
      q.remarks    || '',
      q.sourceType || CONST.SOURCE_TYPE_GOOGLE
    ]);
    logInfo('SearchQueryManager: added query "' + q.queryName + '" (ID ' + id + ')');
    return id;
  }

  /**
   * Builds the RSS URL for a query object.
   * When SOURCE_TYPE is 'RSS', the GoogleQuery column holds the full feed URL directly.
   * Otherwise, builds a standard Google News RSS URL.
   */
  function buildRssUrl(query) {
    var sourceType = String(query.sourceType || CONST.SOURCE_TYPE_GOOGLE).trim();

    if (sourceType === CONST.SOURCE_TYPE_RSS) {
      return query.googleQuery;  // caller-supplied full URL
    }

    // Default: Google News RSS
    var base    = CONST.GOOGLE_NEWS_RSS_BASE;
    var q       = encodeURIComponent(query.googleQuery);
    var country = query.country || SettingsManager.get('DEFAULT_COUNTRY', 'US');
    var lang    = query.language || SettingsManager.get('DEFAULT_LANGUAGE', 'en');
    return base + q + '&hl=' + lang + '&gl=' + country + '&ceid=' + country + ':' + lang;
  }

  // ----------------------------------------------------------
  // PRIVATE
  // ----------------------------------------------------------

  function _rowToQuery(r) {
    return {
      queryId:     r[COL.SEARCH_QUERIES.QUERY_ID - 1],
      queryName:   r[COL.SEARCH_QUERIES.QUERY_NAME - 1],
      googleQuery: r[COL.SEARCH_QUERIES.GOOGLE_QUERY - 1],
      categoryId:  r[COL.SEARCH_QUERIES.CATEGORY_ID - 1],
      signalId:    r[COL.SEARCH_QUERIES.SIGNAL_ID - 1],
      industryId:  r[COL.SEARCH_QUERIES.INDUSTRY_ID - 1],
      country:     r[COL.SEARCH_QUERIES.COUNTRY - 1],
      language:    r[COL.SEARCH_QUERIES.LANGUAGE - 1],
      frequency:   r[COL.SEARCH_QUERIES.FREQUENCY - 1],
      priority:    r[COL.SEARCH_QUERIES.PRIORITY - 1],
      enabled:     isTruthy(r[COL.SEARCH_QUERIES.ENABLED - 1]),
      lastRun:     r[COL.SEARCH_QUERIES.LAST_RUN - 1],
      lastSuccess: r[COL.SEARCH_QUERIES.LAST_SUCCESS - 1],
      remarks:     r[COL.SEARCH_QUERIES.REMARKS - 1],
      sourceType:  r.length >= COL.SEARCH_QUERIES.SOURCE_TYPE
                   ? String(r[COL.SEARCH_QUERIES.SOURCE_TYPE - 1] || '').trim() || CONST.SOURCE_TYPE_GOOGLE
                   : CONST.SOURCE_TYPE_GOOGLE
    };
  }

  return { getActiveQueries: getActiveQueries, getById: getById, getAll: getAll,
           markRun: markRun, add: add, buildRssUrl: buildRssUrl };

})();
