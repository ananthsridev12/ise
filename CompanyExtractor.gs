// ============================================================
// CompanyExtractor.gs — Extracts the most likely company name
//   from a news item by matching against the Companies sheet
//   (and Company Aliases).  Falls back to NLP-style heuristics.
// ============================================================

var CompanyExtractor = (function() {

  var _companies = null;   // [{ companyId, name, terms, industryId, domain }]

  // ----------------------------------------------------------
  // PUBLIC
  // ----------------------------------------------------------

  /**
   * Returns the best matching company object for a news item, or null.
   * First tries text matching against company names/aliases, then falls
   * back to domain matching against the article URL.
   *
   * @param {Object} item  Raw news item { title, snippet, url }
   * @returns {Object|null}  { companyId, companyName, industryId, confidence }
   */
  function extract(item) {
    _ensureLoaded();
    var text = ((item.title || '') + ' ' + (item.snippet || '')).toLowerCase();

    var bestMatch = null;
    var bestScore = 0;

    _companies.forEach(function(co) {
      var score = _scoreCompany(co, text);
      if (score > bestScore) {
        bestScore = score;
        bestMatch = co;
      }
    });

    if (bestMatch && bestScore >= 1) {
      return {
        companyId:   bestMatch.companyId,
        companyName: bestMatch.name,
        industryId:  bestMatch.industryId,
        confidence:  Math.min(bestScore * 20, 100)
      };
    }

    // Fall back to domain matching when no text match found
    return _domainMatch(item);
  }

  /**
   * Guesses a company name from a news title/snippet when no master
   * company matched. Intended for review, not direct account creation.
   *
   * @param {Object} item Raw news item.
   * @returns {Object|null} { companyName, confidence, reason }
   */
  function guessCandidate(item) {
    var title = String(item.title || '').trim();
    if (!title) return null;

    var cleanTitle = title.split(' - ')[0].trim();
    var patterns = [
      /^(.+?)\s+(announces|opens|launches|expands|invests|raises|acquires|partners|appoints|plans|wins|bags|secures|unveils|builds|starts)\b/i,
      /^(.+?)\s+to\s+(open|launch|expand|invest|acquire|build|hire|set up|establish)\b/i,
      /^(.+?)\s+(gets|receives|lands)\s+(approval|funding|investment|order|contract)\b/i
    ];

    for (var i = 0; i < patterns.length; i++) {
      var m = cleanTitle.match(patterns[i]);
      if (!m) continue;

      var name = _cleanCandidateName(m[1]);
      if (_isLikelyCompanyName(name)) {
        return {
          companyName: name,
          confidence: _candidateConfidence(name, cleanTitle),
          reason: 'title pattern match'
        };
      }
    }

    return null;
  }

  /**
   * Returns an industry name for a given industryId, or ''.
   */
  function getIndustryName(industryId) {
    if (!industryId) return '';
    var rows = getDataRows('INDUSTRIES');
    for (var i = 0; i < rows.length; i++) {
      if (String(rows[i][COL.INDUSTRIES.INDUSTRY_ID - 1]) === String(industryId)) {
        return rows[i][COL.INDUSTRIES.INDUSTRY - 1] || '';
      }
    }
    return '';
  }

  /**
   * Clears the company cache (call after Companies sheet is edited).
   */
  function invalidate() {
    _companies = null;
  }

  // ----------------------------------------------------------
  // PRIVATE
  // ----------------------------------------------------------

  function _ensureLoaded() {
    if (_companies !== null) return;
    _companies = [];

    // Load companies
    var companyRows  = getDataRows('COMPANIES');
    var aliasRows    = getDataRows('COMPANY_ALIASES');

    // Build alias map: companyId -> [alias, ...]
    var aliasMap = {};
    aliasRows.forEach(function(r) {
      var cid   = String(r[COL.COMPANY_ALIASES.COMPANY_ID - 1]);
      var alias = String(r[COL.COMPANY_ALIASES.ALIAS - 1]).toLowerCase().trim();
      if (!aliasMap[cid]) aliasMap[cid] = [];
      if (alias) aliasMap[cid].push(alias);
    });

    companyRows.forEach(function(r) {
      if (!isTruthy(r[COL.COMPANIES.ACTIVE - 1])) return;
      var cid  = String(r[COL.COMPANIES.COMPANY_ID - 1]);
      var name = String(r[COL.COMPANIES.COMPANY_NAME - 1]).trim();
      if (!name) return;

      var terms = [name.toLowerCase()];
      // Add aliases
      if (aliasMap[cid]) terms = terms.concat(aliasMap[cid]);
      // Add short form (remove common suffixes for broader matching)
      var short = name.replace(/\b(Ltd|Limited|Pvt|Private|Inc|Corp|Corporation|Co\.|Company)\b\.?/gi, '').trim();
      if (short && short.toLowerCase() !== name.toLowerCase()) {
        terms.push(short.toLowerCase());
      }

      var domainRaw = r.length >= COL.COMPANIES.DOMAIN
        ? String(r[COL.COMPANIES.DOMAIN - 1] || '').toLowerCase().trim()
        : '';

      _companies.push({
        companyId:  cid,
        name:       name,
        industryId: r[COL.COMPANIES.INDUSTRY_ID - 1],
        terms:      terms,
        domain:     domainRaw.replace(/^https?:\/\//, '').replace(/^www\./, '').replace(/\/.*$/, '')
      });
    });

    logDebug('CompanyExtractor: loaded ' + _companies.length + ' companies');
  }

  /**
   * Scores how strongly a company matches the text.
   * Returns 0 if no match.  Higher is better.
   */
  function _scoreCompany(co, text) {
    var score = 0;
    co.terms.forEach(function(term) {
      if (!term || term.length < 3) return;
      // Whole-word match scores higher
      var regex = new RegExp('\\b' + _escapeRegex(term) + '\\b', 'i');
      if (regex.test(text)) {
        score += (term.length > 10) ? 3 : 2;  // longer term = more confident
      } else if (text.indexOf(term) !== -1) {
        score += 1;
      }
    });
    return score;
  }

  function _escapeRegex(str) {
    return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  function _cleanCandidateName(name) {
    name = String(name || '').trim();
    name = name.replace(/^shares of\s+/i, '');
    name = name.replace(/^stock of\s+/i, '');
    name = name.replace(/[,.;:\-\s]+$/g, '');
    name = name.replace(/\s+/g, ' ');
    return name;
  }

  function _isLikelyCompanyName(name) {
    if (!name || name.length < 3 || name.length > 80) return false;
    if (name.split(/\s+/).length > 8) return false;

    var lower = name.toLowerCase();
    var blocked = [
      'the company', 'company', 'government', 'ministry', 'court',
      'report', 'analysts', 'stocks', 'shares', 'market', 'industry',
      'manufacturing', 'factory', 'plant', 'startup'
    ];
    if (blocked.indexOf(lower) !== -1) return false;

    return /[A-Z]/.test(name) || /\b(inc|corp|corporation|company|co\.|ltd|limited|llc|plc|group|technologies|systems|industries|motors|electric|automation)\b/i.test(name);
  }

  function _candidateConfidence(name, title) {
    var confidence = 55;
    if (/\b(inc|corp|corporation|company|co\.|ltd|limited|llc|plc|group)\b/i.test(name)) confidence += 15;
    if (title.indexOf(name) === 0) confidence += 10;
    if (name.split(/\s+/).length >= 2) confidence += 10;
    return Math.min(90, confidence);
  }

  /**
   * Tries to match a company by comparing the article URL's root domain
   * against the Domain field of known companies.
   * Returns a company match object or null.
   */
  function _domainMatch(item) {
    var articleDomain = _parseDomain(String(item.url || ''));
    if (!articleDomain) return null;

    for (var i = 0; i < _companies.length; i++) {
      var co = _companies[i];
      if (!co.domain) continue;
      // Match if article domain equals or is a subdomain of the company domain
      if (articleDomain === co.domain || _endsWith(articleDomain, '.' + co.domain)) {
        logDebug('CompanyExtractor: domain match "' + co.name + '" via ' + articleDomain);
        return {
          companyId:   co.companyId,
          companyName: co.name,
          industryId:  co.industryId,
          confidence:  80
        };
      }
    }
    return null;
  }

  /**
   * Extracts the root domain (without www. and path) from a URL.
   * Returns '' if the URL is not parseable.
   */
  function _parseDomain(url) {
    if (!url) return '';
    try {
      var m = url.match(/^https?:\/\/([^\/\?#]+)/i);
      if (!m) return '';
      return m[1].toLowerCase().replace(/^www\./, '');
    } catch(e) {
      return '';
    }
  }

  function _endsWith(str, suffix) {
    return str.length >= suffix.length &&
           str.slice(-suffix.length) === suffix;
  }

  return { extract: extract, guessCandidate: guessCandidate,
           getIndustryName: getIndustryName, invalidate: invalidate };

})();
