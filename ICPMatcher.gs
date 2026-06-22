// ============================================================
// ICPMatcher.gs — Matches a news item against ICP (Ideal
//   Customer Profile) definitions in the ICP Master sheet.
//   Returns a best match and confidence score.
// ============================================================

var ICPMatcher = (function() {

  var _icps = null;  // loaded lazily

  // ----------------------------------------------------------
  // PUBLIC
  // ----------------------------------------------------------

  /**
   * Finds the best ICP match for a news item.
   *
   * @param {Object} item           Raw news item.
   * @param {Object} companyMatch   From CompanyExtractor (may be null).
   * @param {Object} classification From SignalClassifier.
   * @returns {Object|null}  Best match:
   *   { matchId, icpId, icpCode, icpName, confidence, reason }
   *   or null if no meaningful match.
   */
  function match(item, companyMatch, classification) {
    _ensureLoaded();

    var bestIcp    = null;
    var bestScore  = 0;
    var bestReason = '';

    _icps.forEach(function(icp) {
      var result = _scoreIcp(icp, item, companyMatch, classification);
      if (result.score > bestScore) {
        bestScore  = result.score;
        bestIcp    = icp;
        bestReason = result.reason;
      }
    });

    if (!bestIcp || bestScore < 35) return null;

    return {
      icpId:      bestIcp.icpId,
      icpCode:    bestIcp.icpCode,
      icpName:    bestIcp.icpName,
      confidence: Math.min(100, bestScore),
      reason:     bestReason
    };
  }

  /**
   * Writes a match record to the ICP Matching sheet.
   * Returns the generated MatchID.
   */
  function record(newsId, icpMatch) {
    if (!icpMatch) return null;
    var id = getNextId('ICP_MATCHING', COL.ICP_MATCHING.MATCH_ID);
    appendRow('ICP_MATCHING', [
      id,
      newsId,
      icpMatch.icpId,
      icpMatch.confidence,
      truncate(icpMatch.reason, 300)
    ]);
    return id;
  }

  /**
   * Returns all ICP matches for a given newsId.
   */
  function getByNewsId(newsId) {
    var rows = getDataRows('ICP_MATCHING');
    return rows
      .filter(function(r) {
        return String(r[COL.ICP_MATCHING.NEWS_ID - 1]) === String(newsId);
      })
      .map(function(r) {
        return {
          matchId:    r[COL.ICP_MATCHING.MATCH_ID - 1],
          newsId:     r[COL.ICP_MATCHING.NEWS_ID - 1],
          icpId:      r[COL.ICP_MATCHING.ICP_ID - 1],
          confidence: r[COL.ICP_MATCHING.CONFIDENCE - 1],
          reason:     r[COL.ICP_MATCHING.REASON - 1]
        };
      });
  }

  /**
   * Invalidates the ICP cache.
   */
  function invalidate() {
    _icps = null;
  }

  // ----------------------------------------------------------
  // PRIVATE
  // ----------------------------------------------------------

  function _ensureLoaded() {
    if (_icps !== null) return;
    _icps = [];
    getDataRows('ICP_MASTER').forEach(function(r) {
      if (!r[COL.ICP_MASTER.ICP_ID - 1]) return;
      _icps.push({
        icpId:                 r[COL.ICP_MASTER.ICP_ID - 1],
        vertical:              r[COL.ICP_MASTER.VERTICAL - 1],
        icpCode:               r[COL.ICP_MASTER.ICP_CODE - 1],
        segment:               r[COL.ICP_MASTER.SEGMENT - 1],
        subSegment:            r[COL.ICP_MASTER.SUB_SEGMENT - 1],
        productService:        r[COL.ICP_MASTER.PRODUCT_SERVICE - 1],
        primaryServicesToPitch: r[COL.ICP_MASTER.PRIMARY_SERVICES_TO_PITCH - 1],
        category:              r[COL.ICP_MASTER.CATEGORY - 1],
        tier:                  r[COL.ICP_MASTER.TIER - 1],
        geo:                   r[COL.ICP_MASTER.GEO - 1],
        employees:             r[COL.ICP_MASTER.EMPLOYEES - 1],
        revenue:               r[COL.ICP_MASTER.REVENUE - 1],
        icpName:               r[COL.ICP_MASTER.SEGMENT - 1] || r[COL.ICP_MASTER.ICP_CODE - 1],
        matchText:             _buildMatchText(r)
      });
    });
    logDebug('ICPMatcher: loaded ' + _icps.length + ' ICP profiles');
  }

  function _scoreIcp(icp, item, companyMatch, classification) {
    var score   = 0;
    var reasons = [];
    var text    = ((item.title || '') + ' ' + (item.snippet || '')).toLowerCase();
    var hasCoreMatch = false;

    // Company name match indicates the news is about a known account.
    if (companyMatch && companyMatch.companyName) {
      score += 15;
      reasons.push('company match');
    }

    // Signal match against the full ICP profile text.
    if (classification && icp.matchText) {
      var signalName = (classification.signal || '').toLowerCase();
      if (signalName && icp.matchText.indexOf(signalName) !== -1) {
        score += 25;
        hasCoreMatch = true;
        reasons.push('signal match');
      }
    }

    var productHits = _countPhraseHits(text, icp.productService + '; ' + icp.primaryServicesToPitch);
    if (productHits > 0) {
      score += Math.min(30, productHits * 10);
      hasCoreMatch = true;
      reasons.push('product/service match');
    }

    var segmentHits = _countPhraseHits(text, icp.segment + '; ' + icp.subSegment + '; ' + icp.category);
    if (segmentHits > 0) {
      score += Math.min(25, segmentHits * 10);
      hasCoreMatch = true;
      reasons.push('segment/category match');
    }

    if (!hasCoreMatch) return { score: 0, reason: '' };

    if (_geoMatches(text, icp.geo, item)) {
      score += 20;
      reasons.push('geo match');
    }

    if (String(icp.tier).toLowerCase() === 'large') {
      score += 10;
      reasons.push('large tier boost');
    }

    return { score: score, reason: reasons.join('; ') };
  }

  function _buildMatchText(r) {
    return [
      r[COL.ICP_MASTER.VERTICAL - 1],
      r[COL.ICP_MASTER.ICP_CODE - 1],
      r[COL.ICP_MASTER.SEGMENT - 1],
      r[COL.ICP_MASTER.SUB_SEGMENT - 1],
      r[COL.ICP_MASTER.PRODUCT_SERVICE - 1],
      r[COL.ICP_MASTER.PRIMARY_SERVICES_TO_PITCH - 1],
      r[COL.ICP_MASTER.CATEGORY - 1],
      r[COL.ICP_MASTER.TIER - 1],
      r[COL.ICP_MASTER.GEO - 1],
      r[COL.ICP_MASTER.EMPLOYEES - 1],
      r[COL.ICP_MASTER.REVENUE - 1]
    ].join(' ').toLowerCase();
  }

  function _countPhraseHits(text, phraseList) {
    if (!phraseList) return 0;
    var hits = 0;
    String(phraseList).split(/[;,|]/).forEach(function(raw) {
      var phrase = raw.toLowerCase().trim();
      if (phrase.length >= 3 && text.indexOf(phrase) !== -1) hits++;
    });
    return hits;
  }

  function _geoMatches(text, geo, item) {
    geo = String(geo || '').toLowerCase().trim();
    if (!geo) return false;

    var aliases = {
      us: ['us', 'u.s.', 'usa', 'united states', 'america', 'north america'],
      usa: ['us', 'u.s.', 'usa', 'united states', 'america', 'north america'],
      eu: ['eu', 'europe', 'european union', 'germany', 'france', 'italy', 'spain', 'netherlands'],
      uk: ['uk', 'u.k.', 'united kingdom', 'britain', 'england'],
      in: ['in', 'india', 'indian']
    };

    var terms = aliases[geo] || [geo];
    for (var i = 0; i < terms.length; i++) {
      if (_textContainsTerm(text, terms[i])) return true;
    }

    if (item && item.queryId) {
      var query = SearchQueryManager.getById(item.queryId);
      var queryCountry = query ? String(query.country || '').toLowerCase() : '';
      if (queryCountry && terms.indexOf(queryCountry) !== -1) return true;
    }

    return false;
  }

  function _textContainsTerm(text, term) {
    term = String(term || '').toLowerCase().trim();
    if (!term) return false;
    if (term.length <= 3) {
      var regex = new RegExp('(^|[^a-z0-9])' + _escapeRegex(term) + '([^a-z0-9]|$)', 'i');
      return regex.test(text);
    }
    return text.indexOf(term) !== -1;
  }

  function _escapeRegex(str) {
    return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  return { match: match, record: record, getByNewsId: getByNewsId, invalidate: invalidate };

})();
