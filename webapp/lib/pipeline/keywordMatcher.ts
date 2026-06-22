import { db } from '@/lib/db';
import { keywords } from '@/lib/db/schema';
import { KeywordMatch } from './types';

let _cache: Array<{
  keywordId: number; keyword: string; signalId: number | null;
  weight: number; matchType: string;
}> | null = null;

async function ensureLoaded() {
  if (_cache !== null) return;
  const rows = await db.select().from(keywords);
  _cache = rows.filter(r => r.active).map(r => ({
    keywordId: r.keywordId,
    keyword: String(r.keyword || '').toLowerCase().trim(),
    signalId: r.signalId,
    weight: r.weight ?? 1,
    matchType: r.matchType || 'contains',
  }));
}

export function invalidate() { _cache = null; }

export async function match(item: { title: string; snippet: string }): Promise<KeywordMatch[]> {
  await ensureLoaded();
  const text = (item.title + ' ' + item.snippet).toLowerCase();
  const hits: KeywordMatch[] = [];

  for (const kw of _cache!) {
    if (!kw.keyword) continue;
    const matched = kw.matchType === 'exact'
      ? new RegExp('\\b' + escapeRegex(kw.keyword) + '\\b').test(text)
      : text.includes(kw.keyword);

    if (matched) {
      hits.push({
        keywordId: kw.keywordId,
        keyword: kw.keyword,
        signalId: kw.signalId,
        weight: kw.weight,
        matchType: kw.matchType,
      });
    }
  }

  return hits;
}

function escapeRegex(str: string) {
  return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}
