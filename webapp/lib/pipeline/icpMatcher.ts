import { db } from '@/lib/db';
import { icpMaster } from '@/lib/db/schema';
import { IcpMatch, Classification, CompanyMatch } from './types';

interface CachedIcp {
  icpId: number; vertical: string; icpCode: string; segment: string;
  subSegment: string; productService: string; primaryServicesToPitch: string;
  category: string; tier: string; geo: string; icpName: string; matchText: string;
}

let _cache: CachedIcp[] | null = null;

export function invalidate() { _cache = null; }

async function ensureLoaded() {
  if (_cache !== null) return;
  const rows = await db.select().from(icpMaster);
  _cache = rows.map(r => {
    const icpName = r.segment || r.icpCode || '';
    const matchText = [r.vertical, r.segment, r.subSegment, r.productService, r.primaryServicesToPitch, r.category].join(' ').toLowerCase();
    return { icpId: r.icpId, vertical: r.vertical || '', icpCode: r.icpCode || '', segment: r.segment || '', subSegment: r.subSegment || '', productService: r.productService || '', primaryServicesToPitch: r.primaryServicesToPitch || '', category: r.category || '', tier: (r.tier || '').toLowerCase(), geo: (r.geo || '').toLowerCase(), icpName, matchText };
  });
}

export async function matchIcp(
  item: { title: string; snippet: string; url: string },
  coMatch: CompanyMatch | null,
  classification: Classification,
): Promise<IcpMatch | null> {
  await ensureLoaded();

  let best: { icp: CachedIcp; score: number; reason: string } | null = null;

  for (const icp of _cache!) {
    const { score, reason } = _scoreIcp(item, coMatch, classification, icp);
    if (score > (best?.score ?? 0)) best = { icp, score, reason };
  }

  if (!best || best.score < 35) return null;

  return {
    icpId: best.icp.icpId,
    icpCode: best.icp.icpCode,
    icpName: best.icp.icpName,
    confidence: Math.min(best.score, 100),
    reason: best.reason,
  };
}

function _scoreIcp(
  item: { title: string; snippet: string },
  coMatch: CompanyMatch | null,
  classification: Classification,
  icp: CachedIcp,
): { score: number; reason: string } {
  const text = (item.title + ' ' + item.snippet).toLowerCase();
  let score = 0;
  const reasons: string[] = [];

  // Company match
  if (coMatch) { score += 15; reasons.push('company match'); }

  // Signal match
  const sigLower = classification.signal.toLowerCase();
  if (sigLower && icp.matchText.includes(sigLower)) { score += 25; reasons.push('signal match'); }

  // Product/service keywords
  const prodTerms = icp.productService.toLowerCase().split(/[,;/]+/).map(t => t.trim()).filter(Boolean);
  let prodHits = 0;
  for (const t of prodTerms) {
    if (t.length > 3 && text.includes(t)) prodHits++;
  }
  if (prodHits > 0) { score += Math.min(30, prodHits * 10); reasons.push('product match'); }

  // Segment/category keywords
  const segTerms = [icp.segment, icp.subSegment, icp.category].join(' ').toLowerCase().split(/\s+/).filter(t => t.length > 4);
  let segHits = 0;
  for (const t of segTerms) {
    if (text.includes(t)) segHits++;
  }
  if (segHits > 0) { score += Math.min(25, segHits * 10); reasons.push('segment match'); }

  // Core match required for geo/tier bonuses
  const coreMatch = score >= 25;

  if (coreMatch) {
    if (icp.geo && _geoMatches(icp.geo, text)) { score += 20; reasons.push('geo match'); }
    if (icp.tier === 'large') { score += 10; reasons.push('large tier'); }
  }

  if (!coreMatch) return { score: 0, reason: '' };
  return { score, reason: reasons.join(', ') };
}

function _geoMatches(geo: string, text: string): boolean {
  const geoMap: Record<string, string[]> = {
    'us': ['united states', 'u.s.', 'usa', 'american', 'north america'],
    'in': ['india', 'indian', 'mumbai', 'delhi', 'bangalore', 'chennai', 'pune', 'hyderabad', 'gujarat'],
    'eu': ['europe', 'european', 'germany', 'france', 'italy', 'spain', 'netherlands', 'poland'],
    'uk': ['united kingdom', 'britain', 'british', 'england', 'uk'],
    'au': ['australia', 'australian', 'sydney', 'melbourne', 'brisbane'],
  };
  const terms = geoMap[geo] || [geo];
  return terms.some(t => text.includes(t));
}
