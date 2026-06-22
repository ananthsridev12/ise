// ============================================================
// DuplicateChecker.gs — Prevents duplicate news items from
//   being written to Raw News by maintaining a hash index.
// ============================================================

var DuplicateChecker = (function() {

  var _hashCache = null;  // Set of known hashes, loaded lazily

  // ----------------------------------------------------------
  // PUBLIC
  // ----------------------------------------------------------

  /**
   * Returns true if the item (by hash) already exists.
   */
  function isDuplicate(hash) {
    _ensureLoaded();
    return _hashCache.hasOwnProperty(hash);
  }

  /**
   * Filters an array of parsed news items to only new (non-duplicate) ones.
   * @param {Object[]} items
   * @returns {Object[]}
   */
  function filterNew(items) {
    _ensureLoaded();
    return items.filter(function(item) {
      return !_hashCache.hasOwnProperty(item.hash);
    });
  }

  /**
   * Registers a hash in the index sheet and in-memory cache.
   * Call this after successfully writing a news item to Raw News.
   *
   * @param {string} hash
   * @param {string|number} newsId
   * @param {string} url
   */
  function register(hash, newsId, url) {
    _ensureLoaded();
    if (_hashCache.hasOwnProperty(hash)) return;

    var id  = getNextId('DUPLICATE_INDEX', COL.DUPLICATE_INDEX.HASH);
    // Duplicate Index uses Hash as key, not an integer ID — use row count instead
    appendRow('DUPLICATE_INDEX', [
      hash,
      newsId,
      truncate(url || '', 500),
      isoDateTime(new Date())
    ]);
    _hashCache[hash] = newsId;
  }

  /**
   * Registers multiple items at once (more efficient).
   */
  function registerBatch(items) {
    items.forEach(function(item) {
      register(item.hash, item.newsId, item.url);
    });
  }

  /**
   * Clears the in-memory cache (forces reload on next use).
   */
  function invalidate() {
    _hashCache = null;
  }

  /**
   * Purges hash entries older than retainDays (default 30).
   * Removes from sheet only; cache is rebuilt on next use.
   */
  function purgeOld(retainDays) {
    retainDays = retainDays || 30;
    var ws     = getSheet('DUPLICATE_INDEX');
    var rows   = getDataRows('DUPLICATE_INDEX');
    var cutoff = new Date();
    cutoff.setDate(cutoff.getDate() - retainDays);
    var toDelete = [];

    rows.forEach(function(r, i) {
      var d = parseDate(r[COL.DUPLICATE_INDEX.CREATED - 1]);
      if (d && d < cutoff) toDelete.push(i + 2);
    });

    for (var i = toDelete.length - 1; i >= 0; i--) {
      ws.deleteRow(toDelete[i]);
    }

    _hashCache = null;
    logInfo('DuplicateChecker: purged ' + toDelete.length + ' old hash entries');
  }

  /**
   * Returns the count of known hashes.
   */
  function count() {
    _ensureLoaded();
    return Object.keys(_hashCache).length;
  }

  // ----------------------------------------------------------
  // PRIVATE
  // ----------------------------------------------------------

  function _ensureLoaded() {
    if (_hashCache !== null) return;
    _hashCache = {};
    try {
      var rows = getDataRows('DUPLICATE_INDEX');
      rows.forEach(function(r) {
        var hash   = String(r[COL.DUPLICATE_INDEX.HASH - 1]).trim();
        var newsId = r[COL.DUPLICATE_INDEX.NEWS_ID - 1];
        if (hash) _hashCache[hash] = newsId;
      });
      logDebug('DuplicateChecker: loaded ' + Object.keys(_hashCache).length + ' hashes');
    } catch(e) {
      logError('DuplicateChecker: failed to load hash index: ' + e.message);
    }
  }

  return { isDuplicate: isDuplicate, filterNew: filterNew,
           register: register, registerBatch: registerBatch,
           invalidate: invalidate, purgeOld: purgeOld, count: count };

})();
