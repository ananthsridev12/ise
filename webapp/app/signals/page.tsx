'use client';
import { useEffect, useState, useCallback } from 'react';
import PipelineButton from '@/components/PipelineButton';

interface AccountRow {
  accountId: number; companyName: string; industry: string; icpMatch: string;
  totalSignals: number; signalTypes: string; lastSignalDate: string;
  highestScore: number; topSignal: string; topPitchAngle: string; keyServices: string;
  outreachReady: string; lastOutreachDate: string; notes: string;
}

function ScoreBar({ score }: { score: number }) {
  const color = score >= 70 ? '#10b981' : score >= 40 ? '#f59e0b' : '#6b7280';
  return (
    <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
      <div style={{ width: 60, height: 4, background: 'var(--card-border)', borderRadius: 2 }}>
        <div style={{ width: `${score}%`, height: '100%', borderRadius: 2, background: color }} />
      </div>
      <span style={{ fontSize: 13, fontWeight: 600, color }}>{score}</span>
    </div>
  );
}

export default function SignalsPage() {
  const [rows, setRows] = useState<AccountRow[]>([]);
  const [filtered, setFiltered] = useState<AccountRow[]>([]);
  const [search, setSearch] = useState('');
  const [readyOnly, setReadyOnly] = useState(false);
  const [minScore, setMinScore] = useState(0);
  const [expanded, setExpanded] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const res = await fetch('/api/account-intelligence');
      setRows(await res.json());
    } catch {}
    setLoading(false);
  }, []);

  useEffect(() => { load(); }, [load]);

  useEffect(() => {
    let r = rows;
    if (readyOnly) r = r.filter(x => x.outreachReady === 'YES');
    if (minScore > 0) r = r.filter(x => x.highestScore >= minScore);
    if (search) r = r.filter(x => x.companyName.toLowerCase().includes(search.toLowerCase()) || x.signalTypes.toLowerCase().includes(search.toLowerCase()));
    setFiltered(r);
  }, [rows, search, readyOnly, minScore]);

  return (
    <div style={{ maxWidth: 1300 }}>
      {/* Header */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 20 }}>
        <div>
          <h1 style={{ fontSize: 22, fontWeight: 700, margin: 0 }}>⚡ Account Intelligence</h1>
          <div style={{ fontSize: 13, color: 'var(--muted)', marginTop: 4 }}>{filtered.length} companies · ranked by intent score</div>
        </div>
        <PipelineButton onComplete={load} />
      </div>

      {/* Filters */}
      <div style={{ display: 'flex', gap: 12, marginBottom: 16, flexWrap: 'wrap', alignItems: 'center' }}>
        <input placeholder="Search company or signal..." value={search} onChange={e => setSearch(e.target.value)}
          style={{ width: 240 }} />
        <label style={{ fontSize: 13, display: 'flex', alignItems: 'center', gap: 6, color: 'var(--muted)', cursor: 'pointer' }}>
          <input type="checkbox" checked={readyOnly} onChange={e => setReadyOnly(e.target.checked)} />
          Outreach Ready only
        </label>
        <label style={{ fontSize: 13, display: 'flex', alignItems: 'center', gap: 6, color: 'var(--muted)' }}>
          Min score:
          <input type="number" value={minScore} onChange={e => setMinScore(Number(e.target.value))} style={{ width: 60 }} min={0} max={100} />
        </label>
      </div>

      {/* Table */}
      <div className="card" style={{ overflow: 'hidden' }}>
        {loading ? (
          <div style={{ padding: 32, color: 'var(--muted)', fontSize: 14 }}>Loading...</div>
        ) : filtered.length === 0 ? (
          <div style={{ padding: 48, textAlign: 'center', color: 'var(--muted)' }}>
            <div style={{ fontSize: 32, marginBottom: 12 }}>⚡</div>
            <div style={{ fontWeight: 600, marginBottom: 4 }}>No accounts yet</div>
            <div style={{ fontSize: 13 }}>Run the pipeline to populate Account Intelligence.</div>
          </div>
        ) : (
          <div style={{ overflowX: 'auto' }}>
            <table>
              <thead>
                <tr>
                  <th>Company</th>
                  <th>Industry</th>
                  <th>ICP Match</th>
                  <th>Signals</th>
                  <th>Signal Types</th>
                  <th>Score</th>
                  <th>Top Signal</th>
                  <th>Last Signal</th>
                  <th>Ready</th>
                </tr>
              </thead>
              <tbody>
                {filtered.map(row => (
                  <>
                    <tr key={row.accountId} onClick={() => setExpanded(expanded === row.accountId ? null : row.accountId)}
                      style={{ cursor: 'pointer' }}>
                      <td>
                        <div style={{ fontWeight: 600, fontSize: 13 }}>{row.companyName}</div>
                        {row.lastOutreachDate && <div style={{ fontSize: 11, color: 'var(--muted)' }}>Outreach: {row.lastOutreachDate}</div>}
                      </td>
                      <td style={{ color: 'var(--muted)', fontSize: 12 }}>{row.industry || '—'}</td>
                      <td style={{ fontSize: 12 }}>{row.icpMatch ? <span style={{ color: '#818cf8' }}>{row.icpMatch}</span> : <span style={{ color: 'var(--muted)' }}>—</span>}</td>
                      <td style={{ textAlign: 'center', fontWeight: 600 }}>{row.totalSignals}</td>
                      <td style={{ maxWidth: 180 }}>
                        <div style={{ fontSize: 11, color: 'var(--muted)', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                          {row.signalTypes || '—'}
                        </div>
                      </td>
                      <td><ScoreBar score={row.highestScore} /></td>
                      <td style={{ fontSize: 12 }}>{row.topSignal ? <span className="badge badge-new">{row.topSignal}</span> : '—'}</td>
                      <td style={{ fontSize: 12, color: 'var(--muted)' }}>{row.lastSignalDate || '—'}</td>
                      <td>
                        <span className={`badge ${row.outreachReady === 'YES' ? 'badge-ready' : 'badge-no'}`}>
                          {row.outreachReady}
                        </span>
                      </td>
                    </tr>
                    {expanded === row.accountId && (
                      <tr key={`${row.accountId}-detail`}>
                        <td colSpan={9} style={{ background: 'rgba(99,102,241,0.04)', padding: 0 }}>
                          <div style={{ padding: '16px 20px', display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: 16 }}>
                            <div>
                              <div style={{ fontSize: 11, color: 'var(--muted)', textTransform: 'uppercase', marginBottom: 6 }}>Pitch Angle</div>
                              <div style={{ fontSize: 13 }}>{row.topPitchAngle || '—'}</div>
                            </div>
                            <div>
                              <div style={{ fontSize: 11, color: 'var(--muted)', textTransform: 'uppercase', marginBottom: 6 }}>Key Services</div>
                              <div style={{ fontSize: 12, color: 'var(--muted)', lineHeight: 1.6 }}>{row.keyServices || '—'}</div>
                            </div>
                            <div>
                              <div style={{ fontSize: 11, color: 'var(--muted)', textTransform: 'uppercase', marginBottom: 6 }}>Notes</div>
                              <div style={{ fontSize: 13 }}>{row.notes || '—'}</div>
                            </div>
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
