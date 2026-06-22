import { db } from '@/lib/db';
import { pitchPlaybook } from '@/lib/db/schema';
import { PitchContext } from './types';

let _cache: Array<{ signal: string; pitchAngle: string; keyServices: string; talkTrack1: string; talkTrack2: string; talkTrack3: string; whyItMatters: string }> | null = null;

export function invalidate() { _cache = null; }

async function ensureLoaded() {
  if (_cache !== null) return;
  const rows = await db.select().from(pitchPlaybook);
  _cache = rows.map(r => ({
    signal: String(r.signal || '').toLowerCase(),
    pitchAngle: r.pitchAngle || '',
    keyServices: r.keyServices || '',
    talkTrack1: r.talkTrack1 || '',
    talkTrack2: r.talkTrack2 || '',
    talkTrack3: r.talkTrack3 || '',
    whyItMatters: r.whyItMatters || '',
  }));
}

export async function lookup(signal: string): Promise<PitchContext> {
  await ensureLoaded();
  const sigLower = signal.toLowerCase();
  const entry = _cache!.find(e => e.signal === sigLower) ||
                _cache!.find(e => sigLower.includes(e.signal) || e.signal.includes(sigLower));

  if (!entry) return { pitchAngle: '', keyServices: '', talkTrack: '', whyItMatters: '' };

  const talkTrack = [entry.talkTrack1, entry.talkTrack2, entry.talkTrack3].filter(Boolean).join('\n\n');
  return { pitchAngle: entry.pitchAngle, keyServices: entry.keyServices, talkTrack, whyItMatters: entry.whyItMatters };
}
