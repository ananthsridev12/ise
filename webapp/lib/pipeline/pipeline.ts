import { db } from '@/lib/db';
import { searchQueries, rawNews, processedNews, intentScore as intentScoreTable, icpMatching, actionQueue, outreachQueue, accountIntelligence, syncLog, companyCandidates } from '@/lib/db/schema';
import { eq, and } from 'drizzle-orm';
import { fetchRss, fetchAdzuna, buildIndeedUrl, AdzunaJob } from './newsFetcher';
import { parse, adzunaJobToRawItem } from './rssParser';
import { filterNew, register as registerHash } from './duplicateChecker';
import { match as matchKeywords } from './keywordMatcher';
import { classify } from './signalClassifier';
import { extract as extractCompany, guessCandidate } from './companyExtractor';
import { matchIcp } from './icpMatcher';
import { compute as computeScore } from './intentScoreEngine';
import { lookup as lookupPitch } from './pitchPlaybook';
import { isoDateTime } from './utils';
import { getSetting, getSettingInt } from './settings';
import { SearchQuery, RawNewsItem, ProcessedItem, PipelineProgress } from './types';

type ProgressFn = (p: PipelineProgress) => void;

// ----------------------------------------------------------------
// Entry points
// ----------------------------------------------------------------

export async function runPipeline(frequency: 'full' | 'hourly' | 'daily', onProgress?: ProgressFn): Promise<PipelineStats> {
  const progress = onProgress || (() => {});

  const queries = await _getActiveQueries(frequency);
  if (!queries.length) {
    progress({ phase: 'idle', message: 'No active queries found for ' + frequency + ' run.' });
    return { fetched: 0, newItems: 0, processed: 0, promoted: 0 };
  }

  progress({ phase: 'fetch', message: `Starting: ${queries.length} active queries to fetch...` });

  const runId = await _startRun();
  let totalFetched = 0;
  let totalNew = 0;
  const allNewItems: RawNewsItem[] = [];

  for (let i = 0; i < queries.length; i++) {
    const query = queries[i];
    progress({ phase: 'fetch', message: `[${i + 1}/${queries.length}] Fetching: ${query.queryName}`, current: i + 1, total: queries.length });

    try {
      const newItems = await _fetchAndInsert(query);
      totalFetched += newItems.length;
      totalNew += newItems.length;
      allNewItems.push(...newItems);

      await db.update(searchQueries)
        .set({ lastRun: isoDateTime() })
        .where(eq(searchQueries.queryId, query.queryId))
        .run();
    } catch (e) {
      console.error('Pipeline fetch error:', e);
    }
  }

  progress({ phase: 'classify', message: `Fetch done: ${totalFetched} articles found, ${totalNew} new. Classifying...` });

  const promoted = await _processItems(allNewItems, onProgress);

  progress({ phase: 'intelligence', message: 'Updating Account Intelligence...' });
  await refreshAccountIntelligence();

  await _endRun(runId, totalFetched, totalNew);

  progress({ phase: 'done', message: `Pipeline complete: ${totalFetched} fetched, ${totalNew} new, ${promoted} promoted.` });

  return { fetched: totalFetched, newItems: totalNew, processed: allNewItems.length, promoted };
}

// ----------------------------------------------------------------
// Fetch & insert raw news for one query
// ----------------------------------------------------------------
async function _fetchAndInsert(query: SearchQuery): Promise<RawNewsItem[]> {
  let items: RawNewsItem[] = [];

  if (query.sourceType === 'Adzuna') {
    const appId = await getSetting('ADZUNA_APP_ID', '');
    const appKey = await getSetting('ADZUNA_APP_KEY', '');
    const jobs = await fetchAdzuna(query.googleQuery, query.country, appId, appKey);
    items = jobs.map((j: AdzunaJob) => adzunaJobToRawItem(j, query.queryId));
  } else if (query.sourceType === 'Indeed') {
    const indeedUrl = buildIndeedUrl(query.googleQuery, query.country);
    const xml = await fetchRss({ ...query, googleQuery: indeedUrl, sourceType: 'RSS' });
    items = xml ? await parse(xml, query) : [];
  } else {
    const xml = await fetchRss(query);
    items = xml ? await parse(xml, query) : [];
  }

  const newItems = await filterNew(items);
  if (!newItems.length) return [];

  const inserted: RawNewsItem[] = [];
  for (const item of newItems) {
    const [row] = await db.insert(rawNews).values({
      fetchTime: item.fetchTime,
      publishedDate: item.publishedDate,
      title: item.title,
      snippet: item.snippet,
      url: item.url,
      sourceId: item.sourceId,
      queryId: item.queryId,
      hash: item.hash,
      processed: false,
    }).returning({ newsId: rawNews.newsId });

    const newsId = row.newsId;
    await registerHash(newsId, item.hash, item.url);
    inserted.push({ ...item, newsId });
  }

  return inserted;
}

// ----------------------------------------------------------------
// Classify & score each item
// ----------------------------------------------------------------
async function _processItems(items: RawNewsItem[], onProgress?: ProgressFn): Promise<number> {
  const progress = onProgress || (() => {});
  const minScore = await getSettingInt('MIN_INTENT_SCORE', 30);
  let promoted = 0;

  for (let i = 0; i < items.length; i++) {
    const item = items[i];
    if (i % 5 === 0) {
      progress({ phase: 'classify', message: `Classifying ${i + 1}/${items.length} articles...`, current: i + 1, total: items.length });
    }

    try {
      const kwMatches = await matchKeywords({ title: item.title, snippet: item.snippet });
      const classification = await classify({ title: item.title, snippet: item.snippet }, kwMatches);
      const coMatch = await extractCompany({ title: item.title, snippet: item.snippet, url: item.url });

      if (!coMatch) {
        const guess = await guessCandidate({ title: item.title });
        if (guess) {
          await db.insert(companyCandidates).values({
            companyName: guess.companyName,
            sourceNewsId: item.newsId,
            title: item.title,
            url: item.url,
            confidence: guess.confidence,
            status: 'New',
            created: isoDateTime(),
            notes: guess.reason,
          }).onConflictDoNothing().run();
        }
      }

      const icpMatch = await matchIcp(
        { title: item.title, snippet: item.snippet, url: item.url },
        coMatch,
        classification,
      );

      const scoreResult = await computeScore(
        { title: item.title, snippet: item.snippet, publishedDate: item.publishedDate, url: item.url },
        classification,
        kwMatches,
        icpMatch,
        item.sourceId,
      );

      const company = coMatch?.companyName || '';
      const industry = coMatch ? await _getIndustryName(coMatch.industryId) : '';

      await db.insert(processedNews).values({
        newsId: item.newsId!,
        title: item.title,
        summary: item.snippet.substring(0, 300),
        company,
        industry,
        category: classification.category,
        signal: classification.signal,
        keywordMatched: kwMatches.map(k => k.keyword).join(', '),
        intentScore: scoreResult.finalScore,
        priority: scoreResult.priority,
        publishedDate: item.publishedDate,
        url: item.url,
        status: 'New',
      }).onConflictDoNothing().run();

      await db.insert(intentScoreTable).values({
        newsId: item.newsId!,
        signalScore: scoreResult.signalScore,
        keywordScore: scoreResult.keywordScore,
        freshnessScore: scoreResult.freshnessScore,
        sourceScore: scoreResult.sourceScore,
        icpScore: scoreResult.icpScore,
        finalScore: scoreResult.finalScore,
      }).onConflictDoNothing().run();

      if (icpMatch) {
        await db.insert(icpMatching).values({
          newsId: item.newsId!,
          icpId: icpMatch.icpId,
          confidence: icpMatch.confidence,
          reason: icpMatch.reason,
        }).run();
      }

      await db.update(rawNews).set({ processed: true }).where(eq(rawNews.newsId, item.newsId!)).run();

      if (scoreResult.finalScore >= minScore) {
        const icpName = icpMatch?.icpName || '';
        const processedItem: ProcessedItem = {
          newsId: item.newsId!, title: item.title, summary: item.snippet.substring(0, 300),
          company, industry, category: classification.category, signal: classification.signal,
          keywordMatched: kwMatches.map(k => k.keyword).join(', '),
          intentScore: scoreResult.finalScore, priority: scoreResult.priority,
          publishedDate: item.publishedDate, url: item.url, status: 'New', icpName,
          companyId: coMatch?.companyId, domain: _parseDomain(item.url),
        };

        await _promoteToActionQueue(processedItem, minScore);
        const pitchContext = await lookupPitch(classification.signal);
        await _promoteToOutreachQueue(processedItem, pitchContext);
        promoted++;
      }
    } catch (e) {
      console.error('_processItems error for newsId', item.newsId, e);
    }
  }

  progress({ phase: 'classify', message: `Classified: ${items.length} articles, ${promoted} promoted to queues.` });
  return promoted;
}

async function _promoteToActionQueue(item: ProcessedItem, minScore: number) {
  if (item.intentScore < minScore) return;
  const existing = await db.select({ queueId: actionQueue.queueId })
    .from(actionQueue).where(eq(actionQueue.newsId, item.newsId)).get();
  if (existing) return;

  await db.insert(actionQueue).values({
    newsId: item.newsId, company: item.company, headline: item.title,
    signal: item.signal, category: item.category, intentScore: item.intentScore,
    icp: item.icpName || '', publishedDate: item.publishedDate, url: item.url,
    actionStatus: 'New', owner: '', notes: '',
  }).run();
}

async function _promoteToOutreachQueue(item: ProcessedItem, pitch: import('./types').PitchContext) {
  const existing = await db.select({ outreachId: outreachQueue.outreachId })
    .from(outreachQueue).where(eq(outreachQueue.newsId, item.newsId)).get();
  if (existing) return;

  await db.insert(outreachQueue).values({
    newsId: item.newsId, companyName: item.company, domain: item.domain || '',
    industry: item.industry, headline: item.title, signal: item.signal,
    category: item.category, intentScore: item.intentScore, icpMatch: item.icpName || '',
    pitchAngle: pitch.pitchAngle, keyServices: pitch.keyServices, talkTrack: pitch.talkTrack,
    publishedDate: item.publishedDate, url: item.url, outreachStatus: 'New',
  }).run();
}

// ----------------------------------------------------------------
// Account Intelligence refresh
// ----------------------------------------------------------------
export async function refreshAccountIntelligence() {
  const minScore = await getSettingInt('MIN_INTENT_SCORE', 30);
  const rows = await db.select().from(outreachQueue);

  const companyMap = new Map<string, {
    companyName: string; industry: string; icpMatch: string;
    totalSignals: number; signalTypes: Set<string>; lastSignalDate: string;
    highestScore: number; topSignal: string; topPitchAngle: string; keyServices: string;
  }>();

  for (const row of rows) {
    const name = String(row.companyName || '').trim();
    if (!name) continue;
    const key = name.toLowerCase();

    if (!companyMap.has(key)) {
      companyMap.set(key, {
        companyName: name, industry: row.industry || '', icpMatch: row.icpMatch || '',
        totalSignals: 0, signalTypes: new Set(), lastSignalDate: '',
        highestScore: 0, topSignal: '', topPitchAngle: '', keyServices: '',
      });
    }

    const entry = companyMap.get(key)!;
    entry.totalSignals++;
    if (row.signal) entry.signalTypes.add(row.signal);
    if ((row.intentScore || 0) > entry.highestScore) {
      entry.highestScore = row.intentScore || 0;
      entry.topSignal = row.signal || '';
      entry.topPitchAngle = row.pitchAngle || '';
      entry.keyServices = row.keyServices || '';
    }
    if (row.publishedDate && (!entry.lastSignalDate || row.publishedDate > entry.lastSignalDate)) {
      entry.lastSignalDate = row.publishedDate;
    }
    if (row.industry && !entry.industry) entry.industry = row.industry;
    if (row.icpMatch && !entry.icpMatch) entry.icpMatch = row.icpMatch;
  }

  // Get existing rows to preserve lastOutreachDate and notes
  const existing = await db.select().from(accountIntelligence);
  const existingMap = new Map(existing.map(r => [r.companyName.toLowerCase(), r]));

  for (const [key, agg] of companyMap) {
    const prev = existingMap.get(key);
    const lastOutreachDate = prev?.lastOutreachDate || '';
    const notes = prev?.notes || '';
    const outreachReady = _isOutreachReady(agg.highestScore, minScore, lastOutreachDate) ? 'YES' : 'NO';

    const rowData = {
      companyName: agg.companyName,
      industry: agg.industry,
      icpMatch: agg.icpMatch,
      totalSignals: agg.totalSignals,
      signalTypes: [...agg.signalTypes].join(', '),
      lastSignalDate: agg.lastSignalDate,
      highestScore: agg.highestScore,
      topSignal: agg.topSignal,
      topPitchAngle: agg.topPitchAngle,
      keyServices: agg.keyServices,
      outreachReady,
      lastOutreachDate,
      notes,
    };

    if (prev) {
      await db.update(accountIntelligence).set(rowData).where(eq(accountIntelligence.accountId, prev.accountId)).run();
    } else {
      await db.insert(accountIntelligence).values(rowData).run();
    }
  }
}

function _isOutreachReady(score: number, minScore: number, lastOutreachDate: string): boolean {
  if (score < minScore) return false;
  if (!lastOutreachDate) return true;
  const d = new Date(lastOutreachDate);
  if (isNaN(d.getTime())) return true;
  return (Date.now() - d.getTime()) / (1000 * 60 * 60 * 24) > 30;
}

// ----------------------------------------------------------------
// Helpers
// ----------------------------------------------------------------

async function _getActiveQueries(frequency: string): Promise<SearchQuery[]> {
  const rows = await db.select().from(searchQueries).where(eq(searchQueries.enabled, true));
  return rows
    .filter(r => frequency === 'full' || r.frequency?.toLowerCase() === frequency)
    .map(r => ({
      queryId: r.queryId,
      queryName: r.queryName,
      googleQuery: r.googleQuery,
      categoryId: r.categoryId,
      signalId: r.signalId,
      industryId: r.industryId,
      country: r.country || 'US',
      language: r.language || 'en',
      frequency: r.frequency || 'Daily',
      priority: r.priority || 'Medium',
      enabled: r.enabled ?? true,
      lastRun: r.lastRun || '',
      lastSuccess: r.lastSuccess || '',
      remarks: r.remarks || '',
      sourceType: r.sourceType || 'GoogleNews',
    }));
}

const _industryCache = new Map<number, string>();
async function _getIndustryName(industryId: number | null | undefined): Promise<string> {
  if (!industryId) return '';
  if (_industryCache.has(industryId)) return _industryCache.get(industryId)!;
  const { industries } = await import('@/lib/db/schema');
  const row = await db.select({ industry: industries.industry }).from(industries)
    .where(eq(industries.industryId, industryId)).get();
  const name = row?.industry || '';
  _industryCache.set(industryId, name);
  return name;
}

function _parseDomain(url: string): string {
  try {
    const m = url.match(/^https?:\/\/([^/?#]+)/i);
    return m ? m[1].toLowerCase().replace(/^www\./, '') : '';
  } catch { return ''; }
}

async function _startRun(): Promise<number> {
  const [row] = await db.insert(syncLog).values({
    startTime: isoDateTime(), status: 'Running',
  }).returning({ runId: syncLog.runId });
  return row.runId;
}

async function _endRun(runId: number, fetched: number, inserted: number) {
  await db.update(syncLog).set({
    endTime: isoDateTime(), recordsFetched: fetched, recordsInserted: inserted, status: 'Success',
  }).where(eq(syncLog.runId, runId)).run();
}

export interface PipelineStats {
  fetched: number; newItems: number; processed: number; promoted: number;
}
