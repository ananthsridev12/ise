// ============================================================
// CustomMenu.gs — Adds the ISE custom menu to Google Sheets.
//   onOpen() runs automatically when the spreadsheet is opened.
// ============================================================

/**
 * Called by Google Sheets when the spreadsheet opens.
 * Builds the top-level "ISE" menu with all pipeline and admin actions.
 */
function onOpen() {
  var ui = SpreadsheetApp.getUi();

  ui.createMenu('ISE')

    // Pipeline
    .addItem('Run Full Pipeline', 'runFullPipeline')
    .addItem('Run Hourly Pipeline', 'runHourlyPipeline')
    .addItem('Run Daily Pipeline', 'runDailyPipeline')
    .addSeparator()

    // Dashboard
    .addItem('Refresh Dashboard', 'refreshDashboard')
    .addSeparator()

    // Companies sub-menu
    .addSubMenu(
      ui.createMenu('Companies')
        .addItem('Setup Company Candidates Sheet', 'setupCompanyCandidates')
        .addItem('Import Approved Candidates', 'importApprovedCompanyCandidates')
        .addItem('Migrate Companies Schema (add new columns)', 'migrateCompaniesSchema')
    )

    // Outreach Intelligence sub-menu
    .addSubMenu(
      ui.createMenu('Outreach')
        .addItem('Setup Pitch Playbook', 'setupPitchPlaybook')
        .addItem('Setup Outreach Queue', 'setupOutreachQueue')
        .addItem('Setup Account Intelligence', 'setupAccountIntelligence')
        .addItem('Setup Target Accounts', 'setupTargetAccounts')
        .addSeparator()
        .addItem('Generate Watch Queries', 'generateWatchQueries')
        .addItem('Refresh Account Intelligence', 'refreshAccountIntelligence')
    )

    // Setup & Admin sub-menu
    .addSubMenu(
      ui.createMenu('Setup')
        .addItem('Load Signal Pack', 'setupSignalPack')
        .addItem('Add Default RSS Feed Queries', 'setupDefaultRssFeeds')
        .addItem('Setup Triggers', 'setupTriggers')
        .addItem('Remove Triggers', 'removeTriggers')
    )

    .addToUi();
}
