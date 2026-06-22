// ============================================================
// NewsFetcher.gs — Fetches raw RSS XML from Google News for a
//                   given search query.  Handles retries, HTTP
//                   errors, and rate-limit back-off.
// ============================================================

var NewsFetcher = (function() {

  var MAX_RETRIES   = 3;
  var RETRY_DELAY_S = 2;   // seconds between retries

  // ----------------------------------------------------------
  // PUBLIC
  // ----------------------------------------------------------

  /**
   * Fetches the RSS feed for a query object and returns raw XML.
   *
   * @param {Object} query   A query object from SearchQueryManager.
   * @returns {string|null}  Raw XML string, or null on failure.
   */
  function fetchRss(query) {
    var url = SearchQueryManager.buildRssUrl(query);
    logDebug('NewsFetcher.fetchRss: ' + url);

    for (var attempt = 1; attempt <= MAX_RETRIES; attempt++) {
      try {
        var response = UrlFetchApp.fetch(url, {
          muteHttpExceptions: true,
          headers: {
            'User-Agent': 'Mozilla/5.0 (compatible; ISE/1.0; +https://github.com/ise)'
          }
        });

        var code = response.getResponseCode();

        if (code === 200) {
          logDebug('NewsFetcher: fetched OK (attempt ' + attempt + ')', { url: url });
          return response.getContentText('UTF-8');
        }

        if (code === 429 || code === 503) {
          logWarn('NewsFetcher: rate-limited (' + code + '), backing off', { attempt: attempt });
          Utilities.sleep((RETRY_DELAY_S * attempt) * 1000);
          continue;
        }

        logWarn('NewsFetcher: HTTP ' + code + ' for query "' + query.queryName + '"');
        return null;

      } catch(e) {
        logWarn('NewsFetcher: exception on attempt ' + attempt + ': ' + e.message);
        if (attempt < MAX_RETRIES) {
          Utilities.sleep(RETRY_DELAY_S * 1000);
        }
      }
    }

    logError('NewsFetcher: all ' + MAX_RETRIES + ' attempts failed for "' + query.queryName + '"');
    return null;
  }

  /**
   * Fetches RSS for multiple queries sequentially.
   * Returns array of { query, xml } pairs (xml may be null).
   * Respects the MAX_RESULTS_PER_QUERY setting indirectly through
   * RSSParser (which truncates items).
   *
   * @param {Object[]} queries
   * @returns {Object[]}
   */
  function fetchAll(queries) {
    var results = [];
    queries.forEach(function(q) {
      logInfo('NewsFetcher: fetching "' + q.queryName + '"');
      var xml = fetchRss(q);
      results.push({ query: q, xml: xml });
      // Polite delay between requests to avoid triggering rate limits
      Utilities.sleep(500);
    });
    return results;
  }

  return { fetchRss: fetchRss, fetchAll: fetchAll };

})();
