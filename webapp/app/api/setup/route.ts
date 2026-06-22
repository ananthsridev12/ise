import { NextRequest, NextResponse } from 'next/server';
import { seedSignalPack } from '@/lib/seed/signalPack';
import { seedPitchPlaybook } from '@/lib/seed/pitchPlaybook';
import { ensureDefaultSettings } from '@/lib/pipeline/settings';

export async function POST(req: NextRequest) {
  const { searchParams } = new URL(req.url);
  const action = searchParams.get('action');

  try {
    await ensureDefaultSettings();

    if (action === 'signal-pack') {
      const result = await seedSignalPack();
      return NextResponse.json({ ok: true, result });
    }

    if (action === 'pitch-playbook') {
      const count = await seedPitchPlaybook();
      return NextResponse.json({ ok: true, inserted: count });
    }

    if (action === 'all') {
      const signals = await seedSignalPack();
      const pitches = await seedPitchPlaybook();
      return NextResponse.json({ ok: true, signals, pitches });
    }

    return NextResponse.json({ error: 'Unknown action' }, { status: 400 });
  } catch (e: any) {
    return NextResponse.json({ error: e.message }, { status: 500 });
  }
}
