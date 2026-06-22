// ============================================================
// CompanyCandidateManager.gs - Tracks guessed company/account
//   names that are not yet in the Companies master sheet.
// ============================================================

var CompanyCandidateManager = (function() {

  var STATUS_NEW      = 'New';
  var STATUS_APPROVED = 'Approved';
  var STATUS_REJECTED = 'Rejected';

  var HEADERS = [
    'CandidateID',
    'CompanyName',
    'SourceNewsID',
    'Title',
    'URL',
    'Confidence',
    'SuggestedIndustry',
    'Status',
    'Created',
    'Notes'
  ];

  // ----------------------------------------------------------
  // PUBLIC
  // ----------------------------------------------------------

  /**
   * Creates the Company Candidates sheet if missing.
   */
  function ensureSheet() {
    return _getOrCreateSheet();
  }

  /**
   * Records a guessed company candidate unless it already exists.
   *
   * @param {Object} item Raw news item.
   * @param {Object} suggestion { companyName, confidence, reason }
   * @param {Object} classification Signal/category result.
   * @returns {number|null} CandidateID, or null when skipped.
   */
  function record(item, suggestion, classification) {
    if (!suggestion || !suggestion.companyName) return null;

    var ws = _getOrCreateSheet();
    var name = _normalizeName(suggestion.companyName);
    if (!name || _isExistingCompany(name) || _findCandidateRow(name)) return null;

    var id = _nextId(ws);
    ws.appendRow([
      id,
      name,
      item.newsId || '',
      item.title || '',
      item.url || '',
      Math.round(suggestion.confidence || 0),
      _suggestIndustry(classification),
      STATUS_NEW,
      isoDateTime(new Date()),
      suggestion.reason || ''
    ]);

    logInfo('CompanyCandidateManager: added "' + name + '" from newsId ' + item.newsId);
    return id;
  }

  /**
   * Converts all candidates marked Approved into Companies rows.
   * SuggestedIndustry can be an IndustryID or exact Industry name.
   *
   * @returns {number} Number of companies created.
   */
  function approveMarked() {
    var ws = _getOrCreateSheet();
    var rows = ws.getDataRange().getValues();
    var created = 0;

    for (var i = 1; i < rows.length; i++) {
      var status = String(rows[i][COL.COMPANY_CANDIDATES.STATUS - 1]).trim();
      if (status !== STATUS_APPROVED) continue;

      var name = _normalizeName(rows[i][COL.COMPANY_CANDIDATES.COMPANY_NAME - 1]);
      if (!name || _isExistingCompany(name)) {
        ws.getRange(i + 1, COL.COMPANY_CANDIDATES.STATUS).setValue('Imported');
        continue;
      }

      var industryValue = rows[i][COL.COMPANY_CANDIDATES.SUGGESTED_INDUSTRY - 1];
      var industryId = _resolveIndustryId(industryValue);
      _appendCompany(name, industryId);
      ws.getRange(i + 1, COL.COMPANY_CANDIDATES.STATUS).setValue('Imported');
      created++;
    }

    if (created > 0) CompanyExtractor.invalidate();
    logInfo('CompanyCandidateManager: imported ' + created + ' approved candidates');
    return created;
  }

  /**
   * Returns candidate counts by status.
   */
  function getStats() {
    var ws = _getOrCreateSheet();
    var rows = ws.getDataRange().getValues();
    var stats = { total: Math.max(0, rows.length - 1), newCount: 0, approved: 0, rejected: 0 };

    for (var i = 1; i < rows.length; i++) {
      var status = String(rows[i][COL.COMPANY_CANDIDATES.STATUS - 1]).trim();
      if (status === STATUS_NEW) stats.newCount++;
      else if (status === STATUS_APPROVED) stats.approved++;
      else if (status === STATUS_REJECTED) stats.rejected++;
    }
    return stats;
  }

  // ----------------------------------------------------------
  // PRIVATE
  // ----------------------------------------------------------

  function _getOrCreateSheet() {
    var ss = getSpreadsheet();
    var ws = ss.getSheetByName(SHEETS.COMPANY_CANDIDATES);
    if (!ws) {
      ws = ss.insertSheet(SHEETS.COMPANY_CANDIDATES);
      ws.getRange(1, 1, 1, HEADERS.length).setValues([HEADERS]);
      ws.setFrozenRows(1);
    } else if (ws.getLastRow() === 0 || !ws.getRange(1, 1).getValue()) {
      ws.getRange(1, 1, 1, HEADERS.length).setValues([HEADERS]);
      ws.setFrozenRows(1);
    }
    return ws;
  }

  function _findCandidateRow(companyName) {
    var ws = _getOrCreateSheet();
    var rows = ws.getDataRange().getValues();
    var target = _key(companyName);
    for (var i = 1; i < rows.length; i++) {
      if (_key(rows[i][COL.COMPANY_CANDIDATES.COMPANY_NAME - 1]) === target) return i + 1;
    }
    return null;
  }

  function _nextId(ws) {
    var last = ws.getLastRow();
    if (last < 2) return 1;
    var values = ws.getRange(2, COL.COMPANY_CANDIDATES.CANDIDATE_ID, last - 1, 1).getValues();
    var maxId = 0;
    values.forEach(function(r) {
      var id = parseInt(r[0]);
      if (!isNaN(id) && id > maxId) maxId = id;
    });
    return maxId + 1;
  }

  function _isExistingCompany(companyName) {
    var target = _key(companyName);
    var rows = getDataRows('COMPANIES');
    for (var i = 0; i < rows.length; i++) {
      if (_key(rows[i][COL.COMPANIES.COMPANY_NAME - 1]) === target) return true;
    }

    var aliasRows = getDataRows('COMPANY_ALIASES');
    for (var j = 0; j < aliasRows.length; j++) {
      if (_key(aliasRows[j][COL.COMPANY_ALIASES.ALIAS - 1]) === target) return true;
    }
    return false;
  }

  function _appendCompany(name, industryId) {
    var companyId = getNextId('COMPANIES', COL.COMPANIES.COMPANY_ID);
    appendRow('COMPANIES', [
      companyId,
      name,
      '',                                          // WEBSITE
      industryId || '',
      SettingsManager.get('DEFAULT_COUNTRY', ''),
      '',                                          // STATE
      '',                                          // CITY
      'Imported from Company Candidates',          // NOTES
      true,                                        // ACTIVE
      '',                                          // DOMAIN
      '',                                          // LINKEDIN_URL
      '',                                          // EMPLOYEE_RANGE
      '',                                          // REVENUE_RANGE
      ''                                           // TARGET_ACCOUNT
    ]);
    _appendAliases(companyId, name);
    return companyId;
  }

  function _appendAliases(companyId, name) {
    var aliases = _buildAliases(name);
    aliases.forEach(function(alias) {
      if (!_aliasExists(alias)) {
        appendRow('COMPANY_ALIASES', [
          getNextId('COMPANY_ALIASES', COL.COMPANY_ALIASES.ALIAS_ID),
          companyId,
          alias
        ]);
      }
    });
  }

  function _buildAliases(name) {
    var aliases = [];
    var clean = _normalizeName(name);
    var short = clean.replace(/\b(Inc|Incorporated|Corp|Corporation|Co\.|Company|Ltd|Limited|LLC|PLC|Group)\b\.?/gi, '').trim();
    if (clean) aliases.push(clean);
    if (short && short !== clean) aliases.push(short);
    return aliases;
  }

  function _aliasExists(alias) {
    var target = _key(alias);
    var rows = getDataRows('COMPANY_ALIASES');
    for (var i = 0; i < rows.length; i++) {
      if (_key(rows[i][COL.COMPANY_ALIASES.ALIAS - 1]) === target) return true;
    }
    return false;
  }

  function _resolveIndustryId(value) {
    if (!value) return '';
    var raw = String(value).trim();
    if (/^\d+$/.test(raw)) return raw;

    var rows = getDataRows('INDUSTRIES');
    for (var i = 0; i < rows.length; i++) {
      if (String(rows[i][COL.INDUSTRIES.INDUSTRY - 1]).toLowerCase() === raw.toLowerCase()) {
        return rows[i][COL.INDUSTRIES.INDUSTRY_ID - 1];
      }
    }
    return '';
  }

  function _suggestIndustry(classification) {
    if (!classification) return '';
    var category = String(classification.category || '').toLowerCase();
    var signal = String(classification.signal || '').toLowerCase();
    if (category.indexOf('technology') !== -1 || signal.indexOf('erp') !== -1) return 'IT and Software';
    if (signal.indexOf('automation') !== -1 || signal.indexOf('factory') !== -1) return 'Manufacturing';
    return '';
  }

  function _normalizeName(name) {
    name = String(name || '').trim();
    name = name.replace(/\s+/g, ' ');
    name = name.replace(/^[,.;:\-\s]+|[,.;:\-\s]+$/g, '');
    return name;
  }

  function _key(name) {
    return _normalizeName(name).toLowerCase();
  }

  return {
    ensureSheet: ensureSheet,
    record: record,
    approveMarked: approveMarked,
    getStats: getStats
  };

})();
