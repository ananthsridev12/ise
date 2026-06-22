import { db } from '@/lib/db';
import { duplicateIndex } from '@/lib/db/schema';
import { inArray } from 'drizzle-orm';
import { RawNewsItem } from './types';
import { isoDateTime } from './utils';

export async function filterNew(items: RawNewsItem[]): Promise<RawNewsItem[]> {
  if (!items.length) return [];

  const hashes = items.map(i => i.hash);
  const existing = await db.select({ hash: duplicateIndex.hash })
    .from(duplicateIndex)
    .where(inArray(duplicateIndex.hash, hashes));

  const existingSet = new Set(existing.map(r => r.hash));

  // Also deduplicate within the batch itself
  const seen = new Set<string>();
  return items.filter(item => {
    if (existingSet.has(item.hash) || seen.has(item.hash)) return false;
    seen.add(item.hash);
    return true;
  });
}

export async function register(newsId: number, hash: string, url: string) {
  await db.insert(duplicateIndex)
    .values({ hash, newsId, url, created: isoDateTime() })
    .onConflictDoNothing()
    .run();
}
