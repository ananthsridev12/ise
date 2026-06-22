// ============================================================
// KeywordMatcher.gs — Matches active keywords (from the
//   Keywords sheet) against a news item's text and returns
//   all matches with their weights.
// ============================================================

var KeywordMatcher = (function() {

  var _keywords = null;  // [{ keywordId, keyword, signalId, weight, matchType }]

  // ----------------------------------------------------------
  // PUBLIC
  // ----------------------------------------------------------

  /**
   * Returns all keyword matches found in a news item.
   *
   * @param {Object} item  News item with title and snippet fields.
   * @returns {Object[]}   Array of match objects:
   *   { keywordId, keyword, signalId, weight, matchType }
   */
  function match(item) {
    _ensureLoaded();
    var text    = ((item.title || '') + ' ' + (item.snippet || '')).toLowerCase();
    var matches = [];
    var seen    = {};  // prevent duplicate keyword matches

    _keywords.forEach(function(kw) {
      if (seen[kw.keywordId]) return;
      var matched = false;

      if (kw.matchType === 'Exact') {
        var regex = new RegExp('\\b' + _escapeRegex(kw.keyword) + '\\b', 'i');
        matched = regex.test(text);
      } else {
        // Contains
        matched = text.indexOf(kw.keyword) !== -1;
      }

      if (matched) {
        matches.push({
          keywordId: kw.keywordId,
          keyword:   kw.keyword,
          signalId:  kw.signalId,
          weight:    kw.weight,
          matchType: kw.matchType
        });
        seen[kw.keywordId] = true;
      }
    });

    return matches;
  }

  /**
   * Returns the combined keyword score for a set of matches.
   * Score = sum of weights, capped at 100.
   */
  function totalScore(matches) {
    var sum = matches.reduce(function(acc, m) { return acc + (m.weight || 1); }, 0);
    return Math.min(sum, 100);
  }

  /**
   * Returns the dominant signalId from a set of matches
   * (the one with the highest total weight).
   */
  function dominantSignalId(matches) {
    var tally = {};
    matches.forEach(function(m) {
      tally[m.signalId] = (tally[m.signalId] || 0) + (m.weight || 1);
    });
    var best = null, bestW = 0;
    for (var sid in tally) {
      if (tally[sid] > bestW) { bestW = tally[sid]; best = sid; }
    }
    return best;
  }

  /**
   * Returns matched keyword strings joined by ', '.
   */
  function matchedKeywordString(matches) {
    return matches.map(function(m) { return m.keyword; }).join(', ');
  }

  /**
   * Invalidates the keyword cache.
   */
  function invalidate() {
    _keywords = null;
  }

  // ----------------------------------------------------------
  // PRIVATE
  // ----------------------------------------------------------

  function _ensureLoaded() {
    if (_keywords !== null) return;
    _keywords = [];
    var rows = getDataRows('KEYWORDS');
    rows.forEach(function(r) {
      if (!isTruthy(r[COL.KEYWORDS.ACTIVE - 1])) return;
      var kw = String(r[COL.KEYWORDS.KEYWORD - 1]).trim().toLowerCase();
      if (!kw) return;
      _keywords.push({
        keywordId: r[COL.KEYWORDS.KEYWORD_ID - 1],
        keyword:   kw,
        signalId:  r[COL.KEYWORDS.SIGNAL_ID - 1],
        weight:    parseFloat(r[COL.KEYWORDS.WEIGHT - 1]) || 1,
        matchType: r[COL.KEYWORDS.MATCH_TYPE - 1] || 'Contains'
      });
    });
    logDebug('KeywordMatcher: loaded ' + _keywords.length + ' active keywords');
  }

  function _escapeRegex(str) {
    return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  return { match: match, totalScore: totalScore, dominantSignalId: dominantSignalId,
           matchedKeywordString: matchedKeywordString, invalidate: invalidate };

})();
