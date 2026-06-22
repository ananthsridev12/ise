import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { outreachQueue } from '@/lib/db/schema';
import { eq } from 'drizzle-orm';

export async function PATCH(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  try {
    const { id: idStr } = await params;
    const id = parseInt(idStr);
    const body = await req.json();
    const allowed = ['outreachStatus', 'owner', 'contactName', 'contactTitle', 'contactEmail', 'outreachDate', 'followUpDate', 'notes'];
    const updates: Record<string, string> = {};
    for (const key of allowed) {
      if (body[key] !== undefined) updates[key] = body[key];
    }
    await db.update(outreachQueue).set(updates).where(eq(outreachQueue.outreachId, id)).run();
    return NextResponse.json({ ok: true });
  } catch (e: any) {
    return NextResponse.json({ error: e.message }, { status: 500 });
  }
}
