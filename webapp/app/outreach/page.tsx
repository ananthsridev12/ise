'use client';
import { useEffect, useState, useCallback } from 'react';

interface OutreachRow {
  outreachId: number; companyName: string; domain: string; industry: string;
  headline: string; signal: string; category: string; intentScore: number;
  icpMatch: string; pitchAngle: string; keyServices: string; talkTrack: string;
  publishedDate: string; url: string; outreachStatus: string; owner: string;
  contactName: string; contactTitle: string; contactEmail: string;
  outreachDate: string; followUpDate: string; notes: string;
}

const STATUS_COLORS: Record<string, string> = {
  New: '#818cf8', Draft: '#f59e0b', Sent: '#3b82f6', Replied: '#10b981',
  Won: '#22c55e', Closed: '#6b7280', Snoozed: '#9ca3af',
};

const STATUSES = ['New', 'Draft', 'Sent', 'Replied', 'Won', 'Closed', 'Snoozed'];

export default function OutreachPage() {
  const [rows, setRows] = useState<OutreachRow[]>([]);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [expanded, setExpanded] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState<number | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const params = new URLSearchParams();
      if (statusFilter) params.set('status', statusFilter);
      if (search) params.set('search', search);
      const res = await fetch('/api/outreach-queue?' + params);
      setRows(await res.json());
    } catch {}
    setLoading(false);
  }, [search, statusFilter]);

  useEffect(() => { load(); }, [load]);

  async function updateRow(id: number, updates: Partial<OutreachRow>) {
    setSaving(id);
    await fetch(`/api/outreach-queue/${id}`, { method: 'PATCH', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(updates) });
    setSaving(null);
    setRows(prev => prev.map(r => r.outreachId === id ? { ...r, ...updates } : r));
  }

  const displayed = rows.filter(r =>
    !search || r.companyName.toLowerCase().includes(search.toLowerCase()) || r.headline.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <div style={{ maxWidth: 1300 }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 20 }}>
        <div>
          <h1 style={{ fontSize: 22, fontWeight: 700, margin: 0 }}>📤 Outreach Queue</h1>
          <div style={{ fontSize: 13, color: 'var(--muted)', marginTop: 4 }}>{displayed.length} items</div>
        </div>
      </div>

      {/* Filters */}
      <div style={{ display: 'flex', gap: 12, marginBottom: 16, alignItems: 'center' }}>
        <input placeholder="Search company or headline..." value={search} onChange={e => setSearch(e.target.value)} style={{ width: 240 }} />
        <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)}>
          <option value="">All statuses</option>
          {STATUSES.map(s => <option key={s} value={s}>{s}</option>)}
        </select>
      </div>

      {/* Status tabs */}
      <div style={{ display: 'flex', gap: 6, marginBottom: 16 }}>
        {[{ v: '', l: 'All' }, ...STATUSES.map(s => ({ v: s, l: s }))].map(({ v, l }) => (
          <button key={v} onClick={() => setStatusFilter(v)}
            style={{
              fontSize: 12, padding: '4px 12px', borderRadius: 9999,
              background: statusFilter === v ? 'rgba(99,102,241,0.2)' : 'var(--card)',
              border: `1px solid ${statusFilter === v ? 'var(--accent)' : 'var(--card-border)'}`,
              color: statusFilter === v ? '#818cf8' : 'var(--muted)',
            }}>
            {l}
          </button>
        ))}
      </div>

      <div className="card" style={{ overflow: 'hidden' }}>
        {loading ? (
          <div style={{ padding: 32, color: 'var(--muted)' }}>Loading...</div>
        ) : displayed.length === 0 ? (
          <div style={{ padding: 48, textAlign: 'center', color: 'var(--muted)' }}>
            <div style={{ fontSize: 32, marginBottom: 12 }}>📤</div>
            <div style={{ fontWeight: 600 }}>No items in outreach queue</div>
            <div style={{ fontSize: 13, marginTop: 4 }}>Run the pipeline to populate.</div>
          </div>
        ) : (
          <div style={{ overflowX: 'auto' }}>
            <table>
              <thead>
                <tr>
                  <th>Company</th>
                  <th>Signal</th>
                  <th>Score</th>
                  <th>ICP</th>
                  <th>Status</th>
                  <th>Owner</th>
                  <th>Contact</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {displayed.map(row => (
                  <>
                    <tr key={row.outreachId}>
                      <td>
                        <div style={{ fontWeight: 600, fontSize: 13 }}>{row.companyName || '—'}</div>
                        <div style={{ fontSize: 11, color: 'var(--muted)', maxWidth: 200, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{row.headline}</div>
                      </td>
                      <td><span className="badge badge-new">{row.signal || '—'}</span></td>
                      <td>
                        <span style={{
                          fontWeight: 700, fontSize: 14,
                          color: row.intentScore >= 70 ? '#10b981' : row.intentScore >= 40 ? '#f59e0b' : '#6b7280',
                        }}>{row.intentScore}</span>
                      </td>
                      <td style={{ fontSize: 12, color: 'var(--muted)' }}>{row.icpMatch || '—'}</td>
                      <td>
                        <select
                          value={row.outreachStatus}
                          onChange={e => updateRow(row.outreachId, { outreachStatus: e.target.value })}
                          style={{ fontSize: 12, padding: '3px 6px', color: STATUS_COLORS[row.outreachStatus] || 'var(--foreground)' }}>
                          {STATUSES.map(s => <option key={s} value={s}>{s}</option>)}
                        </select>
                      </td>
                      <td>
                        <input value={row.owner || ''} placeholder="Assign..."
                          onChange={e => setRows(prev => prev.map(r => r.outreachId === row.outreachId ? { ...r, owner: e.target.value } : r))}
                          onBlur={e => updateRow(row.outreachId, { owner: e.target.value })}
                          style={{ width: 100, fontSize: 12 }} />
                      </td>
                      <td style={{ fontSize: 12 }}>
                        {row.contactEmail ? (
                          <div>
                            <div style={{ fontWeight: 500 }}>{row.contactName}</div>
                            <div style={{ color: 'var(--muted)' }}>{row.contactEmail}</div>
                          </div>
                        ) : <span style={{ color: 'var(--muted)' }}>—</span>}
                      </td>
                      <td style={{ fontSize: 12, color: 'var(--muted)' }}>{row.publishedDate || '—'}</td>
                      <td>
                        <button className="btn-ghost" onClick={() => setExpanded(expanded === row.outreachId ? null : row.outreachId)} style={{ fontSize: 12 }}>
                          {expanded === row.outreachId ? '▲' : '▼'} Details
                        </button>
                      </td>
                    </tr>
                    {expanded === row.outreachId && (
                      <tr key={`${row.outreachId}-detail`}>
                        <td colSpan={9} style={{ background: 'rgba(99,102,241,0.04)', padding: 0 }}>
                          <div style={{ padding: '16px 20px', display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                            <div>
                              <div style={{ fontSize: 11, color: 'var(--muted)', textTransform: 'uppercase', marginBottom: 6 }}>Pitch Angle</div>
                              <div style={{ fontSize: 13, marginBottom: 12 }}>{row.pitchAngle || '—'}</div>
                              <div style={{ fontSize: 11, color: 'var(--muted)', textTransform: 'uppercase', marginBottom: 6 }}>Key Services</div>
                              <div style={{ fontSize: 12, color: 'var(--muted)' }}>{row.keyServices || '—'}</div>
                            </div>
                            <div>
                              <div style={{ fontSize: 11, color: 'var(--muted)', textTransform: 'uppercase', marginBottom: 6 }}>Talk Track</div>
                              <div style={{ fontSize: 12, lineHeight: 1.7, whiteSpace: 'pre-line', color: 'var(--muted)' }}>{row.talkTrack || '—'}</div>
                            </div>
                            <div>
                              <div style={{ fontSize: 11, color: 'var(--muted)', textTransform: 'uppercase', marginBottom: 8 }}>Contact Details</div>
                              <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                                <input placeholder="Contact name" value={row.contactName || ''}
                                  onChange={e => setRows(prev => prev.map(r => r.outreachId === row.outreachId ? { ...r, contactName: e.target.value } : r))}
                                  onBlur={e => updateRow(row.outreachId, { contactName: e.target.value })} style={{ width: 160 }} />
                                <input placeholder="Title" value={row.contactTitle || ''}
                                  onChange={e => setRows(prev => prev.map(r => r.outreachId === row.outreachId ? { ...r, contactTitle: e.target.value } : r))}
                                  onBlur={e => updateRow(row.outreachId, { contactTitle: e.target.value })} style={{ width: 140 }} />
                                <input placeholder="Email" value={row.contactEmail || ''}
                                  onChange={e => setRows(prev => prev.map(r => r.outreachId === row.outreachId ? { ...r, contactEmail: e.target.value } : r))}
                                  onBlur={e => updateRow(row.outreachId, { contactEmail: e.target.value })} style={{ width: 200 }} />
                              </div>
                            </div>
                            <div>
                              <div style={{ fontSize: 11, color: 'var(--muted)', textTransform: 'uppercase', marginBottom: 8 }}>Notes</div>
                              <textarea value={row.notes || ''} rows={3}
                                onChange={e => setRows(prev => prev.map(r => r.outreachId === row.outreachId ? { ...r, notes: e.target.value } : r))}
                                onBlur={e => updateRow(row.outreachId, { notes: e.target.value })}
                                style={{ width: '100%', resize: 'vertical' }} placeholder="Notes..." />
                            </div>
                          </div>
                          <div style={{ padding: '0 20px 16px' }}>
                            {row.url && <a href={row.url} target="_blank" rel="noopener noreferrer"
                              style={{ fontSize: 12, color: 'var(--accent)', textDecoration: 'none' }}>
                              🔗 View source article →
                            </a>}
                          </div>
                        </td>
                      </tr>
                    )}
                  </>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
