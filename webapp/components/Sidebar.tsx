'use client';
import Link from 'next/link';
import { usePathname } from 'next/navigation';

const NAV = [
  { href: '/', label: 'Dashboard', icon: '⬛' },
  { href: '/signals', label: 'Account Intelligence', icon: '⚡' },
  { href: '/outreach', label: 'Outreach Queue', icon: '📤' },
  { href: '/action-queue', label: 'Action Queue', icon: '⚙️' },
  { href: '/settings', label: 'Settings', icon: '🔧' },
];

export default function Sidebar() {
  const pathname = usePathname();
  return (
    <aside style={{ width: 220, minWidth: 220, background: 'var(--card)', borderRight: '1px solid var(--card-border)', display: 'flex', flexDirection: 'column', padding: '0 0 16px' }}>
      <div style={{ padding: '20px 16px 16px', borderBottom: '1px solid var(--card-border)' }}>
        <div style={{ fontWeight: 700, fontSize: 15, color: 'var(--foreground)', letterSpacing: '-0.01em' }}>⚡ ISE</div>
        <div style={{ fontSize: 11, color: 'var(--muted)', marginTop: 2 }}>Intent Signal Engine</div>
      </div>
      <nav style={{ marginTop: 8, flex: 1 }}>
        {NAV.map(n => (
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
        <div style={{ fontSize: 11, color: 'var(--muted)' }}>SolidPro Engineering</div>
      </div>
    </aside>
  );
}
