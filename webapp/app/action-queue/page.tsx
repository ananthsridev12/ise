'use client';
import { useEffect, useState, useCallback } from 'react';

interface ActionRow {
  queueId: number; newsId: number; company: string; headline: string;
  signal: string; category: string; intentScore: number; icp: string;
  publishedDate: string; url: string; actionStatus: string; owner: string; notes: string;
}

export default function ActionQueuePage() {
  const [rows, setRows] = useState<ActionRow[]>([]);
  const [filter, setFilter] = useState('');
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const res = await fetch('/api/action-queue' + (filter ? `?status=${filter}` : ''));
      setRows(await res.json());
    } catch {}
    setLoading(false);
  }, [filter]);

  useEffect(() => { load(); }, [load]);

  return (
    <div style={{ maxWidth: 1100 }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 20 }}>
        <div>
          <h1 style={{ fontSize: 22, fontWeight: 700, margin: 0 }}>⚙️ Action Queue</h1>
          <div style={{ fontSize: 13, color: 'var(--muted)', marginTop: 4 }}>{rows.length} items</div>
        </div>
        <div style={{ display: 'flex', gap: 8 }}>
          {['', 'New', 'Completed'].map(s => (
            <button key={s} onClick={() => setFilter(s)}
              style={{ fontSize: 12, padding: '4px 12px', borderRadius: 9999,
                background: filter === s ? 'rgba(99,102,241,0.2)' : 'var(--card)',
                border: `1px solid ${filter === s ? 'var(--accent)' : 'var(--card-border)'}`,
                color: filter === s ? '#818cf8' : 'var(--muted)' }}>
              {s || 'All'}
            </button>
          ))}
        </div>
      </div>

      <div className="card" style={{ overflow: 'hidden' }}>
        {loading ? <div style={{ padding: 32, color: 'var(--muted)' }}>Loading...</div> :
        rows.length === 0 ? (
          <div style={{ padding: 48, textAlign: 'center', color: 'var(--muted)' }}>
            <div style={{ fontSize: 32, marginBottom: 12 }}>⚙️</div>
            <div style={{ fontWeight: 600 }}>No action items</div>
            <div style={{ fontSize: 13 }}>Run the pipeline to generate action queue items.</div>
          </div>
        ) : (
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Company</th>
                <th>Headline</th>
                <th>Signal</th>
                <th>Score</th>
                <th>ICP</th>
                <th>Date</th>
                <th>Status</th>
                <th>Source</th>
              </tr>
            </thead>
            <tbody>
              {rows.map(row => (
                <tr key={row.queueId}>
                  <td style={{ color: 'var(--muted)', fontSize: 12 }}>{row.queueId}</td>
                  <td style={{ fontWeight: 600, fontSize: 13 }}>{row.company || '—'}</td>
                  <td style={{ maxWidth: 300 }}>
                    <div style={{ fontSize: 12, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', maxWidth: 280 }}>
                      {row.headline}
                    </div>
                  </td>
                  <td><span className="badge badge-new">{row.signal || '—'}</span></td>
                  <td style={{ fontWeight: 700, color: row.intentScore >= 70 ? '#10b981' : row.intentScore >= 40 ? '#f59e0b' : '#6b7280' }}>
                    {row.intentScore}
                  </td>
                  <td style={{ fontSize: 12, color: 'var(--muted)' }}>{row.icp || '—'}</td>
                  <td style={{ fontSize: 12, color: 'var(--muted)' }}>{row.publishedDate || '—'}</td>
                  <td>
                    <span className={`badge ${row.actionStatus === 'New' ? 'badge-new' : 'badge-low'}`}>
                      {row.actionStatus}
                    </span>
                  </td>
                  <td>
                    {row.url && <a href={row.url} target="_blank" rel="noopener noreferrer"
                      style={{ fontSize: 12, color: 'var(--accent)', textDecoration: 'none' }}>↗</a>}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
}
