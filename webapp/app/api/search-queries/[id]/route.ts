import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { searchQueries } from '@/lib/db/schema';
import { eq } from 'drizzle-orm';

export async function PATCH(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: idStr } = await params;
  const id = parseInt(idStr);
  const body = await req.json();
  await db.update(searchQueries).set(body).where(eq(searchQueries.queryId, id)).run();
  return NextResponse.json({ ok: true });
}

export async function DELETE(_req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: idStr } = await params;
  const id = parseInt(idStr);
  await db.delete(searchQueries).where(eq(searchQueries.queryId, id)).run();
  return NextResponse.json({ ok: true });
}
