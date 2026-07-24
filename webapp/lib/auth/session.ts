import { NextRequest } from 'next/server';
import { db } from '@/lib/db';
import { sessions, users, tenants, userVerticals, userServices } from '@/lib/db/schema';
import { eq, and, gt } from 'drizzle-orm';

export interface SessionUser {
  userId: number;
  tenantId: number;
  role: string;
  displayName: string;
  email: string;
}

export async function getSession(req: NextRequest): Promise<SessionUser | null> {
  const token = req.cookies.get('ise_session')?.value;
  if (!token) return null;

  const now = new Date().toISOString();
  const row = await db
    .select({
      token: sessions.token,
      userId: sessions.userId,
      tenantId: sessions.tenantId,
      expiresAt: sessions.expiresAt,
      role: users.role,
      displayName: users.displayName,
      email: users.email,
      userActive: users.active,
      tenantActive: tenants.active,
    })
    .from(sessions)
    .innerJoin(users, eq(sessions.userId, users.id))
    .innerJoin(tenants, eq(sessions.tenantId, tenants.id))
    .where(eq(sessions.token, token))
    .get();

  if (!row || row.expiresAt < now || !row.userActive || !row.tenantActive) return null;

  return {
    userId: row.userId,
    tenantId: row.tenantId,
    role: row.role,
    displayName: row.displayName,
    email: row.email,
  };
}

export async function getUserPermissions(userId: number): Promise<{ verticalIds: number[]; serviceIds: number[] }> {
  const vRows = await db.select({ verticalId: userVerticals.verticalId }).from(userVerticals).where(eq(userVerticals.userId, userId)).all();
  const sRows = await db.select({ serviceId: userServices.serviceId }).from(userServices).where(eq(userServices.userId, userId)).all();
  return {
    verticalIds: vRows.map(r => r.verticalId),
    serviceIds: sRows.map(r => r.serviceId),
  };
}
