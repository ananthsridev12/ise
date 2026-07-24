import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { outreachQueue } from '@/lib/db/schema';
import { eq, and, desc, like } from 'drizzle-orm';
import { getSession } from '@/lib/auth/session';

export const dynamic = 'force-dynamic';

export async function GET(req: NextRequest) {
  try {
    const session = await getSession(req);
    if (!session) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });

    const { searchParams } = new URL(req.url);
    const status = searchParams.get('status');
    const search = searchParams.get('search');

    let query = db.select().from(outreachQueue).$dynamic();
    query = query.where(eq(outreachQueue.tenantId, session.tenantId));
    if (status) query = query.where(and(eq(outreachQueue.tenantId, session.tenantId), eq(outreachQueue.outreachStatus, status)));
    if (search) query = query.where(and(eq(outreachQueue.tenantId, session.tenantId), like(outreachQueue.companyName, `%${search}%`)));

    const rows = await query.orderBy(desc(outreachQueue.intentScore));
    return NextResponse.json(rows);
  } catch (e: any) {
    return NextResponse.json({ error: e.message }, { status: 500 });
  }
}
