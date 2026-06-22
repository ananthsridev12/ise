import { db } from '@/lib/db';
import { companies, companyAliases } from '@/lib/db/schema';
import { CompanyMatch } from './types';

interface CachedCompany {
  companyId: number; name: string; industryId: number | null;
  terms: string[]; domain: string;
}

let _cache: CachedCompany[] | null = null;

export function invalidate() { _cache = null; }

async function ensureLoaded() {
  if (_cache !== null) return;
  _cache = [];

  const companyRows = await db.select().from(companies);
  const aliasRows = await db.select().from(companyAliases);

  const aliasMap = new Map<number, string[]>();
  for (const r of aliasRows) {
    const cid = r.companyId;
    if (!aliasMap.has(cid)) aliasMap.set(cid, []);
    const alias = String(r.alias || '').toLowerCase().trim();
    if (alias) aliasMap.get(cid)!.push(alias);
  }

  for (const r of companyRows) {
    if (!r.active) continue;
    const name = String(r.companyName || '').trim();
    if (!name) continue;

    const terms = [name.toLowerCase()];
    const aliases = aliasMap.get(r.companyId) || [];
    terms.push(...aliases);

    const short = name.replace(/\b(Ltd|Limited|Pvt|Private|Inc|Corp|Corporation|Co\.|Company)\b\.?/gi, '').trim();
    if (short && short.toLowerCase() !== name.toLowerCase()) terms.push(short.toLowerCase());

    const domain = String(r.domain || '').toLowerCase()
      .replace(/^https?:\/\//, '').replace(/^www\./, '').replace(/\/.*$/, '');

    _cache.push({ companyId: r.companyId, name, industryId: r.industryId, terms, domain });
  }
}

export async function extract(item: { title: string; snippet: string; url: string }): Promise<CompanyMatch | null> {
  await ensureLoaded();
  const text = (item.title + ' ' + item.snippet).toLowerCase();

  let best: CachedCompany | null = null;
  let bestScore = 0;

  for (const co of _cache!) {
    const score = _score(co, text);
    if (score > bestScore) { bestScore = score; best = co; }
  }

  if (best && bestScore >= 1) {
    return { companyId: best.companyId, companyName: best.name, industryId: best.industryId, confidence: Math.min(bestScore * 20, 100) };
  }

  return _domainMatch(item);
}

function _score(co: CachedCompany, text: string): number {
  let score = 0;
  for (const term of co.terms) {
    if (!term || term.length < 3) continue;
    const re = new RegExp('\\b' + escapeRegex(term) + '\\b', 'i');
    if (re.test(text)) score += term.length > 10 ? 3 : 2;
    else if (text.includes(term)) score += 1;
  }
  return score;
}

function _domainMatch(item: { url: string }): CompanyMatch | null {
  const articleDomain = _parseDomain(item.url);
  if (!articleDomain) return null;
  for (const co of _cache!) {
    if (!co.domain) continue;
    if (articleDomain === co.domain || articleDomain.endsWith('.' + co.domain)) {
      return { companyId: co.companyId, companyName: co.name, industryId: co.industryId, confidence: 80 };
    }
  }
  return null;
}

function _parseDomain(url: string): string {
  try {
    const m = url.match(/^https?:\/\/([^/?#]+)/i);
    return m ? m[1].toLowerCase().replace(/^www\./, '') : '';
  } catch { return ''; }
}

function escapeRegex(str: string) {
  return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

export async function guessCandidate(item: { title: string }): Promise<{ companyName: string; confidence: number; reason: string } | null> {
  const title = String(item.title || '').trim();
  if (!title) return null;

  const cleanTitle = title.split(' - ')[0].trim();
  const patterns = [
    /^(.+?)\s+(announces|opens|launches|expands|invests|raises|acquires|partners|appoints|plans|wins|bags|secures|unveils|builds|starts)\b/i,
    /^(.+?)\s+to\s+(open|launch|expand|invest|acquire|build|hire|set up|establish)\b/i,
    /^(.+?)\s+(gets|receives|lands)\s+(approval|funding|investment|order|contract)\b/i,
  ];

  for (const pattern of patterns) {
    const m = cleanTitle.match(pattern);
    if (!m) continue;
    const name = _cleanName(m[1]);
    if (_isLikelyCompany(name)) {
      return { companyName: name, confidence: _confidence(name, cleanTitle), reason: 'title pattern match' };
    }
  }
  return null;
}

function _cleanName(name: string) {
  return name.trim().replace(/^shares of\s+/i, '').replace(/^stock of\s+/i, '').replace(/[,.;:\-\s]+$/, '').replace(/\s+/g, ' ');
}

function _isLikelyCompany(name: string) {
  if (!name || name.length < 3 || name.length > 80) return false;
  if (name.split(/\s+/).length > 8) return false;
  const blocked = ['the company', 'company', 'government', 'ministry', 'court', 'report', 'analysts', 'stocks', 'shares', 'market', 'industry', 'manufacturing', 'factory', 'plant', 'startup'];
  if (blocked.includes(name.toLowerCase())) return false;
  return /[A-Z]/.test(name) || /\b(inc|corp|corporation|company|co\.|ltd|limited|llc|plc|group|technologies|systems|industries|motors|electric|automation)\b/i.test(name);
}

function _confidence(name: string, title: string): number {
  let c = 55;
  if (/\b(inc|corp|corporation|company|co\.|ltd|limited|llc|plc|group)\b/i.test(name)) c += 15;
  if (title.startsWith(name)) c += 10;
  if (name.split(/\s+/).length >= 2) c += 10;
  return Math.min(90, c);
}
