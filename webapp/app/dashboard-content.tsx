'use client';
import { useEffect, useState, useCallback } from 'react';
import PipelineButton from '@/components/PipelineButton';
import Link from 'next/link';

interface DashboardData {
  totalNews: number; todayNews: number;
  highIntent: number; mediumIntent: number; lowIntent: number;
  actionQueueNew: number; actionQueueTotal: number;
  outreachQueueNew: number; outreachQueueTotal: number;
  accountsReady: number; targetAccounts: number;
  topSignal: string; topCategory: string;
  lastSync: string; lastSyncStatus: string;
}

const MetricCard = ({ label, value, sub, accent }: { label: string; value: string | number; sub?: string; accent?: string }) => (
  <div className="card" style={{ padding: '18px 20px', flex: 1, minWidth: 140 }}>
    <div style={{ fontSize: 11, color: 'var(--muted)', textTransform: 'uppercase', letterSpacing: '0.05em', marginBottom: 8 }}>{label}</div>
    <div style={{ fontSize: 28, fontWeight: 700, color: accent || 'var(--foreground)', lineHeight: 1 }}>{value}</div>
    {sub && <div style={{ fontSize: 12, color: 'var(--muted)', marginTop: 6 }}>{sub}</div>}
  </div>
);

export default function DashboardPage() {
  const [data, setData] = useState<DashboardData | null>(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    try {
      const res = await fetch('/api/dashboard');
      const json = await res.json();
      setData(json);
    } catch {}
    setLoading(false);
  }, []);

  useEffect(() => { load(); }, [load]);

  if (loading) return <div style={{ color: 'var(--muted)', fontSize: 14 }}>Loading dashboard...</div>;

  return (
    <div style={{ maxWidth: 1100 }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 24 }}>
        <div>
          <h1 style={{ fontSize: 22, fontWeight: 700, margin: 0 }}>Dashboard</h1>
          <div style={{ fontSize: 13, color: 'var(--muted)', marginTop: 4 }}>
            Last sync: {data?.lastSync || '—'}
            {data?.lastSyncStatus && <span style={{ marginLeft: 8, color: data.lastSyncStatus === 'Success' ? 'var(--success)' : 'var(--warning)' }}>● {data.lastSyncStatus}</span>}
          </div>
        </div>
        <PipelineButton onComplete={load} />
      </div>

      {/* First Run Setup */}
      {data?.totalNews === 0 && (
        <div className="card" style={{ padding: '16px 20px', marginBottom: 24, borderColor: 'var(--accent)', background: 'rgba(99,102,241,0.05)' }}>
          <div style={{ fontWeight: 600, marginBottom: 8 }}>🚀 First-time setup</div>
          <div style={{ fontSize: 13, color: 'var(--muted)', marginBottom: 12 }}>Load the signal pack and pitch playbook, add search queries, then run the pipeline.</div>
          <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
            <SetupButton action="all" label="Load Signal Pack + Pitch Playbook" />
            <Link href="/settings"><button className="btn-secondary">Add Search Queries →</button></Link>
          </div>
        </div>
      )}

      {/* Stats Row 1 */}
      <div style={{ display: 'flex', gap: 12, marginBottom: 12, flexWrap: 'wrap' }}>
        <MetricCard label="Total News" value={data?.totalNews ?? 0} sub={`${data?.todayNews ?? 0} today`} />
        <MetricCard label="High Intent" value={data?.highIntent ?? 0} accent="var(--success)" />
        <MetricCard label="Medium Intent" value={data?.mediumIntent ?? 0} accent="var(--warning)" />
        <MetricCard label="Low Intent" value={data?.lowIntent ?? 0} />
        <MetricCard label="Action Queue" value={data?.actionQueueNew ?? 0} sub={`${data?.actionQueueTotal ?? 0} total`} accent="var(--accent)" />
      </div>

      {/* Stats Row 2 */}
      <div style={{ display: 'flex', gap: 12, marginBottom: 24, flexWrap: 'wrap' }}>
        <MetricCard label="Outreach Queue" value={data?.outreachQueueNew ?? 0} sub={`${data?.outreachQueueTotal ?? 0} total, new`} accent="#818cf8" />
        <MetricCard label="Accounts Ready" value={data?.accountsReady ?? 0} sub="score ≥ threshold" accent="var(--success)" />
        <MetricCard label="Target Accounts" value={data?.targetAccounts ?? 0} />
        <MetricCard label="Top Signal" value={data?.topSignal || '—'} />
        <MetricCard label="Top Category" value={data?.topCategory || '—'} />
      </div>

      {/* Quick links */}
      <div style={{ display: 'flex', gap: 12 }}>
        <Link href="/signals" style={{ textDecoration: 'none', flex: 1 }}>
          <div className="card" style={{ padding: '16px 20px', cursor: 'pointer', transition: 'border-color 0.15s' }}>
            <div style={{ fontWeight: 600, fontSize: 14, marginBottom: 4 }}>⚡ Account Intelligence</div>
            <div style={{ fontSize: 12, color: 'var(--muted)' }}>View all company signals, scores, and pitch angles</div>
          </div>
        </Link>
        <Link href="/outreach" style={{ textDecoration: 'none', flex: 1 }}>
          <div className="card" style={{ padding: '16px 20px', cursor: 'pointer' }}>
            <div style={{ fontWeight: 600, fontSize: 14, marginBottom: 4 }}>📤 Outreach Queue</div>
            <div style={{ fontSize: 12, color: 'var(--muted)' }}>Manage outreach status, contacts, and follow-ups</div>
          </div>
        </Link>
        <Link href="/settings" style={{ textDecoration: 'none', flex: 1 }}>
          <div className="card" style={{ padding: '16px 20px', cursor: 'pointer' }}>
            <div style={{ fontWeight: 600, fontSize: 14, marginBottom: 4 }}>🔧 Settings</div>
            <div style={{ fontSize: 12, color: 'var(--muted)' }}>Configure queries, ICPs, companies, and signal pack</div>
          </div>
        </Link>
      </div>
    </div>
  );
}

function SetupButton({ action, label }: { action: string; label: string }) {
  const [loading, setLoading] = useState(false);
  const [done, setDone] = useState(false);

  async function run() {
    setLoading(true);
    await fetch(`/api/setup?action=${action}`, { method: 'POST' });
    setLoading(false);
    setDone(true);
  }

  return (
    <button className="btn-primary" onClick={run} disabled={loading || done}>
      {done ? '✓ Done' : loading ? 'Loading...' : label}
    </button>
  );
}
