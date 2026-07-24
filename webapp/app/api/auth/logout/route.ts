import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { sessions } from '@/lib/db/schema';
import { eq } from 'drizzle-orm';

export const dynamic = 'force-dynamic';

export async function POST(req: NextRequest) {
  const token = req.cookies.get('ise_session')?.value;
  if (token) {
    await db.delete(sessions).where(eq(sessions.token, token));
  }
  const res = NextResponse.json({ ok: true });
  res.cookies.set('ise_session', '', { httpOnly: true, expires: new Date(0), path: '/' });
  return res;
}
