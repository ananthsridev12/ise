import { db } from '@/lib/db';
import { signals, categories } from '@/lib/db/schema';
import { KeywordMatch, Classification } from './types';

interface CachedSignal {
  signalId: number; signal: string; categoryId: number | null;
  category: string; defaultScore: number; buyingIntent: boolean;
}

let _cache: CachedSignal[] | null = null;

async function ensureLoaded() {
  if (_cache !== null) return;
  const rows = await db.select({
    signalId: signals.signalId,
    signal: signals.signal,
    categoryId: signals.categoryId,
    defaultScore: signals.defaultScore,
    buyingIntent: signals.buyingIntent,
    active: signals.active,
  }).from(signals);

  const catRows = await db.select().from(categories);
  const catMap = new Map(catRows.map(c => [c.categoryId, c.category]));

  _cache = rows
    .filter(r => r.active)
    .map(r => ({
      signalId: r.signalId,
      signal: r.signal,
      categoryId: r.categoryId,
      category: catMap.get(r.categoryId ?? 0) || '',
      defaultScore: r.defaultScore ?? 50,
      buyingIntent: r.buyingIntent ?? false,
    }));
}

export function invalidate() { _cache = null; }

export async function classify(
  item: { title: string; snippet: string },
  kwMatches: KeywordMatch[],
): Promise<Classification> {
  await ensureLoaded();

  const signalIdCounts = new Map<number, number>();
  for (const kw of kwMatches) {
    if (kw.signalId != null) {
      signalIdCounts.set(kw.signalId, (signalIdCounts.get(kw.signalId) || 0) + kw.weight);
    }
  }

  let bestSignal: CachedSignal | null = null;
  let bestCount = 0;

  for (const [signalId, count] of signalIdCounts) {
    if (count > bestCount) {
      const sig = _cache!.find(s => s.signalId === signalId);
      if (sig) { bestCount = count; bestSignal = sig; }
    }
  }

  if (!bestSignal) {
    bestSignal = _heuristicClassify(item, _cache!);
  }

  if (!bestSignal) {
    return { signalId: null, signal: '', categoryId: null, category: '', signalScore: 0, buyingIntent: false };
  }

  return {
    signalId: bestSignal.signalId,
    signal: bestSignal.signal,
    categoryId: bestSignal.categoryId,
    category: bestSignal.category,
    signalScore: bestSignal.defaultScore,
    buyingIntent: bestSignal.buyingIntent,
  };
}

function _heuristicClassify(item: { title: string; snippet: string }, cache: CachedSignal[]): CachedSignal | null {
  const text = (item.title + ' ' + item.snippet).toLowerCase();
  const patterns: [RegExp, string][] = [
    [/\b(new plant|greenfield|manufacturing facility|production facility)\b/i, 'Greenfield Project'],
    [/\b(erp|sap|oracle|dynamics|epicor|infor)\s*(implementation|rollout|deployment|upgrade)\b/i, 'ERP Implementation'],
    [/\b(factory automation|industrial automation|automated (plant|line|system))\b/i, 'Factory Automation'],
    [/\b(digital transformation|industry 4\.0|smart factory|iiot)\b/i, 'Digital Transformation'],
    [/\b(capex|capacity expansion|expand(ing|s)? (capacity|production))\b/i, 'Capacity Expansion'],
    [/\b(acqui(res?|sition)|merger|takeover|buyout)\b/i, 'Acquisition'],
    [/\b(joint venture|jv with|partnership with)\b/i, 'Joint Venture'],
    [/\b(series [a-e]|seed funding|venture capital|private equity|pe investment|raises? \$)\b/i, 'Funding'],
    [/\b(hiring|recruits?|job openings?|vacancies)\b/i, 'Manufacturing Hiring'],
    [/\b(new contract|wins? contract|awarded contract|order worth)\b/i, 'New Contract'],
    [/\b(warehouse|distribution center|logistics hub)\b/i, 'Warehouse Expansion'],
  ];

  for (const [regex, signalName] of patterns) {
    if (regex.test(text)) {
      const match = cache.find(s => s.signal.toLowerCase() === signalName.toLowerCase());
      if (match) return match;
    }
  }

  return null;
}
