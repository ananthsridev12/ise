import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { searchQueries } from '@/lib/db/schema';
import { eq } from 'drizzle-orm';

export const dynamic = 'force-dynamic';

export async function GET() {
  const rows = await db.select().from(searchQueries).orderBy(searchQueries.queryId);
  return NextResponse.json(rows);
}

export async function POST(req: NextRequest) {
  const body = await req.json();
  const [row] = await db.insert(searchQueries).values({
    queryName: body.queryName,
    googleQuery: body.googleQuery,
    country: body.country || 'US',
    language: body.language || 'en',
    frequency: body.frequency || 'Daily',
    priority: body.priority || 'Medium',
    enabled: body.enabled ?? true,
    sourceType: body.sourceType || 'GoogleNews',
    remarks: body.remarks || '',
  }).returning();
  return NextResponse.json(row, { status: 201 });
}
