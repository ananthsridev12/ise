import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { actionQueue } from '@/lib/db/schema';
import { eq, desc } from 'drizzle-orm';

export const dynamic = 'force-dynamic';

export async function GET(req: NextRequest) {
  const { searchParams } = new URL(req.url);
  const status = searchParams.get('status');
  let query = db.select().from(actionQueue).$dynamic();
  if (status) query = query.where(eq(actionQueue.actionStatus, status));
  const rows = await query.orderBy(desc(actionQueue.intentScore));
  return NextResponse.json(rows);
}
