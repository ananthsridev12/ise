import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { settings } from '@/lib/db/schema';
import { eq } from 'drizzle-orm';
import { invalidateSettings } from '@/lib/pipeline/settings';

export const dynamic = 'force-dynamic';

export async function GET() {
  const rows = await db.select().from(settings).orderBy(settings.key);
  return NextResponse.json(rows);
}

export async function POST(req: NextRequest) {
  const body = await req.json();
  await db.insert(settings).values({ key: body.key, value: body.value, description: body.description || '' })
    .onConflictDoUpdate({ target: settings.key, set: { value: body.value } }).run();
  invalidateSettings();
  return NextResponse.json({ ok: true });
}
