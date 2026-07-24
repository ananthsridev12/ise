import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { users } from '@/lib/db/schema';
import { eq, and } from 'drizzle-orm';
import { getSession } from '@/lib/auth/session';
import bcrypt from 'bcryptjs';

export const dynamic = 'force-dynamic';

export async function GET(req: NextRequest) {
  const session = await getSession(req);
  if (!session) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  if (session.role !== 'admin') return NextResponse.json({ error: 'Forbidden' }, { status: 403 });

  const rows = await db.select({
    id: users.id,
    email: users.email,
    displayName: users.displayName,
    role: users.role,
    active: users.active,
    createdAt: users.createdAt,
  }).from(users).where(eq(users.tenantId, session.tenantId)).all();

  return NextResponse.json(rows);
}

export async function POST(req: NextRequest) {
  const session = await getSession(req);
  if (!session) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  if (session.role !== 'admin') return NextResponse.json({ error: 'Forbidden' }, { status: 403 });

  const { email, password, displayName, role } = await req.json();
  if (!email || !password) return NextResponse.json({ error: 'Email and password required' }, { status: 400 });

  const hash = await bcrypt.hash(password, 10);
  const now = new Date().toISOString();
  const result = await db.insert(users).values({
    tenantId: session.tenantId,
    email: email.toLowerCase().trim(),
    passwordHash: hash,
    displayName: displayName || '',
    role: role === 'admin' ? 'admin' : 'member',
    active: true,
    createdAt: now,
  }).returning({ id: users.id });

  return NextResponse.json({ ok: true, id: result[0].id });
}
