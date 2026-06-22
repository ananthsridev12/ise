// ============================================================
// SignalClassifier.gs — Classifies a news item into a Signal
//   and Category using keyword matches and the Signals /
//   Categories sheets.
// ============================================================

var SignalClassifier = (function() {

  var _signals    = null;   // [{ signalId, signal, categoryId, defaultScore, buyingIntent }]
  var _categories = null;   // { categoryId: { category, description } }

  // ----------------------------------------------------------
  // PUBLIC
  // ----------------------------------------------------------

  /**
   * Classifies a news item.
   *
   * @param {Object} item         Raw news item.
   * @param {Object[]} kwMatches  Keyword matches from KeywordMatcher.
   * @returns {Object}  Classification result:
   *   { signalId, signal, categoryId, category, signalScore, buyingIntent }
   */
  function classify(item, kwMatches) {
    _ensureLoaded();

    // Determine the best signal from keyword matches
    var signalId = KeywordMatcher.dominantSignalId(kwMatches);

    // If no keyword match, fall back to the query's default signal
    var signal   = null;
    if (signalId) {
      signal = _signals[String(signalId)];
    }

    // If still no signal, try title-based heuristic classification
    if (!signal) {
      signal = _heuristicClassify(item);
    }

    if (!signal) {
      return {
        signalId: '', signal: 'Unknown',
        categoryId: '', category: 'Unknown',
        signalScore: 0, buyingIntent: false
      };
    }

    var cat = _categories[String(signal.categoryId)] || {};
    return {
      signalId:    signal.signalId,
      signal:      signal.signal,
      categoryId:  signal.categoryId,
      category:    cat.category || '',
      signalScore: signal.defaultScore || 0,
      buyingIntent: isTruthy(signal.buyingIntent)
    };
  }

  /**
   * Returns the signal object for a given signal ID, or null.
   */
  function getSignal(signalId) {
    _ensureLoaded();
    return _signals[String(signalId)] || null;
  }

  /**
   * Returns the category name for a category ID, or ''.
   */
  function getCategoryName(categoryId) {
    _ensureLoaded();
    var cat = _categories[String(categoryId)];
    return cat ? cat.category : '';
  }

  /**
   * Invalidates signal/category caches.
   */
  function invalidate() {
    _signals    = null;
    _categories = null;
  }

  // ----------------------------------------------------------
  // PRIVATE
  // ----------------------------------------------------------

  function _ensureLoaded() {
    if (_signals !== null) return;
    _signals    = {};
    _categories = {};

    getDataRows('SIGNALS').forEach(function(r) {
      if (!isTruthy(r[COL.SIGNALS.ACTIVE - 1])) return;
      var id = String(r[COL.SIGNALS.SIGNAL_ID - 1]);
      _signals[id] = {
        signalId:     id,
        signal:       r[COL.SIGNALS.SIGNAL - 1],
        categoryId:   r[COL.SIGNALS.CATEGORY_ID - 1],
        defaultScore: parseFloat(r[COL.SIGNALS.DEFAULT_SCORE - 1]) || 0,
        buyingIntent: r[COL.SIGNALS.BUYING_INTENT - 1]
      };
    });

    getDataRows('CATEGORIES').forEach(function(r) {
      var id = String(r[COL.CATEGORIES.CATEGORY_ID - 1]);
      _categories[id] = {
        category:    r[COL.CATEGORIES.CATEGORY - 1],
        description: r[COL.CATEGORIES.DESCRIPTION - 1]
      };
    });

    logDebug('SignalClassifier: loaded ' + Object.keys(_signals).length +
             ' signals, ' + Object.keys(_categories).length + ' categories');
  }

  /**
   * Simple heuristic: scan the title for known signal keywords.
   * Returns a signal object or null.
   */
  function _heuristicClassify(item) {
    var text = ((item.title || '') + ' ' + (item.snippet || '')).toLowerCase();

    var heuristics = [
      { patterns: ['new plant', 'greenfield', 'new facility', 'capacity expansion', 'expansion'], signal: 'Capacity Expansion' },
      { patterns: ['erp', 'sap', 'oracle', 'digital transformation', 'technology upgrade'], signal: 'ERP' },
      { patterns: ['hiring', 'recruitment', 'talent acquisition', 'job openings'], signal: 'Hiring Drive' },
      { patterns: ['funding', 'series a', 'series b', 'investment', 'raised'], signal: 'Funding' },
      { patterns: ['acquisition', 'acquires', 'merger', 'takeover'], signal: 'Acquisition' },
      { patterns: ['partnership', 'collaboration', 'tie-up', 'joint venture'], signal: 'Partnership' },
      { patterns: ['export', 'exports to', 'global expansion', 'international'], signal: 'Export Expansion' },
      { patterns: ['ai adoption', 'artificial intelligence', 'machine learning', 'automation'], signal: 'AI Adoption' },
      { patterns: ['product launch', 'launches', 'new product', 'new offering'], signal: 'Product Launch' },
      { patterns: ['new ceo', 'new cto', 'new cfo', 'appoints', 'leadership'], signal: 'Leadership Change' }
    ];

    for (var i = 0; i < heuristics.length; i++) {
      var h = heuristics[i];
      for (var j = 0; j < h.patterns.length; j++) {
        if (text.indexOf(h.patterns[j]) !== -1) {
          // Find the signal in our loaded signals map by name
          for (var sid in _signals) {
            if (_signals[sid].signal.toLowerCase() === h.signal.toLowerCase()) {
              return _signals[sid];
            }
          }
        }
      }
    }
    return null;
  }

  return { classify: classify, getSignal: getSignal,
           getCategoryName: getCategoryName, invalidate: invalidate };

})();
