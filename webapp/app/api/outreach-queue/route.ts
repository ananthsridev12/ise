import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { outreachQueue } from '@/lib/db/schema';
import { eq, desc, like } from 'drizzle-orm';

export const dynamic = 'force-dynamic';

export async function GET(req: NextRequest) {
  try {
    const { searchParams } = new URL(req.url);
    const status = searchParams.get('status');
    const search = searchParams.get('search');

    let query = db.select().from(outreachQueue).$dynamic();
    if (status) query = query.where(eq(outreachQueue.outreachStatus, status));
    if (search) query = query.where(like(outreachQueue.companyName, `%${search}%`));

    const rows = await query.orderBy(desc(outreachQueue.intentScore));
    return NextResponse.json(rows);
  } catch (e: any) {
    return NextResponse.json({ error: e.message }, { status: 500 });
  }
}
