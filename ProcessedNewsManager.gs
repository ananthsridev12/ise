// ============================================================
// ProcessedNewsManager.gs — Writes enriched, classified news
//   items to the Processed News sheet and provides read helpers.
// ============================================================

var ProcessedNewsManager = (function() {

  // ----------------------------------------------------------
  // PUBLIC
  // ----------------------------------------------------------

  /**
   * Writes a fully-processed news record.
   *
   * @param {Object} item         Raw news item.
   * @param {Object} coMatch      CompanyExtractor result (may be null).
   * @param {Object} classification  SignalClassifier result.
   * @param {Object[]} kwMatches  KeywordMatcher results.
   * @param {Object} scoreObj     IntentScoreEngine result.
   * @param {Object} icpMatch     ICPMatcher result (may be null).
   * @returns {number}  The raw NewsID (same as item.newsId).
   */
  function insert(item, coMatch, classification, kwMatches, scoreObj, icpMatch) {
    var existing = _findRow(item.newsId);
    var row = [
      item.newsId,
      item.title         || '',
      item.snippet       || '',
      coMatch  ? coMatch.companyName : '',
      coMatch  ? CompanyExtractor.getIndustryName(coMatch.industryId) : '',
      classification.category  || '',
      classification.signal    || '',
      KeywordMatcher.matchedKeywordString(kwMatches),
      scoreObj.finalScore,
      scoreObj.priority,
      item.publishedDate || '',
      item.url           || '',
      CONST.ACTION_STATUS_NEW
    ];

    if (existing) {
      var ws = getSheet('PROCESSED_NEWS');
      ws.getRange(existing, 1, 1, row.length).setValues([row]);
    } else {
      appendRow('PROCESSED_NEWS', row);
    }

    return item.newsId;
  }

  /**
   * Returns all processed news items as objects.
   * Optionally filter by status.
   */
  function getAll(statusFilter) {
    var rows = getDataRows('PROCESSED_NEWS');
    return rows
      .map(_rowToItem)
      .filter(function(item) {
        if (!statusFilter) return true;
        return item.status === statusFilter;
      });
  }

  /**
   * Returns a processed news item by NewsID, or null.
   */
  function getById(newsId) {
    var rows = getDataRows('PROCESSED_NEWS');
    for (var i = 0; i < rows.length; i++) {
      if (String(rows[i][COL.PROCESSED_NEWS.NEWS_ID - 1]) === String(newsId)) {
        return _rowToItem(rows[i]);
      }
    }
    return null;
  }

  /**
   * Updates the status of a processed news item.
   */
  function updateStatus(newsId, status) {
    var ws   = getSheet('PROCESSED_NEWS');
    var rows = ws.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][COL.PROCESSED_NEWS.NEWS_ID - 1]) === String(newsId)) {
        ws.getRange(i + 1, COL.PROCESSED_NEWS.STATUS).setValue(status);
        return;
      }
    }
  }

  /**
   * Returns items whose intent score meets the minimum threshold.
   */
  function getHighIntent() {
    var minScore = SettingsManager.getInt('MIN_INTENT_SCORE', 30);
    return getAll().filter(function(item) {
      return parseFloat(item.intentScore) >= minScore;
    });
  }

  /**
   * Returns counts grouped by priority.
   */
  function getPriorityCounts() {
    var rows  = getDataRows('PROCESSED_NEWS');
    var high  = 0, med = 0, low = 0;
    rows.forEach(function(r) {
      var p = String(r[COL.PROCESSED_NEWS.PRIORITY - 1]);
      if (p === CONST.PRIORITY_HIGH)   high++;
      else if (p === CONST.PRIORITY_MEDIUM) med++;
      else low++;
    });
    return { high: high, medium: med, low: low, total: rows.length };
  }

  // ----------------------------------------------------------
  // PRIVATE
  // ----------------------------------------------------------

  function _findRow(newsId) {
    var ws   = getSheet('PROCESSED_NEWS');
    var rows = ws.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][COL.PROCESSED_NEWS.NEWS_ID - 1]) === String(newsId)) {
        return i + 1;  // 1-based row index
      }
    }
    return null;
  }

  function _rowToItem(r) {
    return {
      newsId:         r[COL.PROCESSED_NEWS.NEWS_ID - 1],
      title:          r[COL.PROCESSED_NEWS.TITLE - 1],
      summary:        r[COL.PROCESSED_NEWS.SUMMARY - 1],
      company:        r[COL.PROCESSED_NEWS.COMPANY - 1],
      industry:       r[COL.PROCESSED_NEWS.INDUSTRY - 1],
      category:       r[COL.PROCESSED_NEWS.CATEGORY - 1],
      signal:         r[COL.PROCESSED_NEWS.SIGNAL - 1],
      keywordMatched: r[COL.PROCESSED_NEWS.KEYWORD_MATCHED - 1],
      intentScore:    r[COL.PROCESSED_NEWS.INTENT_SCORE - 1],
      priority:       r[COL.PROCESSED_NEWS.PRIORITY - 1],
      publishedDate:  r[COL.PROCESSED_NEWS.PUBLISHED_DATE - 1],
      url:            r[COL.PROCESSED_NEWS.URL - 1],
      status:         r[COL.PROCESSED_NEWS.STATUS - 1]
    };
  }

  return { insert: insert, getAll: getAll, getById: getById,
           updateStatus: updateStatus, getHighIntent: getHighIntent,
           getPriorityCounts: getPriorityCounts };

})();
