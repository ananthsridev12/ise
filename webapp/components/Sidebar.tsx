'use client';
import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import { useState, useEffect } from 'react';

const NAV_BASE = [
  { href: '/', label: 'Dashboard', icon: '⬛' },
  { href: '/signals', label: 'Account Intelligence', icon: '⚡' },
  { href: '/outreach', label: 'Outreach Queue', icon: '📤' },
  { href: '/action-queue', label: 'Action Queue', icon: '⚙️' },
];

const NAV_ADMIN = [
  { href: '/knowledge', label: 'Knowledge Hub', icon: '🧠' },
  { href: '/settings', label: 'Settings', icon: '🔧' },
  { href: '/admin/users', label: 'Users', icon: '👥' },
];

const NAV_MEMBER = [
  { href: '/settings', label: 'Settings', icon: '🔧' },
];

interface User { displayName: string; email: string; role: string; }

export default function Sidebar() {
  const pathname = usePathname();
  const router = useRouter();
  const [user, setUser] = useState<User | null>(null);

  useEffect(() => {
    fetch('/api/auth/me').then(r => r.ok ? r.json() : null).then(d => { if (d) setUser(d); });
  }, []);

  async function logout() {
    await fetch('/api/auth/logout', { method: 'POST' });
    router.push('/login');
    router.refresh();
  }

  const adminLinks = user?.role === 'admin' ? NAV_ADMIN : NAV_MEMBER;
  const allLinks = [...NAV_BASE, ...adminLinks];

  return (
    <aside style={{ width: 220, minWidth: 220, background: 'var(--card)', borderRight: '1px solid var(--card-border)', display: 'flex', flexDirection: 'column', padding: '0 0 16px' }}>
      <div style={{ padding: '20px 16px 16px', borderBottom: '1px solid var(--card-border)' }}>
        <div style={{ fontWeight: 700, fontSize: 15, color: 'var(--foreground)', letterSpacing: '-0.01em' }}>⚡ ISE</div>
        <div style={{ fontSize: 11, color: 'var(--muted)', marginTop: 2 }}>Intent Signal Engine</div>
      </div>
      <nav style={{ marginTop: 8, flex: 1 }}>
        {allLinks.map(n => (
          <Link key={n.href} href={n.href}
            style={{
              display: 'flex', alignItems: 'center', gap: 10,
              padding: '9px 16px', fontSize: 13,
              color: pathname === n.href ? 'var(--foreground)' : 'var(--muted)',
              background: pathname === n.href ? 'rgba(99,102,241,0.15)' : 'transparent',
              textDecoration: 'none', borderRadius: 6, margin: '1px 8px',
              transition: 'all 0.15s',
            }}>
            <span style={{ fontSize: 14 }}>{n.icon}</span>
            {n.label}
          </Link>
        ))}
      </nav>
      <div style={{ padding: '12px 16px', borderTop: '1px solid var(--card-border)' }}>
        {user ? (
          <div>
            <div style={{ fontSize: 12, color: 'var(--foreground)', fontWeight: 600, marginBottom: 2, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{user.displayName || user.email}</div>
            <div style={{ fontSize: 11, color: 'var(--muted)', marginBottom: 8, display: 'flex', alignItems: 'center', gap: 6 }}>
              <span style={{ background: user.role === 'admin' ? 'rgba(99,102,241,0.25)' : 'rgba(34,197,94,0.2)', color: user.role === 'admin' ? '#818cf8' : '#4ade80', borderRadius: 4, padding: '1px 6px', fontSize: 10, fontWeight: 600, textTransform: 'uppercase' }}>{user.role}</span>
            </div>
            <button onClick={logout} style={{ fontSize: 11, color: 'var(--muted)', background: 'none', border: '1px solid var(--card-border)', borderRadius: 6, padding: '5px 10px', cursor: 'pointer', width: '100%' }}>
              Sign out
            </button>
          </div>
        ) : (
          <div style={{ fontSize: 11, color: 'var(--muted)' }}>Loading…</div>
        )}
      </div>
    </aside>
  );
}
