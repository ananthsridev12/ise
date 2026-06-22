import { sleep } from './utils';
import { SearchQuery } from './types';
import { getSetting } from './settings';

const GOOGLE_NEWS_BASE = 'https://news.google.com/rss/search?q=';
const MAX_RETRIES = 3;
const RETRY_DELAY_MS = 2000;

export function buildRssUrl(query: SearchQuery): string {
  const sourceType = (query.sourceType || 'GoogleNews').trim();

  if (sourceType === 'RSS' || sourceType === 'Indeed' || sourceType === 'Adzuna') {
    return query.googleQuery;
  }

  const country = (query.country || 'US').toUpperCase();
  const lang = (query.language || 'en').toLowerCase();
  const encoded = encodeURIComponent(query.googleQuery);
  return `${GOOGLE_NEWS_BASE}${encoded}&hl=${lang}&gl=${country}&ceid=${country}:${lang}`;
}

export async function fetchRss(query: SearchQuery): Promise<string | null> {
  const url = buildRssUrl(query);
  let lastError: Error | null = null;

  for (let attempt = 1; attempt <= MAX_RETRIES; attempt++) {
    try {
      const res = await fetch(url, {
        headers: {
          'User-Agent': 'Mozilla/5.0 (compatible; ISE/2.0)',
          'Accept': 'application/rss+xml, application/xml, text/xml',
        },
        signal: AbortSignal.timeout(15000),
      });

      if (res.status === 429 || res.status === 503) {
        await sleep(RETRY_DELAY_MS * attempt);
        continue;
      }

      if (!res.ok) {
        throw new Error(`HTTP ${res.status} for ${url}`);
      }

      return await res.text();
    } catch (e) {
      lastError = e as Error;
      if (attempt < MAX_RETRIES) await sleep(RETRY_DELAY_MS * attempt);
    }
  }

  console.error(`NewsFetcher: failed after ${MAX_RETRIES} retries: ${lastError?.message}`);
  return null;
}

// ----------------------------------------------------------------
// Indeed RSS
// ----------------------------------------------------------------
export function buildIndeedUrl(q: string, country = 'US'): string {
  const loc = country === 'IN' ? 'India' : country === 'AU' ? 'Australia' :
              country === 'GB' ? 'United+Kingdom' : 'United+States';
  return `https://www.indeed.com/rss?q=${encodeURIComponent(q)}&l=${loc}&sort=date`;
}

// ----------------------------------------------------------------
// Adzuna REST API  (free tier: 100 req/day)
// https://developer.adzuna.com/
// Returns JSON, so we return parsed items directly
// ----------------------------------------------------------------
export async function fetchAdzuna(
  keywords: string,
  country = 'us',
  appId: string,
  appKey: string,
  page = 1,
  resultsPerPage = 20,
): Promise<AdzunaJob[]> {
  if (!appId || !appKey) return [];

  const countryCode = country.toLowerCase().slice(0, 2);
  const url = `https://api.adzuna.com/v1/api/jobs/${countryCode}/search/${page}` +
    `?app_id=${appId}&app_key=${appKey}` +
    `&results_per_page=${resultsPerPage}` +
    `&what=${encodeURIComponent(keywords)}` +
    `&sort_by=date&content-type=application/json`;

  try {
    const res = await fetch(url, { signal: AbortSignal.timeout(15000) });
    if (!res.ok) return [];
    const json = await res.json() as { results?: AdzunaJob[] };
    return json.results || [];
  } catch {
    return [];
  }
}

export interface AdzunaJob {
  id: string;
  title: string;
  description: string;
  redirect_url: string;
  created: string;
  company?: { display_name?: string };
  location?: { display_name?: string };
  salary_min?: number;
  salary_max?: number;
}

export async function fetchAll(queries: SearchQuery[]): Promise<{ query: SearchQuery; xml: string | null }[]> {
  const results = [];
  for (const q of queries) {
    const xml = await fetchRss(q);
    results.push({ query: q, xml });
    await sleep(500);
  }
  return results;
}
