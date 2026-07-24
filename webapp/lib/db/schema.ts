import { sqliteTable, text, integer, real } from 'drizzle-orm/sqlite-core';

export const settings = sqliteTable('settings', {
  key: text('key').primaryKey(),
  value: text('value').notNull().default(''),
  description: text('description').default(''),
});

export const categories = sqliteTable('categories', {
  categoryId: integer('category_id').primaryKey({ autoIncrement: true }),
  category: text('category').notNull(),
  description: text('description').default(''),
});

export const signals = sqliteTable('signals', {
  signalId: integer('signal_id').primaryKey({ autoIncrement: true }),
  signal: text('signal').notNull(),
  categoryId: integer('category_id'),
  defaultScore: real('default_score').default(50),
  buyingIntent: integer('buying_intent', { mode: 'boolean' }).default(false),
  active: integer('active', { mode: 'boolean' }).default(true),
});

export const keywords = sqliteTable('keywords', {
  keywordId: integer('keyword_id').primaryKey({ autoIncrement: true }),
  keyword: text('keyword').notNull(),
  signalId: integer('signal_id'),
  weight: real('weight').default(1),
  matchType: text('match_type').default('contains'),
  active: integer('active', { mode: 'boolean' }).default(true),
});

export const industries = sqliteTable('industries', {
  industryId: integer('industry_id').primaryKey({ autoIncrement: true }),
  industry: text('industry').notNull(),
  vertical: text('vertical').default(''),
  parentIndustry: text('parent_industry').default(''),
  active: integer('active', { mode: 'boolean' }).default(true),
});

export const sources = sqliteTable('sources', {
  sourceId: integer('source_id').primaryKey({ autoIncrement: true }),
  source: text('source').notNull(),
  domain: text('domain').default(''),
  type: text('type').default(''),
  priority: text('priority').default('Medium'),
  active: integer('active', { mode: 'boolean' }).default(true),
});

export const companies = sqliteTable('companies', {
  companyId: integer('company_id').primaryKey({ autoIncrement: true }),
  companyName: text('company_name').notNull(),
  website: text('website').default(''),
  industryId: integer('industry_id'),
  country: text('country').default(''),
  state: text('state').default(''),
  city: text('city').default(''),
  notes: text('notes').default(''),
  active: integer('active', { mode: 'boolean' }).default(true),
  domain: text('domain').default(''),
  linkedinUrl: text('linkedin_url').default(''),
  employeeRange: text('employee_range').default(''),
  revenueRange: text('revenue_range').default(''),
  targetAccount: integer('target_account', { mode: 'boolean' }).default(false),
});

export const companyAliases = sqliteTable('company_aliases', {
  aliasId: integer('alias_id').primaryKey({ autoIncrement: true }),
  companyId: integer('company_id').notNull(),
  alias: text('alias').notNull(),
});

export const companyCandidates = sqliteTable('company_candidates', {
  candidateId: integer('candidate_id').primaryKey({ autoIncrement: true }),
  companyName: text('company_name').notNull(),
  sourceNewsId: integer('source_news_id'),
  title: text('title').default(''),
  url: text('url').default(''),
  confidence: real('confidence').default(0),
  suggestedIndustry: text('suggested_industry').default(''),
  status: text('status').default('New'),
  created: text('created').default(''),
  notes: text('notes').default(''),
});

export const icpMaster = sqliteTable('icp_master', {
  icpId: integer('icp_id').primaryKey({ autoIncrement: true }),
  vertical: text('vertical').default(''),
  icpCode: text('icp_code').default(''),
  segment: text('segment').default(''),
  subSegment: text('sub_segment').default(''),
  productService: text('product_service').default(''),
  primaryServicesToPitch: text('primary_services_to_pitch').default(''),
  category: text('category').default(''),
  tier: text('tier').default(''),
  geo: text('geo').default(''),
  employees: text('employees').default(''),
  revenue: text('revenue').default(''),
});

export const searchQueries = sqliteTable('search_queries', {
  queryId: integer('query_id').primaryKey({ autoIncrement: true }),
  queryName: text('query_name').notNull(),
  googleQuery: text('google_query').notNull(),
  categoryId: integer('category_id'),
  signalId: integer('signal_id'),
  industryId: integer('industry_id'),
  country: text('country').default('US'),
  language: text('language').default('en'),
  frequency: text('frequency').default('Daily'),
  priority: text('priority').default('Medium'),
  enabled: integer('enabled', { mode: 'boolean' }).default(true),
  lastRun: text('last_run').default(''),
  lastSuccess: text('last_success').default(''),
  remarks: text('remarks').default(''),
  sourceType: text('source_type').default('GoogleNews'),
  tenantId: integer('tenant_id').default(1),
});

export const rawNews = sqliteTable('raw_news', {
  newsId: integer('news_id').primaryKey({ autoIncrement: true }),
  fetchTime: text('fetch_time').default(''),
  publishedDate: text('published_date').default(''),
  title: text('title').notNull(),
  snippet: text('snippet').default(''),
  url: text('url').notNull(),
  sourceId: integer('source_id'),
  queryId: integer('query_id'),
  hash: text('hash').notNull(),
  processed: integer('processed', { mode: 'boolean' }).default(false),
});

export const processedNews = sqliteTable('processed_news', {
  newsId: integer('news_id').primaryKey(),
  title: text('title').default(''),
  summary: text('summary').default(''),
  company: text('company').default(''),
  industry: text('industry').default(''),
  category: text('category').default(''),
  signal: text('signal').default(''),
  keywordMatched: text('keyword_matched').default(''),
  intentScore: real('intent_score').default(0),
  priority: text('priority').default('Low'),
  publishedDate: text('published_date').default(''),
  url: text('url').default(''),
  status: text('status').default('New'),
});

export const intentScore = sqliteTable('intent_score', {
  newsId: integer('news_id').primaryKey(),
  signalScore: real('signal_score').default(0),
  keywordScore: real('keyword_score').default(0),
  freshnessScore: real('freshness_score').default(0),
  sourceScore: real('source_score').default(0),
  icpScore: real('icp_score').default(0),
  finalScore: real('final_score').default(0),
});

export const icpMatching = sqliteTable('icp_matching', {
  matchId: integer('match_id').primaryKey({ autoIncrement: true }),
  newsId: integer('news_id'),
  icpId: integer('icp_id'),
  confidence: real('confidence').default(0),
  reason: text('reason').default(''),
});

export const priorityRules = sqliteTable('priority_rules', {
  ruleId: integer('rule_id').primaryKey({ autoIncrement: true }),
  signal: text('signal').default(''),
  condition: text('condition').default(''),
  adjustment: real('adjustment').default(0),
  remarks: text('remarks').default(''),
});

export const duplicateIndex = sqliteTable('duplicate_index', {
  hash: text('hash').primaryKey(),
  newsId: integer('news_id'),
  url: text('url').default(''),
  created: text('created').default(''),
});

export const syncLog = sqliteTable('sync_log', {
  runId: integer('run_id').primaryKey({ autoIncrement: true }),
  startTime: text('start_time').default(''),
  endTime: text('end_time').default(''),
  query: text('query').default(''),
  recordsFetched: integer('records_fetched').default(0),
  recordsInserted: integer('records_inserted').default(0),
  status: text('status').default(''),
  error: text('error').default(''),
});

export const actionQueue = sqliteTable('action_queue', {
  queueId: integer('queue_id').primaryKey({ autoIncrement: true }),
  newsId: integer('news_id'),
  company: text('company').default(''),
  headline: text('headline').default(''),
  signal: text('signal').default(''),
  category: text('category').default(''),
  intentScore: real('intent_score').default(0),
  icp: text('icp').default(''),
  publishedDate: text('published_date').default(''),
  url: text('url').default(''),
  actionStatus: text('action_status').default('New'),
  owner: text('owner').default(''),
  notes: text('notes').default(''),
  tenantId: integer('tenant_id').default(1),
});

export const pitchPlaybook = sqliteTable('pitch_playbook', {
  playbookId: integer('playbook_id').primaryKey({ autoIncrement: true }),
  signal: text('signal').default(''),
  category: text('category').default(''),
  whyItMatters: text('why_it_matters').default(''),
  pitchAngle: text('pitch_angle').default(''),
  keyServices: text('key_services').default(''),
  talkTrack1: text('talk_track_1').default(''),
  talkTrack2: text('talk_track_2').default(''),
  talkTrack3: text('talk_track_3').default(''),
  priority: integer('priority').default(5),
});

export const outreachQueue = sqliteTable('outreach_queue', {
  outreachId: integer('outreach_id').primaryKey({ autoIncrement: true }),
  newsId: integer('news_id'),
  companyName: text('company_name').default(''),
  domain: text('domain').default(''),
  industry: text('industry').default(''),
  headline: text('headline').default(''),
  signal: text('signal').default(''),
  category: text('category').default(''),
  intentScore: real('intent_score').default(0),
  icpMatch: text('icp_match').default(''),
  pitchAngle: text('pitch_angle').default(''),
  keyServices: text('key_services').default(''),
  talkTrack: text('talk_track').default(''),
  publishedDate: text('published_date').default(''),
  url: text('url').default(''),
  outreachStatus: text('outreach_status').default('New'),
  owner: text('owner').default(''),
  contactName: text('contact_name').default(''),
  contactTitle: text('contact_title').default(''),
  contactEmail: text('contact_email').default(''),
  outreachDate: text('outreach_date').default(''),
  followUpDate: text('follow_up_date').default(''),
  notes: text('notes').default(''),
  tenantId: integer('tenant_id').default(1),
});

export const accountIntelligence = sqliteTable('account_intelligence', {
  accountId: integer('account_id').primaryKey({ autoIncrement: true }),
  companyName: text('company_name').notNull(),
  industry: text('industry').default(''),
  icpMatch: text('icp_match').default(''),
  totalSignals: integer('total_signals').default(0),
  signalTypes: text('signal_types').default(''),
  lastSignalDate: text('last_signal_date').default(''),
  highestScore: real('highest_score').default(0),
  topSignal: text('top_signal').default(''),
  topPitchAngle: text('top_pitch_angle').default(''),
  keyServices: text('key_services').default(''),
  outreachReady: text('outreach_ready').default('NO'),
  lastOutreachDate: text('last_outreach_date').default(''),
  notes: text('notes').default(''),
  tenantId: integer('tenant_id').default(1),
});

export const targetAccounts = sqliteTable('target_accounts', {
  accountId: integer('account_id').primaryKey({ autoIncrement: true }),
  companyName: text('company_name').notNull(),
  domain: text('domain').default(''),
  industry: text('industry').default(''),
  icpMatch: text('icp_match').default(''),
  watchPriority: text('watch_priority').default('Medium'),
  autoSearch: integer('auto_search', { mode: 'boolean' }).default(false),
  notes: text('notes').default(''),
  tenantId: integer('tenant_id').default(1),
});

// ── Auth & Multi-tenant ────────────────────────────────────────────────────

export const tenants = sqliteTable('tenants', {
  id: integer('id').primaryKey({ autoIncrement: true }),
  name: text('name').notNull(),
  slug: text('slug').notNull().unique(),
  active: integer('active', { mode: 'boolean' }).default(true),
  createdAt: text('created_at').default(''),
});

export const users = sqliteTable('users', {
  id: integer('id').primaryKey({ autoIncrement: true }),
  tenantId: integer('tenant_id').notNull(),
  email: text('email').notNull().unique(),
  passwordHash: text('password_hash').notNull(),
  displayName: text('display_name').notNull().default(''),
  role: text('role').notNull().default('member'), // 'admin' | 'member'
  active: integer('active', { mode: 'boolean' }).default(true),
  createdAt: text('created_at').default(''),
});

export const sessions = sqliteTable('sessions', {
  token: text('token').primaryKey(),
  userId: integer('user_id').notNull(),
  tenantId: integer('tenant_id').notNull(),
  expiresAt: text('expires_at').notNull(),
  createdAt: text('created_at').default(''),
});

// ── Knowledge Hub (per-tenant) ─────────────────────────────────────────────

export const kbVerticals = sqliteTable('kb_verticals', {
  id: integer('id').primaryKey({ autoIncrement: true }),
  tenantId: integer('tenant_id').notNull(),
  name: text('name').notNull(),
  focus: text('focus').default(''),
  industries: text('industries').default(''),
  priority: text('priority').default('core'),
  createdAt: text('created_at').default(''),
});

export const kbServices = sqliteTable('kb_services', {
  id: integer('id').primaryKey({ autoIncrement: true }),
  tenantId: integer('tenant_id').notNull(),
  verticalId: integer('vertical_id'),
  name: text('name').notNull(),
  description: text('description').default(''),
  signalKeywords: text('signal_keywords').default(''),
  signalTypes: text('signal_types').default(''),
  techTriggers: text('tech_triggers').default(''),
  createdAt: text('created_at').default(''),
});

// ── Member permission assignments ─────────────────────────────────────────

export const userVerticals = sqliteTable('user_verticals', {
  id: integer('id').primaryKey({ autoIncrement: true }),
  userId: integer('user_id').notNull(),
  verticalId: integer('vertical_id').notNull(),
});

export const userServices = sqliteTable('user_services', {
  id: integer('id').primaryKey({ autoIncrement: true }),
  userId: integer('user_id').notNull(),
  serviceId: integer('service_id').notNull(),
});
