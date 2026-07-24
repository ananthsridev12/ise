import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { kbVerticals } from '@/lib/db/schema';
import { eq } from 'drizzle-orm';
import { getSession } from '@/lib/auth/session';

export const dynamic = 'force-dynamic';

export async function GET(req: NextRequest) {
  const session = await getSession(req);
  if (!session) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });

  const rows = await db.select().from(kbVerticals).where(eq(kbVerticals.tenantId, session.tenantId)).all();
  return NextResponse.json(rows);
}

export async function POST(req: NextRequest) {
  const session = await getSession(req);
  if (!session) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  if (session.role !== 'admin') return NextResponse.json({ error: 'Forbidden' }, { status: 403 });

  const body = await req.json();
  const now = new Date().toISOString();
  const result = await db.insert(kbVerticals).values({
    tenantId: session.tenantId,
    name: body.name,
    focus: body.focus || '',
    industries: body.industries || '',
    priority: body.priority || 'core',
    createdAt: now,
  }).returning({ id: kbVerticals.id });

  return NextResponse.json({ ok: true, id: result[0].id });
}
