// ============================================================
// SettingsManager.gs — Read / write / cache settings from the
//                       "Settings" sheet.
// ============================================================

var SettingsManager = (function() {

  var _cache = null;   // { KEY: value, ... }

  // ----------------------------------------------------------
  // PUBLIC
  // ----------------------------------------------------------

  /**
   * Returns the value for a given setting key.
   * Returns defaultValue (or null) if the key is not found.
   */
  function get(key, defaultValue) {
    _ensureLoaded();
    var val = _cache[key];
    if (val === undefined || val === '') {
      return (defaultValue !== undefined) ? defaultValue : null;
    }
    return val;
  }

  /**
   * Returns a setting as an integer.
   */
  function getInt(key, defaultValue) {
    var v = parseInt(get(key));
    return isNaN(v) ? (defaultValue !== undefined ? defaultValue : 0) : v;
  }

  /**
   * Returns a setting as a boolean (truthy per ISE convention).
   */
  function getBool(key, defaultValue) {
    var v = get(key);
    if (v === null) return (defaultValue !== undefined) ? defaultValue : false;
    return isTruthy(v);
  }

  /**
   * Writes (or updates) a setting key/value in the sheet.
   * Also updates the in-memory cache.
   */
  function set(key, value, description) {
    var ws   = getSheet('SETTINGS');
    var rows = ws.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][COL.SETTINGS.KEY - 1]).trim() === key) {
        ws.getRange(i + 1, COL.SETTINGS.VALUE).setValue(value);
        if (description) ws.getRange(i + 1, COL.SETTINGS.DESCRIPTION).setValue(description);
        _cache[key] = value;
        return;
      }
    }
    // Key not found — append new row
    ws.appendRow([key, value, description || '']);
    _cache[key] = value;
  }

  /**
   * Returns all settings as a plain object.
   */
  function getAll() {
    _ensureLoaded();
    return JSON.parse(JSON.stringify(_cache)); // return a copy
  }

  /**
   * Invalidates the in-memory cache (e.g. after bulk edits).
   */
  function invalidate() {
    _cache = null;
    resetLogLevelCache();
  }

  // ----------------------------------------------------------
  // PRIVATE
  // ----------------------------------------------------------

  function _ensureLoaded() {
    if (_cache !== null) return;
    _cache = {};
    try {
      var rows = getDataRows('SETTINGS');
      rows.forEach(function(row) {
        var key = String(row[COL.SETTINGS.KEY - 1]).trim();
        var val = row[COL.SETTINGS.VALUE - 1];
        if (key) _cache[key] = val;
      });
    } catch(e) {
      console.error('SettingsManager: failed to load settings — ' + e.message);
    }
  }

  // ----------------------------------------------------------
  // EXPOSE
  // ----------------------------------------------------------
  return { get: get, getInt: getInt, getBool: getBool, set: set, getAll: getAll, invalidate: invalidate };

})();
