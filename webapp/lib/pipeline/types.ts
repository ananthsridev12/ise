export interface SearchQuery {
  queryId: number;
  queryName: string;
  googleQuery: string;
  categoryId: number | null;
  signalId: number | null;
  industryId: number | null;
  country: string;
  language: string;
  frequency: string;
  priority: string;
  enabled: boolean;
  lastRun: string;
  lastSuccess: string;
  remarks: string;
  sourceType: string; // 'GoogleNews' | 'RSS' | 'Indeed' | 'Adzuna'
}

export interface RawNewsItem {
  newsId?: number;
  fetchTime: string;
  publishedDate: string;
  title: string;
  snippet: string;
  url: string;
  sourceId: number | null;
  queryId: number;
  hash: string;
  processed: boolean;
}

export interface Classification {
  signalId: number | null;
  signal: string;
  categoryId: number | null;
  category: string;
  signalScore: number;
  buyingIntent: boolean;
}

export interface KeywordMatch {
  keywordId: number;
  keyword: string;
  signalId: number | null;
  weight: number;
  matchType: string;
}

export interface CompanyMatch {
  companyId: number;
  companyName: string;
  industryId: number | null;
  confidence: number;
}

export interface IcpMatch {
  icpId: number;
  icpCode: string;
  icpName: string;
  confidence: number;
  reason: string;
}

export interface IntentScoreResult {
  signalScore: number;
  keywordScore: number;
  freshnessScore: number;
  sourceScore: number;
  icpScore: number;
  finalScore: number;
  priority: string;
}

export interface PitchContext {
  pitchAngle: string;
  keyServices: string;
  talkTrack: string;
  whyItMatters: string;
}

export interface ProcessedItem {
  newsId: number;
  title: string;
  summary: string;
  company: string;
  industry: string;
  category: string;
  signal: string;
  keywordMatched: string;
  intentScore: number;
  priority: string;
  publishedDate: string;
  url: string;
  status: string;
  icpName?: string;
  companyId?: number;
  domain?: string;
}

export interface PipelineProgress {
  phase: string;
  message: string;
  current?: number;
  total?: number;
}
