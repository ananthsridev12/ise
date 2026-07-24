import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { userVerticals, userServices, users } from '@/lib/db/schema';
import { eq, and, inArray } from 'drizzle-orm';
import { getSession } from '@/lib/auth/session';

export const dynamic = 'force-dynamic';

export async function GET(req: NextRequest) {
  const session = await getSession(req);
  if (!session) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  if (session.role !== 'admin') return NextResponse.json({ error: 'Forbidden' }, { status: 403 });

  const userId = parseInt(req.nextUrl.searchParams.get('user_id') || '0');
  if (!userId) return NextResponse.json({ error: 'user_id required' }, { status: 400 });

  const vRows = await db.select().from(userVerticals).where(eq(userVerticals.userId, userId)).all();
  const sRows = await db.select().from(userServices).where(eq(userServices.userId, userId)).all();

  return NextResponse.json({
    verticalIds: vRows.map(r => r.verticalId),
    serviceIds: sRows.map(r => r.serviceId),
  });
}

export async function POST(req: NextRequest) {
  const session = await getSession(req);
  if (!session) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  if (session.role !== 'admin') return NextResponse.json({ error: 'Forbidden' }, { status: 403 });

  const { userId, verticalIds, serviceIds } = await req.json();

  // Replace all assignments for this user
  await db.delete(userVerticals).where(eq(userVerticals.userId, userId));
  await db.delete(userServices).where(eq(userServices.userId, userId));

  if (verticalIds?.length) {
    await db.insert(userVerticals).values(verticalIds.map((vId: number) => ({ userId, verticalId: vId })));
  }
  if (serviceIds?.length) {
    await db.insert(userServices).values(serviceIds.map((sId: number) => ({ userId, serviceId: sId })));
  }

  return NextResponse.json({ ok: true });
}
