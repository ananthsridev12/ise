import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { users } from '@/lib/db/schema';
import { eq, and } from 'drizzle-orm';
import { getSession } from '@/lib/auth/session';
import bcrypt from 'bcryptjs';

export const dynamic = 'force-dynamic';

export async function PATCH(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const session = await getSession(req);
  if (!session) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  if (session.role !== 'admin') return NextResponse.json({ error: 'Forbidden' }, { status: 403 });

  const { id } = await params;
  const userId = parseInt(id);
  const body = await req.json();

  const updates: Record<string, any> = {};
  if (body.displayName !== undefined) updates.displayName = body.displayName;
  if (body.role !== undefined) updates.role = body.role === 'admin' ? 'admin' : 'member';
  if (body.active !== undefined) updates.active = !!body.active;
  if (body.password) updates.passwordHash = await bcrypt.hash(body.password, 10);

  if (Object.keys(updates).length === 0) return NextResponse.json({ ok: true });

  await db.update(users).set(updates).where(and(eq(users.id, userId), eq(users.tenantId, session.tenantId)));
  return NextResponse.json({ ok: true });
}

export async function DELETE(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const session = await getSession(req);
  if (!session) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  if (session.role !== 'admin') return NextResponse.json({ error: 'Forbidden' }, { status: 403 });

  const { id } = await params;
  const userId = parseInt(id);
  if (userId === session.userId) return NextResponse.json({ error: 'Cannot delete yourself' }, { status: 400 });

  await db.delete(users).where(and(eq(users.id, userId), eq(users.tenantId, session.tenantId)));
  return NextResponse.json({ ok: true });
}
