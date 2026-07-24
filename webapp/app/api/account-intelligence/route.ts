import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { accountIntelligence } from '@/lib/db/schema';
import { eq, and, desc, gte, like } from 'drizzle-orm';
import { getSession } from '@/lib/auth/session';

export const dynamic = 'force-dynamic';

export async function GET(req: NextRequest) {
  try {
    const session = await getSession(req);
    if (!session) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });

    const { searchParams } = new URL(req.url);
    const ready = searchParams.get('ready');
    const minScore = searchParams.get('minScore');
    const signal = searchParams.get('signal');
    const search = searchParams.get('search');

    let conditions: any[] = [eq(accountIntelligence.tenantId, session.tenantId)];
    if (ready === 'true') conditions.push(eq(accountIntelligence.outreachReady, 'YES'));
    if (minScore) conditions.push(gte(accountIntelligence.highestScore, parseFloat(minScore)));
    if (signal) conditions.push(like(accountIntelligence.signalTypes, `%${signal}%`));
    if (search) conditions.push(like(accountIntelligence.companyName, `%${search}%`));

    const rows = await db.select().from(accountIntelligence)
      .where(conditions.length === 1 ? conditions[0] : and(...conditions))
      .orderBy(desc(accountIntelligence.highestScore));
    return NextResponse.json(rows);
  } catch (e: any) {
    return NextResponse.json({ error: e.message }, { status: 500 });
  }
}
