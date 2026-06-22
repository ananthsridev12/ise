import { db } from '@/lib/db';
import { settings } from '@/lib/db/schema';
import { eq } from 'drizzle-orm';

const _cache: Record<string, string> = {};

export async function getSetting(key: string, defaultValue = ''): Promise<string> {
  if (_cache[key] !== undefined) return _cache[key];
  const row = await db.select().from(settings).where(eq(settings.key, key)).get();
  const val = row?.value ?? defaultValue;
  _cache[key] = val;
  return val;
}

export async function getSettingInt(key: string, defaultValue = 0): Promise<number> {
  const val = await getSetting(key, String(defaultValue));
  const n = parseInt(val);
  return isNaN(n) ? defaultValue : n;
}

export function invalidateSettings() {
  Object.keys(_cache).forEach(k => delete _cache[k]);
}

export async function ensureDefaultSettings() {
  const defaults: Record<string, string> = {
    MIN_INTENT_SCORE: '30',
    MAX_NEWS_AGE_DAYS: '7',
    MAX_RESULTS_PER_QUERY: '20',
    LOG_LEVEL: 'INFO',
    ENABLE_DUPLICATE_CHECK: 'TRUE',
    ENABLE_ICP_MATCHING: 'TRUE',
    DEFAULT_COUNTRY: 'US',
    DEFAULT_LANGUAGE: 'en',
    LOG_RETAIN_DAYS: '30',
    ADZUNA_APP_ID: '',
    ADZUNA_APP_KEY: '',
  };

  for (const [key, value] of Object.entries(defaults)) {
    const existing = await db.select().from(settings).where(eq(settings.key, key)).get();
    if (!existing) {
      await db.insert(settings).values({ key, value }).run();
    }
  }
}
