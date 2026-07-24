import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { actionQueue } from '@/lib/db/schema';
import { eq, and, desc } from 'drizzle-orm';
import { getSession } from '@/lib/auth/session';

export const dynamic = 'force-dynamic';

export async function GET(req: NextRequest) {
  const session = await getSession(req);
  if (!session) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });

  const { searchParams } = new URL(req.url);
  const status = searchParams.get('status');

  let query = db.select().from(actionQueue).$dynamic();
  if (status) {
    query = query.where(and(eq(actionQueue.tenantId, session.tenantId), eq(actionQueue.actionStatus, status)));
  } else {
    query = query.where(eq(actionQueue.tenantId, session.tenantId));
  }
  const rows = await query.orderBy(desc(actionQueue.intentScore));
  return NextResponse.json(rows);
}
