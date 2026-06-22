// ============================================================
// Logger.gs — Structured logging to the Sync Log sheet and
//              Apps Script console.  Supports DEBUG / INFO /
//              WARN / ERROR levels and per-run context.
// ============================================================

var _logLevel   = null;   // resolved lazily from Settings
var _runContext = null;   // set by SyncLog.startRun()

var LOG_LEVELS = { DEBUG: 0, INFO: 1, WARN: 2, ERROR: 3 };

// ----------------------------------------------------------
// PUBLIC API
// ----------------------------------------------------------

/**
 * Logs a DEBUG message.
 */
function logDebug(msg, data) { _log('DEBUG', msg, data); }

/**
 * Logs an INFO message.
 */
function logInfo(msg, data)  { _log('INFO',  msg, data); }

/**
 * Logs a WARN message.
 */
function logWarn(msg, data)  { _log('WARN',  msg, data); }

/**
 * Logs an ERROR message.
 */
function logError(msg, data) { _log('ERROR', msg, data); }

/**
 * Sets a run context object (from SyncLog) so that log entries
 * can be correlated with a specific run ID.
 */
function setLogContext(ctx) {
  _runContext = ctx;
}

/**
 * Clears the run context (called at end of run).
 */
function clearLogContext() {
  _runContext = null;
}

// ----------------------------------------------------------
// INTERNAL
// ----------------------------------------------------------

function _log(level, msg, data) {
  // Resolve log level threshold from settings (cached)
  if (_logLevel === null) {
    try {
      var setting = SettingsManager.get('LOG_LEVEL') || 'INFO';
      _logLevel = LOG_LEVELS[setting.toUpperCase()] !== undefined
        ? LOG_LEVELS[setting.toUpperCase()]
        : LOG_LEVELS.INFO;
    } catch(e) {
      _logLevel = LOG_LEVELS.INFO;
    }
  }

  if (LOG_LEVELS[level] < _logLevel) return;

  var timestamp = isoDateTime(new Date());
  var runId     = _runContext ? _runContext.runId : '-';
  var extra     = data ? ' | ' + JSON.stringify(data) : '';
  var line      = '[' + timestamp + '] [' + level + '] [Run:' + runId + '] ' + msg + extra;

  // Always write to Apps Script console
  switch(level) {
    case 'DEBUG': console.log(line);  break;
    case 'INFO':  console.info(line); break;
    case 'WARN':  console.warn(line); break;
    case 'ERROR': console.error(line); break;
    default:      console.log(line);
  }

  // Write errors and warnings to Sync Log sheet immediately
  if (level === 'ERROR' || level === 'WARN') {
    try {
      _writeToSyncLog(level, msg, extra);
    } catch(e) {
      console.error('Logger: failed to write to Sync Log: ' + e.message);
    }
  }
}

function _writeToSyncLog(level, msg, extra) {
  var ws = getSheet('SYNC_LOG');
  var id = getNextId('SYNC_LOG', COL.SYNC_LOG.RUN_ID);
  var now = new Date();
  ws.appendRow([
    id,
    isoDateTime(now),
    isoDateTime(now),
    (_runContext && _runContext.query) ? _runContext.query : 'system',
    0,
    0,
    level,
    truncate(msg + (extra || ''), 500)
  ]);
}

/**
 * Resets the cached log level (call when Settings change).
 */
function resetLogLevelCache() {
  _logLevel = null;
}
