import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { accountIntelligence } from '@/lib/db/schema';
import { eq, desc, gte, like } from 'drizzle-orm';

export const dynamic = 'force-dynamic';

export async function GET(req: NextRequest) {
  try {
    const { searchParams } = new URL(req.url);
    const ready = searchParams.get('ready');
    const minScore = searchParams.get('minScore');
    const signal = searchParams.get('signal');
    const search = searchParams.get('search');

    let query = db.select().from(accountIntelligence).$dynamic();

    if (ready === 'true') query = query.where(eq(accountIntelligence.outreachReady, 'YES'));
    if (minScore) query = query.where(gte(accountIntelligence.highestScore, parseFloat(minScore)));
    if (signal) query = query.where(like(accountIntelligence.signalTypes, `%${signal}%`));
    if (search) query = query.where(like(accountIntelligence.companyName, `%${search}%`));

    const rows = await query.orderBy(desc(accountIntelligence.highestScore));
    return NextResponse.json(rows);
  } catch (e: any) {
    return NextResponse.json({ error: e.message }, { status: 500 });
  }
}
