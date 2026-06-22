// ============================================================
// IntentScoreEngine.gs — Computes a 0-100 Intent Score from
//   five sub-scores: Signal, Keyword, Freshness, Source, ICP.
//   Applies Priority Rules adjustments.
// ============================================================

var IntentScoreEngine = (function() {

  var _priorityRules = null;  // loaded lazily

  // Sub-score weights (must sum to 100)
  var WEIGHTS = {
    SIGNAL:    30,
    KEYWORD:   25,
    FRESHNESS: 20,
    SOURCE:    10,
    ICP:       15
  };

  // ----------------------------------------------------------
  // PUBLIC
  // ----------------------------------------------------------

  /**
   * Computes the full intent score for a news item.
   *
   * @param {Object} item        Raw news item.
   * @param {Object} classification  From SignalClassifier.classify().
   * @param {Object[]} kwMatches  From KeywordMatcher.match().
   * @param {Object|null} icpMatch  From ICPMatcher (may be null at this stage).
   * @returns {Object}  Score breakdown + final score:
   *   { signalScore, keywordScore, freshnessScore, sourceScore,
   *     icpScore, finalScore, priority }
   */
  function compute(item, classification, kwMatches, icpMatch) {
    _ensureRulesLoaded();

    var signalScore    = _signalScore(classification);
    var keywordScore   = _keywordScore(kwMatches);
    var freshnessScore = _freshnessScore(item);
    var sourceScore    = _sourceScore(item);
    var icpScore       = _icpScore(icpMatch);

    var weighted = (
      (signalScore    * WEIGHTS.SIGNAL    / 100) +
      (keywordScore   * WEIGHTS.KEYWORD   / 100) +
      (freshnessScore * WEIGHTS.FRESHNESS / 100) +
      (sourceScore    * WEIGHTS.SOURCE    / 100) +
      (icpScore       * WEIGHTS.ICP       / 100)
    );

    // Apply priority rule adjustments
    var adjustment = _applyPriorityRules(classification, item);
    var finalScore = Math.min(100, Math.max(0, Math.round(weighted + adjustment)));

    return {
      signalScore:    Math.round(signalScore),
      keywordScore:   Math.round(keywordScore),
      freshnessScore: Math.round(freshnessScore),
      sourceScore:    Math.round(sourceScore),
      icpScore:       Math.round(icpScore),
      finalScore:     finalScore,
      priority:       _scoreToPriority(finalScore)
    };
  }

  /**
   * Writes the score breakdown to the Intent Score Engine sheet.
   */
  function record(newsId, scoreObj) {
    var id = getNextId('INTENT_SCORE', COL.INTENT_SCORE.NEWS_ID);
    // Check if a row for this newsId already exists; update if so
    var ws   = getSheet('INTENT_SCORE');
    var rows = ws.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][COL.INTENT_SCORE.NEWS_ID - 1]) === String(newsId)) {
        updateRowCells('INTENT_SCORE', i + 1, {
          [COL.INTENT_SCORE.SIGNAL_SCORE]:    scoreObj.signalScore,
          [COL.INTENT_SCORE.KEYWORD_SCORE]:   scoreObj.keywordScore,
          [COL.INTENT_SCORE.FRESHNESS_SCORE]: scoreObj.freshnessScore,
          [COL.INTENT_SCORE.SOURCE_SCORE]:    scoreObj.sourceScore,
          [COL.INTENT_SCORE.ICP_SCORE]:       scoreObj.icpScore,
          [COL.INTENT_SCORE.FINAL_SCORE]:     scoreObj.finalScore
        });
        return;
      }
    }
    appendRow('INTENT_SCORE', [
      newsId,
      scoreObj.signalScore,
      scoreObj.keywordScore,
      scoreObj.freshnessScore,
      scoreObj.sourceScore,
      scoreObj.icpScore,
      scoreObj.finalScore
    ]);
  }

  /**
   * Invalidates priority rules cache.
   */
  function invalidate() {
    _priorityRules = null;
  }

  // ----------------------------------------------------------
  // SUB-SCORE CALCULATORS
  // ----------------------------------------------------------

  function _signalScore(classification) {
    if (!classification || !classification.signalScore) return 0;
    // signalScore from sheet is already 0-100 (DefaultScore column)
    var score = parseFloat(classification.signalScore) || 0;
    // Boost buying-intent signals
    if (classification.buyingIntent) score = Math.min(100, score * 1.15);
    return Math.min(100, score);
  }

  function _keywordScore(kwMatches) {
    if (!kwMatches || kwMatches.length === 0) return 0;
    return KeywordMatcher.totalScore(kwMatches);
  }

  function _freshnessScore(item) {
    var age = ageInDays(parseDate(item.publishedDate));
    if (age <= 1)  return 100;
    if (age <= 2)  return 85;
    if (age <= 3)  return 70;
    if (age <= 5)  return 50;
    if (age <= 7)  return 30;
    return 10;
  }

  function _sourceScore(item) {
    if (!item.sourceId) return 50;  // neutral if unknown
    var rows = getDataRows('SOURCES');
    for (var i = 0; i < rows.length; i++) {
      if (String(rows[i][COL.SOURCES.SOURCE_ID - 1]) === String(item.sourceId)) {
        var priority = String(rows[i][COL.SOURCES.PRIORITY - 1]).toLowerCase();
        if (priority === 'high'   || priority === '1') return 100;
        if (priority === 'medium' || priority === '2') return 60;
        if (priority === 'low'    || priority === '3') return 30;
        return 50;
      }
    }
    return 50;
  }

  function _icpScore(icpMatch) {
    if (!icpMatch) return 0;
    return Math.min(100, parseFloat(icpMatch.confidence) || 0);
  }

  function _scoreToPriority(score) {
    if (score >= CONST.SCORE_HIGH_THRESHOLD) return CONST.PRIORITY_HIGH;
    if (score >= CONST.SCORE_MED_THRESHOLD)  return CONST.PRIORITY_MEDIUM;
    return CONST.PRIORITY_LOW;
  }

  // ----------------------------------------------------------
  // PRIORITY RULES
  // ----------------------------------------------------------

  function _ensureRulesLoaded() {
    if (_priorityRules !== null) return;
    _priorityRules = [];
    getDataRows('PRIORITY_RULES').forEach(function(r) {
      _priorityRules.push({
        ruleId:     r[COL.PRIORITY_RULES.RULE_ID - 1],
        signal:     String(r[COL.PRIORITY_RULES.SIGNAL - 1]).toLowerCase(),
        condition:  String(r[COL.PRIORITY_RULES.CONDITION - 1]).toLowerCase(),
        adjustment: parseFloat(r[COL.PRIORITY_RULES.ADJUSTMENT - 1]) || 0,
        remarks:    r[COL.PRIORITY_RULES.REMARKS - 1]
      });
    });
  }

  function _applyPriorityRules(classification, item) {
    var total = 0;
    var signalName = (classification.signal || '').toLowerCase();
    var text       = ((item.title || '') + ' ' + (item.snippet || '')).toLowerCase();

    _priorityRules.forEach(function(rule) {
      if (rule.signal && rule.signal !== signalName) return;
      // condition is a keyword that must appear in text
      if (rule.condition && text.indexOf(rule.condition) === -1) return;
      total += rule.adjustment;
    });

    return total;
  }

  return { compute: compute, record: record, invalidate: invalidate };

})();
