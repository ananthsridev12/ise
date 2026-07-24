import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { rawNews, processedNews, actionQueue, outreachQueue, accountIntelligence, syncLog, targetAccounts } from '@/lib/db/schema';
import { eq, sql, desc } from 'drizzle-orm';
import { todayString } from '@/lib/pipeline/utils';
import { getSession } from '@/lib/auth/session';

export const dynamic = 'force-dynamic';

export async function GET(req: NextRequest) {
  const session = await getSession(req);
  if (!session) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });

  try {
    const today = todayString();

    const [totalNews] = await db.select({ count: sql<number>`count(*)` }).from(rawNews);
    const [todayNews] = await db.select({ count: sql<number>`count(*)` }).from(rawNews)
      .where(sql`date(fetch_time) = ${today}`);
    const [highIntent] = await db.select({ count: sql<number>`count(*)` }).from(processedNews)
      .where(eq(processedNews.priority, 'High'));
    const [medIntent] = await db.select({ count: sql<number>`count(*)` }).from(processedNews)
      .where(eq(processedNews.priority, 'Medium'));
    const [lowIntent] = await db.select({ count: sql<number>`count(*)` }).from(processedNews)
      .where(eq(processedNews.priority, 'Low'));

    const [aqTotal] = await db.select({ count: sql<number>`count(*)` }).from(actionQueue);
    const [aqNew] = await db.select({ count: sql<number>`count(*)` }).from(actionQueue)
      .where(eq(actionQueue.actionStatus, 'New'));
    const [oqTotal] = await db.select({ count: sql<number>`count(*)` }).from(outreachQueue);
    const [oqNew] = await db.select({ count: sql<number>`count(*)` }).from(outreachQueue)
      .where(eq(outreachQueue.outreachStatus, 'New'));
    const [accountsReady] = await db.select({ count: sql<number>`count(*)` }).from(accountIntelligence)
      .where(eq(accountIntelligence.outreachReady, 'YES'));
    const [targetCount] = await db.select({ count: sql<number>`count(*)` }).from(targetAccounts);

    const lastRun = await db.select({ endTime: syncLog.endTime, status: syncLog.status })
      .from(syncLog).orderBy(desc(syncLog.runId)).limit(1).get();

    // Top signal
    const topSignalRow = await db.select({
      signal: processedNews.signal,
      count: sql<number>`count(*) as count`,
    }).from(processedNews)
      .groupBy(processedNews.signal)
      .orderBy(sql`count desc`)
      .limit(1).get();

    const topCategoryRow = await db.select({
      category: processedNews.category,
      count: sql<number>`count(*) as count`,
    }).from(processedNews)
      .groupBy(processedNews.category)
      .orderBy(sql`count desc`)
      .limit(1).get();

    return NextResponse.json({
      totalNews: totalNews.count,
      todayNews: todayNews.count,
      highIntent: highIntent.count,
      mediumIntent: medIntent.count,
      lowIntent: lowIntent.count,
      actionQueueNew: aqNew.count,
      actionQueueTotal: aqTotal.count,
      outreachQueueNew: oqNew.count,
      outreachQueueTotal: oqTotal.count,
      accountsReady: accountsReady.count,
      targetAccounts: targetCount.count,
      topSignal: topSignalRow?.signal || '—',
      topCategory: topCategoryRow?.category || '—',
      lastSync: lastRun?.endTime || '—',
      lastSyncStatus: lastRun?.status || '—',
    });
  } catch (e: any) {
    return NextResponse.json({ error: e.message }, { status: 500 });
  }
}
