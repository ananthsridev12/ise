import { parseStringPromise } from 'xml2js';
import { md5Hash, isoDateTime, stripHtml } from './utils';
import { SearchQuery, RawNewsItem } from './types';

export async function parse(xml: string, query: SearchQuery): Promise<RawNewsItem[]> {
  if (!xml) return [];

  let parsed: any;
  try {
    parsed = await parseStringPromise(xml, { explicitArray: false, ignoreAttrs: false });
  } catch {
    return [];
  }

  const channel = parsed?.rss?.channel || parsed?.feed;
  if (!channel) return [];

  const rawItems: any[] = Array.isArray(channel.item)
    ? channel.item
    : channel.item
    ? [channel.item]
    : Array.isArray(channel.entry)
    ? channel.entry
    : channel.entry
    ? [channel.entry]
    : [];

  const now = isoDateTime();
  const results: RawNewsItem[] = [];

  for (const it of rawItems) {
    const title = stripHtml(String(it.title?._ || it.title || '')).trim();
    const link = String(it.link?.$ ? it.link.$.href : it.link || it.guid?._ || it.guid || '').trim();
    const snippet = stripHtml(String(it.description || it.summary?._ || it.summary || '')).trim();

    let pubDate = '';
    const rawDate = it.pubDate || it.published || it.updated || '';
    if (rawDate) {
      const d = new Date(String(rawDate));
      if (!isNaN(d.getTime())) pubDate = d.toISOString().split('T')[0];
    }

    if (!title || !link) continue;

    const hash = md5Hash(link + title);

    results.push({
      fetchTime: now,
      publishedDate: pubDate,
      title,
      snippet: snippet.substring(0, 500),
      url: link,
      sourceId: null,
      queryId: query.queryId,
      hash,
      processed: false,
    });
  }

  return results;
}

export function adzunaJobToRawItem(job: import('./newsFetcher').AdzunaJob, queryId: number): RawNewsItem {
  const company = job.company?.display_name || '';
  const location = job.location?.display_name || '';
  const title = `${company} hiring: ${job.title}${location ? ' in ' + location : ''}`;
  const snippet = stripHtml(job.description || '').substring(0, 500);
  const url = job.redirect_url;
  const pubDate = job.created ? new Date(job.created).toISOString().split('T')[0] : '';

  return {
    fetchTime: isoDateTime(),
    publishedDate: pubDate,
    title,
    snippet,
    url,
    sourceId: null,
    queryId,
    hash: md5Hash(url + title),
    processed: false,
  };
}
