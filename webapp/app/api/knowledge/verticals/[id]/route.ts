import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { kbVerticals } from '@/lib/db/schema';
import { eq, and } from 'drizzle-orm';
import { getSession } from '@/lib/auth/session';

export const dynamic = 'force-dynamic';

export async function PATCH(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const session = await getSession(req);
  if (!session) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  if (session.role !== 'admin') return NextResponse.json({ error: 'Forbidden' }, { status: 403 });

  const { id } = await params;
  const body = await req.json();
  await db.update(kbVerticals).set(body).where(and(eq(kbVerticals.id, parseInt(id)), eq(kbVerticals.tenantId, session.tenantId)));
  return NextResponse.json({ ok: true });
}

export async function DELETE(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const session = await getSession(req);
  if (!session) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  if (session.role !== 'admin') return NextResponse.json({ error: 'Forbidden' }, { status: 403 });

  const { id } = await params;
  await db.delete(kbVerticals).where(and(eq(kbVerticals.id, parseInt(id)), eq(kbVerticals.tenantId, session.tenantId)));
  return NextResponse.json({ ok: true });
}
