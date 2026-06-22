import { db } from '@/lib/db';
import { priorityRules, sources } from '@/lib/db/schema';
import { eq } from 'drizzle-orm';
import { Classification, KeywordMatch, IcpMatch, IntentScoreResult } from './types';
import { ageInDays, parseDate } from './utils';

const WEIGHTS = { signal: 0.30, keyword: 0.25, freshness: 0.20, source: 0.10, icp: 0.15 };

let _rules: Array<{ signal: string; condition: string; adjustment: number }> | null = null;

async function ensureRules() {
  if (_rules !== null) return;
  const rows = await db.select().from(priorityRules);
  _rules = rows.map(r => ({
    signal: String(r.signal || '').toLowerCase(),
    condition: String(r.condition || '').toLowerCase(),
    adjustment: r.adjustment ?? 0,
  }));
}

export async function compute(
  item: { title: string; snippet: string; publishedDate: string; url: string },
  classification: Classification,
  kwMatches: KeywordMatch[],
  icpMatch: IcpMatch | null,
  sourceId: number | null,
): Promise<IntentScoreResult> {
  await ensureRules();

  // 1. Signal score
  let signalScore = classification.signalScore ?? 0;
  if (classification.buyingIntent) signalScore = Math.min(100, signalScore * 1.15);

  // 2. Keyword score — sum of weights, capped at 100
  const kwScore = Math.min(100, kwMatches.reduce((s, k) => s + k.weight * 10, 0));

  // 3. Freshness score
  const freshnessScore = _freshness(item.publishedDate);

  // 4. Source score
  const sourceScore = await _sourceScore(sourceId, item.url);

  // 5. ICP score
  const icpScore = icpMatch ? icpMatch.confidence : 0;

  let final = (signalScore * WEIGHTS.signal) +
               (kwScore * WEIGHTS.keyword) +
               (freshnessScore * WEIGHTS.freshness) +
               (sourceScore * WEIGHTS.source) +
               (icpScore * WEIGHTS.icp);

  // Priority rules adjustments
  const text = (item.title + ' ' + item.snippet).toLowerCase();
  const sigLower = classification.signal.toLowerCase();
  for (const rule of _rules!) {
    if (rule.signal && !sigLower.includes(rule.signal)) continue;
    if (rule.condition && !text.includes(rule.condition)) continue;
    final += rule.adjustment;
  }

  final = Math.max(0, Math.min(100, Math.round(final)));

  const priority = final >= 70 ? 'High' : final >= 40 ? 'Medium' : 'Low';

  return {
    signalScore: Math.round(signalScore),
    keywordScore: Math.round(kwScore),
    freshnessScore: Math.round(freshnessScore),
    sourceScore: Math.round(sourceScore),
    icpScore: Math.round(icpScore),
    finalScore: final,
    priority,
  };
}

function _freshness(publishedDate: string): number {
  if (!publishedDate) return 50;
  const d = parseDate(publishedDate);
  if (!d) return 50;
  const age = ageInDays(d);
  if (age <= 1) return 100;
  if (age <= 2) return 85;
  if (age <= 3) return 70;
  if (age <= 5) return 50;
  if (age <= 7) return 30;
  return 10;
}

async function _sourceScore(sourceId: number | null, url: string): Promise<number> {
  if (sourceId) {
    const src = await db.select({ priority: sources.priority }).from(sources)
      .where(eq(sources.sourceId, sourceId)).get();
    if (src) return _priorityToScore(src.priority || '');
  }
  // Infer from URL
  if (url.includes('prnewswire') || url.includes('businesswire') || url.includes('globenewswire')) return 100;
  if (url.includes('reuters') || url.includes('bloomberg') || url.includes('ft.com')) return 100;
  if (url.includes('industryweek') || url.includes('manufacturing') || url.includes('assembly')) return 60;
  return 50;
}

function _priorityToScore(p: string): number {
  const lower = p.toLowerCase();
  if (lower === 'high') return 100;
  if (lower === 'medium') return 60;
  if (lower === 'low') return 30;
  return 50;
}
