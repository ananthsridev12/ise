// ============================================================
// TriggerManager.gs — Creates, lists, and removes Apps Script
//   time-based triggers for the ISE pipeline.
// ============================================================

var TriggerManager = (function() {

  var HOURLY_FUNCTION  = 'runHourlyPipeline';
  var DAILY_FUNCTION   = 'runDailyPipeline';
  var CLEANUP_FUNCTION = 'runDailyCleanup';

  // ----------------------------------------------------------
  // PUBLIC
  // ----------------------------------------------------------

  /**
   * Sets up the standard ISE trigger set.
   * Safe to call repeatedly — existing triggers are removed first.
   */
  function setupTriggers() {
    removeAllIseTriggers();

    // Hourly pipeline (fetches 'Hourly' frequency queries)
    ScriptApp.newTrigger(HOURLY_FUNCTION)
      .timeBased()
      .everyHours(1)
      .create();

    // Daily pipeline (fetches 'Daily' frequency queries, runs at 7 AM)
    ScriptApp.newTrigger(DAILY_FUNCTION)
      .timeBased()
      .everyDays(1)
      .atHour(7)
      .create();

    // Daily cleanup (purge old logs/hashes, refresh dashboard — runs at 2 AM)
    ScriptApp.newTrigger(CLEANUP_FUNCTION)
      .timeBased()
      .everyDays(1)
      .atHour(2)
      .create();

    logInfo('TriggerManager: triggers set up — hourly pipeline, daily pipeline, daily cleanup');
  }

  /**
   * Removes all ISE-owned triggers.
   */
  function removeAllIseTriggers() {
    var triggers = ScriptApp.getProjectTriggers();
    var iseFns   = [HOURLY_FUNCTION, DAILY_FUNCTION, CLEANUP_FUNCTION];
    var removed  = 0;
    triggers.forEach(function(t) {
      if (iseFns.indexOf(t.getHandlerFunction()) !== -1) {
        ScriptApp.deleteTrigger(t);
        removed++;
      }
    });
    if (removed > 0) logInfo('TriggerManager: removed ' + removed + ' existing ISE triggers');
  }

  /**
   * Returns a list of current ISE trigger descriptions.
   */
  function listTriggers() {
    var triggers = ScriptApp.getProjectTriggers();
    return triggers.map(function(t) {
      return {
        fn:   t.getHandlerFunction(),
        type: t.getEventType().toString(),
        id:   t.getUniqueId()
      };
    });
  }

  /**
   * Creates a single custom time-based trigger.
   *
   * @param {string} fnName    The global function name to call.
   * @param {string} interval  'hourly' | 'daily' | 'weekly'
   * @param {number} [hour]    Hour for daily/weekly triggers (0-23).
   */
  function createTrigger(fnName, interval, hour) {
    var builder = ScriptApp.newTrigger(fnName).timeBased();
    switch(interval.toLowerCase()) {
      case 'hourly': builder.everyHours(1); break;
      case 'daily':  builder.everyDays(1).atHour(hour || 8); break;
      case 'weekly': builder.everyWeeks(1).atHour(hour || 8); break;
      default:       builder.everyHours(1);
    }
    builder.create();
    logInfo('TriggerManager: created ' + interval + ' trigger for ' + fnName);
  }

  return { setupTriggers: setupTriggers, removeAllIseTriggers: removeAllIseTriggers,
           listTriggers: listTriggers, createTrigger: createTrigger };

})();
