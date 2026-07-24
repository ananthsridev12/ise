import Database from 'better-sqlite3';
import path from 'path';
import fs from 'fs';
import bcrypt from 'bcryptjs';
import crypto from 'crypto';

const dbPath = path.join(process.cwd(), 'ise.db');

export function initDb() {
  const sqlite = new Database(dbPath);
  sqlite.pragma('journal_mode = WAL');
  sqlite.pragma('foreign_keys = ON');

  sqlite.exec(`
    CREATE TABLE IF NOT EXISTS settings (
      key TEXT PRIMARY KEY, value TEXT NOT NULL DEFAULT '', description TEXT DEFAULT ''
    );
    CREATE TABLE IF NOT EXISTS categories (
      category_id INTEGER PRIMARY KEY AUTOINCREMENT, category TEXT NOT NULL, description TEXT DEFAULT ''
    );
    CREATE TABLE IF NOT EXISTS signals (
      signal_id INTEGER PRIMARY KEY AUTOINCREMENT, signal TEXT NOT NULL, category_id INTEGER,
      default_score REAL DEFAULT 50, buying_intent INTEGER DEFAULT 0, active INTEGER DEFAULT 1
    );
    CREATE TABLE IF NOT EXISTS keywords (
      keyword_id INTEGER PRIMARY KEY AUTOINCREMENT, keyword TEXT NOT NULL, signal_id INTEGER,
      weight REAL DEFAULT 1, match_type TEXT DEFAULT 'contains', active INTEGER DEFAULT 1
    );
    CREATE TABLE IF NOT EXISTS industries (
      industry_id INTEGER PRIMARY KEY AUTOINCREMENT, industry TEXT NOT NULL, vertical TEXT DEFAULT '',
      parent_industry TEXT DEFAULT '', active INTEGER DEFAULT 1
    );
    CREATE TABLE IF NOT EXISTS sources (
      source_id INTEGER PRIMARY KEY AUTOINCREMENT, source TEXT NOT NULL, domain TEXT DEFAULT '',
      type TEXT DEFAULT '', priority TEXT DEFAULT 'Medium', active INTEGER DEFAULT 1
    );
    CREATE TABLE IF NOT EXISTS companies (
      company_id INTEGER PRIMARY KEY AUTOINCREMENT, company_name TEXT NOT NULL, website TEXT DEFAULT '',
      industry_id INTEGER, country TEXT DEFAULT '', state TEXT DEFAULT '', city TEXT DEFAULT '',
      notes TEXT DEFAULT '', active INTEGER DEFAULT 1, domain TEXT DEFAULT '', linkedin_url TEXT DEFAULT '',
      employee_range TEXT DEFAULT '', revenue_range TEXT DEFAULT '', target_account INTEGER DEFAULT 0
    );
    CREATE TABLE IF NOT EXISTS company_aliases (
      alias_id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER NOT NULL, alias TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS company_candidates (
      candidate_id INTEGER PRIMARY KEY AUTOINCREMENT, company_name TEXT NOT NULL,
      source_news_id INTEGER, title TEXT DEFAULT '', url TEXT DEFAULT '', confidence REAL DEFAULT 0,
      suggested_industry TEXT DEFAULT '', status TEXT DEFAULT 'New', created TEXT DEFAULT '', notes TEXT DEFAULT ''
    );
    CREATE TABLE IF NOT EXISTS icp_master (
      icp_id INTEGER PRIMARY KEY AUTOINCREMENT, vertical TEXT DEFAULT '', icp_code TEXT DEFAULT '',
      segment TEXT DEFAULT '', sub_segment TEXT DEFAULT '', product_service TEXT DEFAULT '',
      primary_services_to_pitch TEXT DEFAULT '', category TEXT DEFAULT '', tier TEXT DEFAULT '',
      geo TEXT DEFAULT '', employees TEXT DEFAULT '', revenue TEXT DEFAULT ''
    );
    CREATE TABLE IF NOT EXISTS search_queries (
      query_id INTEGER PRIMARY KEY AUTOINCREMENT, query_name TEXT NOT NULL, google_query TEXT NOT NULL,
      category_id INTEGER, signal_id INTEGER, industry_id INTEGER, country TEXT DEFAULT 'US',
      language TEXT DEFAULT 'en', frequency TEXT DEFAULT 'Daily', priority TEXT DEFAULT 'Medium',
      enabled INTEGER DEFAULT 1, last_run TEXT DEFAULT '', last_success TEXT DEFAULT '',
      remarks TEXT DEFAULT '', source_type TEXT DEFAULT 'GoogleNews'
    );
    CREATE TABLE IF NOT EXISTS raw_news (
      news_id INTEGER PRIMARY KEY AUTOINCREMENT, fetch_time TEXT DEFAULT '', published_date TEXT DEFAULT '',
      title TEXT NOT NULL, snippet TEXT DEFAULT '', url TEXT NOT NULL, source_id INTEGER,
      query_id INTEGER, hash TEXT NOT NULL, processed INTEGER DEFAULT 0
    );
    CREATE TABLE IF NOT EXISTS processed_news (
      news_id INTEGER PRIMARY KEY, title TEXT DEFAULT '', summary TEXT DEFAULT '', company TEXT DEFAULT '',
      industry TEXT DEFAULT '', category TEXT DEFAULT '', signal TEXT DEFAULT '', keyword_matched TEXT DEFAULT '',
      intent_score REAL DEFAULT 0, priority TEXT DEFAULT 'Low', published_date TEXT DEFAULT '',
      url TEXT DEFAULT '', status TEXT DEFAULT 'New'
    );
    CREATE TABLE IF NOT EXISTS intent_score (
      news_id INTEGER PRIMARY KEY, signal_score REAL DEFAULT 0, keyword_score REAL DEFAULT 0,
      freshness_score REAL DEFAULT 0, source_score REAL DEFAULT 0, icp_score REAL DEFAULT 0,
      final_score REAL DEFAULT 0
    );
    CREATE TABLE IF NOT EXISTS icp_matching (
      match_id INTEGER PRIMARY KEY AUTOINCREMENT, news_id INTEGER, icp_id INTEGER,
      confidence REAL DEFAULT 0, reason TEXT DEFAULT ''
    );
    CREATE TABLE IF NOT EXISTS priority_rules (
      rule_id INTEGER PRIMARY KEY AUTOINCREMENT, signal TEXT DEFAULT '', condition TEXT DEFAULT '',
      adjustment REAL DEFAULT 0, remarks TEXT DEFAULT ''
    );
    CREATE TABLE IF NOT EXISTS duplicate_index (
      hash TEXT PRIMARY KEY, news_id INTEGER, url TEXT DEFAULT '', created TEXT DEFAULT ''
    );
    CREATE TABLE IF NOT EXISTS sync_log (
      run_id INTEGER PRIMARY KEY AUTOINCREMENT, start_time TEXT DEFAULT '', end_time TEXT DEFAULT '',
      query TEXT DEFAULT '', records_fetched INTEGER DEFAULT 0, records_inserted INTEGER DEFAULT 0,
      status TEXT DEFAULT '', error TEXT DEFAULT ''
    );
    CREATE TABLE IF NOT EXISTS action_queue (
      queue_id INTEGER PRIMARY KEY AUTOINCREMENT, news_id INTEGER, company TEXT DEFAULT '',
      headline TEXT DEFAULT '', signal TEXT DEFAULT '', category TEXT DEFAULT '',
      intent_score REAL DEFAULT 0, icp TEXT DEFAULT '', published_date TEXT DEFAULT '',
      url TEXT DEFAULT '', action_status TEXT DEFAULT 'New', owner TEXT DEFAULT '', notes TEXT DEFAULT ''
    );
    CREATE TABLE IF NOT EXISTS pitch_playbook (
      playbook_id INTEGER PRIMARY KEY AUTOINCREMENT, signal TEXT DEFAULT '', category TEXT DEFAULT '',
      why_it_matters TEXT DEFAULT '', pitch_angle TEXT DEFAULT '', key_services TEXT DEFAULT '',
      talk_track_1 TEXT DEFAULT '', talk_track_2 TEXT DEFAULT '', talk_track_3 TEXT DEFAULT '',
      priority INTEGER DEFAULT 5
    );
    CREATE TABLE IF NOT EXISTS outreach_queue (
      outreach_id INTEGER PRIMARY KEY AUTOINCREMENT, news_id INTEGER, company_name TEXT DEFAULT '',
      domain TEXT DEFAULT '', industry TEXT DEFAULT '', headline TEXT DEFAULT '', signal TEXT DEFAULT '',
      category TEXT DEFAULT '', intent_score REAL DEFAULT 0, icp_match TEXT DEFAULT '',
      pitch_angle TEXT DEFAULT '', key_services TEXT DEFAULT '', talk_track TEXT DEFAULT '',
      published_date TEXT DEFAULT '', url TEXT DEFAULT '', outreach_status TEXT DEFAULT 'New',
      owner TEXT DEFAULT '', contact_name TEXT DEFAULT '', contact_title TEXT DEFAULT '',
      contact_email TEXT DEFAULT '', outreach_date TEXT DEFAULT '', follow_up_date TEXT DEFAULT '',
      notes TEXT DEFAULT ''
    );
    CREATE TABLE IF NOT EXISTS account_intelligence (
      account_id INTEGER PRIMARY KEY AUTOINCREMENT, company_name TEXT NOT NULL, industry TEXT DEFAULT '',
      icp_match TEXT DEFAULT '', total_signals INTEGER DEFAULT 0, signal_types TEXT DEFAULT '',
      last_signal_date TEXT DEFAULT '', highest_score REAL DEFAULT 0, top_signal TEXT DEFAULT '',
      top_pitch_angle TEXT DEFAULT '', key_services TEXT DEFAULT '', outreach_ready TEXT DEFAULT 'NO',
      last_outreach_date TEXT DEFAULT '', notes TEXT DEFAULT ''
    );
    CREATE TABLE IF NOT EXISTS target_accounts (
      account_id INTEGER PRIMARY KEY AUTOINCREMENT, company_name TEXT NOT NULL, domain TEXT DEFAULT '',
      industry TEXT DEFAULT '', icp_match TEXT DEFAULT '', watch_priority TEXT DEFAULT 'Medium',
      auto_search INTEGER DEFAULT 0, notes TEXT DEFAULT ''
    );

    CREATE TABLE IF NOT EXISTS tenants (
      id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, slug TEXT NOT NULL UNIQUE,
      active INTEGER DEFAULT 1, created_at TEXT DEFAULT ''
    );
    CREATE TABLE IF NOT EXISTS users (
      id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, email TEXT NOT NULL UNIQUE,
      password_hash TEXT NOT NULL, display_name TEXT NOT NULL DEFAULT '', role TEXT NOT NULL DEFAULT 'member',
      active INTEGER DEFAULT 1, created_at TEXT DEFAULT ''
    );
    CREATE TABLE IF NOT EXISTS sessions (
      token TEXT PRIMARY KEY, user_id INTEGER NOT NULL, tenant_id INTEGER NOT NULL,
      expires_at TEXT NOT NULL, created_at TEXT DEFAULT ''
    );
    CREATE TABLE IF NOT EXISTS kb_verticals (
      id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, name TEXT NOT NULL,
      focus TEXT DEFAULT '', industries TEXT DEFAULT '', priority TEXT DEFAULT 'core',
      created_at TEXT DEFAULT ''
    );
    CREATE TABLE IF NOT EXISTS kb_services (
      id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, vertical_id INTEGER,
      name TEXT NOT NULL, description TEXT DEFAULT '', signal_keywords TEXT DEFAULT '',
      signal_types TEXT DEFAULT '', tech_triggers TEXT DEFAULT '', created_at TEXT DEFAULT ''
    );
    CREATE TABLE IF NOT EXISTS user_verticals (
      id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, vertical_id INTEGER NOT NULL,
      UNIQUE(user_id, vertical_id)
    );
    CREATE TABLE IF NOT EXISTS user_services (
      id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, service_id INTEGER NOT NULL,
      UNIQUE(user_id, service_id)
    );
  `);

  // Add tenant_id to existing tables (safe on existing DBs)
  const addCols = [
    'ALTER TABLE target_accounts ADD COLUMN tenant_id INTEGER DEFAULT 1',
    'ALTER TABLE outreach_queue ADD COLUMN tenant_id INTEGER DEFAULT 1',
    'ALTER TABLE action_queue ADD COLUMN tenant_id INTEGER DEFAULT 1',
    'ALTER TABLE account_intelligence ADD COLUMN tenant_id INTEGER DEFAULT 1',
    'ALTER TABLE search_queries ADD COLUMN tenant_id INTEGER DEFAULT 1',
  ];
  for (const sql of addCols) {
    try { sqlite.exec(sql); } catch (_) { /* column already exists */ }
  }
  // Back-fill existing rows to default tenant 1
  const backfill = ['target_accounts', 'outreach_queue', 'action_queue', 'account_intelligence', 'search_queries'];
  for (const tbl of backfill) {
    try { sqlite.exec(`UPDATE ${tbl} SET tenant_id = 1 WHERE tenant_id IS NULL`); } catch (_) {}
  }

  // Seed default tenant if none exists
  const tenantCount = (sqlite.prepare('SELECT COUNT(*) as c FROM tenants').get() as any).c;
  if (tenantCount === 0) {
    const now = new Date().toISOString();
    sqlite.prepare(
      "INSERT INTO tenants (name, slug, active, created_at) VALUES (?, ?, 1, ?)"
    ).run('SolidPro', 'solidpro', now);

    const tenantId = (sqlite.prepare('SELECT id FROM tenants WHERE slug = ?').get('solidpro') as any).id;
    const hash = bcrypt.hashSync('admin123', 10);
    sqlite.prepare(
      "INSERT INTO users (tenant_id, email, password_hash, display_name, role, active, created_at) VALUES (?, ?, ?, ?, ?, 1, ?)"
    ).run(tenantId, 'admin@solidpro.com', hash, 'Admin', 'admin', now);
  }

  sqlite.close();
}
