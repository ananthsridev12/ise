import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { kbServices } from '@/lib/db/schema';
import { eq } from 'drizzle-orm';
import { getSession } from '@/lib/auth/session';

export const dynamic = 'force-dynamic';

export async function GET(req: NextRequest) {
  const session = await getSession(req);
  if (!session) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });

  const rows = await db.select().from(kbServices).where(eq(kbServices.tenantId, session.tenantId)).all();
  return NextResponse.json(rows);
}

export async function POST(req: NextRequest) {
  const session = await getSession(req);
  if (!session) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  if (session.role !== 'admin') return NextResponse.json({ error: 'Forbidden' }, { status: 403 });

  const body = await req.json();
  const now = new Date().toISOString();
  const result = await db.insert(kbServices).values({
    tenantId: session.tenantId,
    verticalId: body.verticalId || null,
    name: body.name,
    description: body.description || '',
    signalKeywords: body.signalKeywords || '',
    signalTypes: body.signalTypes || '',
    techTriggers: body.techTriggers || '',
    createdAt: now,
  }).returning({ id: kbServices.id });

  return NextResponse.json({ ok: true, id: result[0].id });
}
