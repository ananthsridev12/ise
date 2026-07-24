import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { users, sessions, tenants } from '@/lib/db/schema';
import { eq, and } from 'drizzle-orm';
import bcrypt from 'bcryptjs';
import crypto from 'crypto';

export const dynamic = 'force-dynamic';

export async function POST(req: NextRequest) {
  try {
    const { email, password } = await req.json();
    if (!email || !password) return NextResponse.json({ error: 'Email and password required' }, { status: 400 });

    const user = await db.select().from(users).where(eq(users.email, email.toLowerCase().trim())).get();
    if (!user || !user.active) return NextResponse.json({ error: 'Invalid credentials' }, { status: 401 });

    const valid = await bcrypt.compare(password, user.passwordHash);
    if (!valid) return NextResponse.json({ error: 'Invalid credentials' }, { status: 401 });

    const tenant = await db.select().from(tenants).where(eq(tenants.id, user.tenantId)).get();
    if (!tenant || !tenant.active) return NextResponse.json({ error: 'Account inactive' }, { status: 403 });

    const token = crypto.randomBytes(32).toString('hex');
    const expiresAt = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString();
    const now = new Date().toISOString();

    await db.insert(sessions).values({ token, userId: user.id, tenantId: user.tenantId, expiresAt, createdAt: now });

    const res = NextResponse.json({
      ok: true,
      user: { id: user.id, email: user.email, displayName: user.displayName, role: user.role, tenantId: user.tenantId },
    });
    res.cookies.set('ise_session', token, {
      httpOnly: true,
      secure: process.env.NODE_ENV === 'production',
      sameSite: 'lax',
      expires: new Date(expiresAt),
      path: '/',
    });
    return res;
  } catch (e: any) {
    return NextResponse.json({ error: e.message }, { status: 500 });
  }
}
