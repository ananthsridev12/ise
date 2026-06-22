// ============================================================
// OutreachQueue.gs — Enriched outreach queue that combines
//   intent signal data with pitch context from PitchPlaybook.
//   Populated automatically during _processItems().
//
//   Statuses: New → Draft → Sent → Replied → Won / Closed / Snoozed
// ============================================================

var OutreachQueue = (function() {

  var STATUS_NEW    = 'New';
  var STATUS_SENT   = 'Sent';
  var STATUS_CLOSED = 'Closed';

  var HEADERS = [
    'OutreachID', 'NewsID', 'CompanyName', 'Domain', 'Industry',
    'Headline', 'Signal', 'Category', 'IntentScore', 'ICPMatch',
    'PitchAngle', 'KeyServices', 'TalkTrack',
    'PublishedDate', 'URL',
    'OutreachStatus', 'Owner',
    'ContactName', 'ContactTitle', 'ContactEmail',
    'OutreachDate', 'FollowUpDate', 'Notes'
  ];

  // ----------------------------------------------------------
  // PUBLIC
  // ----------------------------------------------------------

  /**
   * Promotes a processed item to the Outreach Queue if it meets
   * the minimum intent score and has not already been queued.
   * Pitch context from PitchPlaybook is embedded automatically.
   *
   * @param {Object} processedItem  From ProcessedNewsManager.
   * @param {Object} pitchContext   From PitchPlaybook.lookup().
   * @returns {number|null}  New OutreachID, or null if skipped.
   */
  function promote(processedItem, pitchContext) {
    var minScore = SettingsManager.getInt('MIN_INTENT_SCORE', 30);
    var score    = parseFloat(processedItem.intentScore) || 0;

    if (score < minScore) return null;
    if (_alreadyQueued(processedItem.newsId)) return null;

    var ws = _getOrCreateSheet();
    var id = _nextId(ws);

    var ctx = pitchContext || {};

    ws.appendRow([
      id,
      processedItem.newsId      || '',
      processedItem.company     || '',
      _domainFromUrl(processedItem.url),
      processedItem.industry    || '',
      processedItem.title       || '',
      processedItem.signal      || '',
      processedItem.category    || '',
      processedItem.intentScore || 0,
      processedItem.icpName     || '',
      ctx.pitchAngle            || '',
      ctx.keyServices           || '',
      ctx.talkTrack             || '',
      processedItem.publishedDate || '',
      processedItem.url         || '',
      STATUS_NEW,
      '',  // Owner
      '',  // ContactName
      '',  // ContactTitle
      '',  // ContactEmail
      '',  // OutreachDate
      '',  // FollowUpDate
      ''   // Notes
    ]);

    logDebug('OutreachQueue: promoted newsId ' + processedItem.newsId +
             ' (score ' + score + ')');
    return id;
  }

  /**
   * Returns all outreach queue items, optionally filtered by status.
   */
  function getAll(statusFilter) {
    var ws = getSpreadsheet().getSheetByName(SHEETS.OUTREACH_QUEUE);
    if (!ws || ws.getLastRow() < 2) return [];
    var rows = ws.getRange(2, 1, ws.getLastRow() - 1, ws.getLastColumn()).getValues();
    return rows
      .map(_rowToItem)
      .filter(function(item) {
        if (!statusFilter) return true;
        return item.outreachStatus === statusFilter;
      });
  }

  /**
   * Returns only new (unactioned) items.
   */
  function getNew() {
    return getAll(STATUS_NEW);
  }

  /**
   * Updates the status, owner, and contact fields for a queue item.
   * Pass null to leave a field unchanged.
   */
  function updateStatus(outreachId, fields) {
    var ws = getSpreadsheet().getSheetByName(SHEETS.OUTREACH_QUEUE);
    if (!ws) return;
    var rows = ws.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][COL.OUTREACH_QUEUE.OUTREACH_ID - 1]) !== String(outreachId)) continue;
      var r = i + 1;
      if (fields.outreachStatus) ws.getRange(r, COL.OUTREACH_QUEUE.OUTREACH_STATUS).setValue(fields.outreachStatus);
      if (fields.owner)         ws.getRange(r, COL.OUTREACH_QUEUE.OWNER).setValue(fields.owner);
      if (fields.contactName)   ws.getRange(r, COL.OUTREACH_QUEUE.CONTACT_NAME).setValue(fields.contactName);
      if (fields.contactTitle)  ws.getRange(r, COL.OUTREACH_QUEUE.CONTACT_TITLE).setValue(fields.contactTitle);
      if (fields.contactEmail)  ws.getRange(r, COL.OUTREACH_QUEUE.CONTACT_EMAIL).setValue(fields.contactEmail);
      if (fields.outreachDate)  ws.getRange(r, COL.OUTREACH_QUEUE.OUTREACH_DATE).setValue(fields.outreachDate);
      if (fields.followUpDate)  ws.getRange(r, COL.OUTREACH_QUEUE.FOLLOW_UP_DATE).setValue(fields.followUpDate);
      if (fields.notes)         ws.getRange(r, COL.OUTREACH_QUEUE.NOTES).setValue(fields.notes);
      return;
    }
    logWarn('OutreachQueue.updateStatus: id ' + outreachId + ' not found');
  }

  /**
   * Returns summary stats for Dashboard.
   */
  function getStats() {
    var ws = getSpreadsheet().getSheetByName(SHEETS.OUTREACH_QUEUE);
    if (!ws || ws.getLastRow() < 2) return { total: 0, new: 0, sent: 0 };

    var rows  = ws.getRange(2, 1, ws.getLastRow() - 1, ws.getLastColumn()).getValues();
    var total = rows.length;
    var newCount  = 0;
    var sentCount = 0;

    rows.forEach(function(r) {
      var st = String(r[COL.OUTREACH_QUEUE.OUTREACH_STATUS - 1] || '');
      if (st === STATUS_NEW)  newCount++;
      if (st === STATUS_SENT) sentCount++;
    });

    return { total: total, new: newCount, sent: sentCount };
  }

  /**
   * Creates the Outreach Queue sheet if it does not exist.
   */
  function ensureSheet() {
    _getOrCreateSheet();
  }

  // ----------------------------------------------------------
  // PRIVATE
  // ----------------------------------------------------------

  function _getOrCreateSheet() {
    var ss = getSpreadsheet();
    var ws = ss.getSheetByName(SHEETS.OUTREACH_QUEUE);
    if (!ws) {
      ws = ss.insertSheet(SHEETS.OUTREACH_QUEUE);
      ws.getRange(1, 1, 1, HEADERS.length).setValues([HEADERS]);
      ws.setFrozenRows(1);
    }
    return ws;
  }

  function _alreadyQueued(newsId) {
    var ws = getSpreadsheet().getSheetByName(SHEETS.OUTREACH_QUEUE);
    if (!ws || ws.getLastRow() < 2) return false;
    var ids = ws.getRange(2, COL.OUTREACH_QUEUE.NEWS_ID, ws.getLastRow() - 1, 1).getValues();
    for (var i = 0; i < ids.length; i++) {
      if (String(ids[i][0]) === String(newsId)) return true;
    }
    return false;
  }

  function _nextId(ws) {
    if (ws.getLastRow() < 2) return 1;
    var ids = ws.getRange(2, COL.OUTREACH_QUEUE.OUTREACH_ID, ws.getLastRow() - 1, 1).getValues();
    var max = 0;
    ids.forEach(function(r) {
      var v = parseInt(r[0]);
      if (!isNaN(v) && v > max) max = v;
    });
    return max + 1;
  }

  function _domainFromUrl(url) {
    if (!url) return '';
    var m = String(url).match(/^https?:\/\/([^\/\?#]+)/i);
    if (!m) return '';
    return m[1].toLowerCase().replace(/^www\./, '');
  }

  function _rowToItem(r) {
    return {
      outreachId:     r[COL.OUTREACH_QUEUE.OUTREACH_ID - 1],
      newsId:         r[COL.OUTREACH_QUEUE.NEWS_ID - 1],
      companyName:    r[COL.OUTREACH_QUEUE.COMPANY_NAME - 1],
      domain:         r[COL.OUTREACH_QUEUE.DOMAIN - 1],
      industry:       r[COL.OUTREACH_QUEUE.INDUSTRY - 1],
      headline:       r[COL.OUTREACH_QUEUE.HEADLINE - 1],
      signal:         r[COL.OUTREACH_QUEUE.SIGNAL - 1],
      category:       r[COL.OUTREACH_QUEUE.CATEGORY - 1],
      intentScore:    r[COL.OUTREACH_QUEUE.INTENT_SCORE - 1],
      icpMatch:       r[COL.OUTREACH_QUEUE.ICP_MATCH - 1],
      pitchAngle:     r[COL.OUTREACH_QUEUE.PITCH_ANGLE - 1],
      keyServices:    r[COL.OUTREACH_QUEUE.KEY_SERVICES - 1],
      talkTrack:      r[COL.OUTREACH_QUEUE.TALK_TRACK - 1],
      publishedDate:  r[COL.OUTREACH_QUEUE.PUBLISHED_DATE - 1],
      url:            r[COL.OUTREACH_QUEUE.URL - 1],
      outreachStatus: r[COL.OUTREACH_QUEUE.OUTREACH_STATUS - 1],
      owner:          r[COL.OUTREACH_QUEUE.OWNER - 1],
      contactName:    r[COL.OUTREACH_QUEUE.CONTACT_NAME - 1],
      contactTitle:   r[COL.OUTREACH_QUEUE.CONTACT_TITLE - 1],
      contactEmail:   r[COL.OUTREACH_QUEUE.CONTACT_EMAIL - 1],
      outreachDate:   r[COL.OUTREACH_QUEUE.OUTREACH_DATE - 1],
      followUpDate:   r[COL.OUTREACH_QUEUE.FOLLOW_UP_DATE - 1],
      notes:          r[COL.OUTREACH_QUEUE.NOTES - 1]
    };
  }

  return {
    promote: promote,
    getAll: getAll, getNew: getNew,
    updateStatus: updateStatus,
    getStats: getStats,
    ensureSheet: ensureSheet
  };

})();
