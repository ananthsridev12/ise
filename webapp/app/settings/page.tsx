'use client';
import { useEffect, useState, useCallback } from 'react';

interface Query {
  queryId: number; queryName: string; googleQuery: string;
  country: string; language: string; frequency: string; priority: string;
  enabled: boolean; sourceType: string; remarks: string;
  lastRun: string;
}

const SOURCE_TYPES = ['GoogleNews', 'RSS', 'Indeed', 'Adzuna'];
const FREQUENCIES = ['Hourly', 'Daily', 'Weekly'];
const COUNTRIES = ['US', 'IN', 'GB', 'AU', 'DE', 'FR', 'SG'];

const DEFAULT_QUERIES = [
  { queryName: 'Manufacturing ERP Implementation (US)', googleQuery: 'manufacturing ERP OR SAP implementation', country: 'US', frequency: 'Daily', sourceType: 'GoogleNews' },
  { queryName: 'Greenfield Plant (US)', googleQuery: 'new manufacturing plant greenfield facility', country: 'US', frequency: 'Daily', sourceType: 'GoogleNews' },
  { queryName: 'Factory Automation (US)', googleQuery: 'factory automation industrial robots manufacturing', country: 'US', frequency: 'Daily', sourceType: 'GoogleNews' },
  { queryName: 'Digital Transformation Manufacturing (IN)', googleQuery: 'manufacturing digital transformation Industry 4.0', country: 'IN', frequency: 'Daily', sourceType: 'GoogleNews' },
  { queryName: 'ERP Implementation India', googleQuery: 'ERP SAP implementation manufacturing India', country: 'IN', frequency: 'Daily', sourceType: 'GoogleNews' },
  { queryName: 'Greenfield Plant India', googleQuery: 'new plant factory expansion India manufacturing', country: 'IN', frequency: 'Daily', sourceType: 'GoogleNews' },
  { queryName: 'SAP CPQ Hiring (Indeed US)', googleQuery: 'SAP CPQ implementation manager', country: 'US', frequency: 'Daily', sourceType: 'Indeed' },
  { queryName: 'PLM Engineer Hiring (Indeed US)', googleQuery: 'PLM engineer windchill teamcenter', country: 'US', frequency: 'Daily', sourceType: 'Indeed' },
  { queryName: 'PR Newswire RSS', googleQuery: 'https://www.prnewswire.com/rss/news-releases-list.rss', country: 'US', frequency: 'Hourly', sourceType: 'RSS' },
  { queryName: 'Business Wire RSS', googleQuery: 'https://feeds.businesswire.com/rss/home/?rss=G1&rssid=6', country: 'US', frequency: 'Hourly', sourceType: 'RSS' },
];

export default function SettingsPage() {
  const [tab, setTab] = useState<'queries' | 'settings' | 'setup'>('queries');
  const [queries, setQueries] = useState<Query[]>([]);
  const [settings, setSettings] = useState<{ key: string; value: string; description: string }[]>([]);
  const [loading, setLoading] = useState(true);
  const [newQuery, setNewQuery] = useState({ queryName: '', googleQuery: '', country: 'US', language: 'en', frequency: 'Daily', priority: 'Medium', sourceType: 'GoogleNews', remarks: '' });
  const [setupStatus, setSetupStatus] = useState('');

  const loadQueries = useCallback(async () => {
    const res = await fetch('/api/search-queries');
    setQueries(await res.json());
  }, []);

  const loadSettings = useCallback(async () => {
    const res = await fetch('/api/settings');
    setSettings(await res.json());
  }, []);

  useEffect(() => {
    Promise.all([loadQueries(), loadSettings()]).then(() => setLoading(false));
  }, [loadQueries, loadSettings]);

  async function addQuery(q: typeof newQuery) {
    if (!q.queryName || !q.googleQuery) return;
    await fetch('/api/search-queries', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(q) });
    await loadQueries();
  }

  async function deleteQuery(id: number) {
    if (!confirm('Delete this query?')) return;
    await fetch(`/api/search-queries/${id}`, { method: 'DELETE' });
    setQueries(prev => prev.filter(q => q.queryId !== id));
  }

  async function toggleQuery(id: number, enabled: boolean) {
    await fetch(`/api/search-queries/${id}`, { method: 'PATCH', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ enabled }) });
    setQueries(prev => prev.map(q => q.queryId === id ? { ...q, enabled } : q));
  }

  async function saveSetting(key: string, value: string) {
    await fetch('/api/settings', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ key, value }) });
  }

  async function runSetup(action: string) {
    setSetupStatus('Running...');
    const res = await fetch(`/api/setup?action=${action}`, { method: 'POST' });
    const json = await res.json();
    setSetupStatus(json.ok ? `✓ Done! ${JSON.stringify(json.result || json)}` : `Error: ${json.error}`);
  }

  async function addDefaultQueries() {
    for (const q of DEFAULT_QUERIES) {
      await addQuery({ ...q, language: 'en', priority: 'Medium', remarks: '' });
    }
    setSetupStatus('✓ Default queries added!');
  }

  return (
    <div style={{ maxWidth: 1000 }}>
      <h1 style={{ fontSize: 22, fontWeight: 700, margin: '0 0 20px' }}>🔧 Settings</h1>

      {/* Tabs */}
      <div style={{ display: 'flex', gap: 4, marginBottom: 20 }}>
        {(['queries', 'settings', 'setup'] as const).map(t => (
          <button key={t} onClick={() => setTab(t)}
            style={{ fontSize: 13, padding: '6px 16px', borderRadius: 6,
              background: tab === t ? 'rgba(99,102,241,0.2)' : 'var(--card)',
              border: `1px solid ${tab === t ? 'var(--accent)' : 'var(--card-border)'}`,
              color: tab === t ? '#818cf8' : 'var(--muted)', textTransform: 'capitalize' }}>
            {t === 'queries' ? 'Search Queries' : t === 'settings' ? 'App Settings' : 'Setup & Seed'}
          </button>
        ))}
      </div>

      {/* QUERIES TAB */}
      {tab === 'queries' && (
        <div>
          {/* Add new */}
          <div className="card" style={{ padding: '16px 20px', marginBottom: 16 }}>
            <div style={{ fontSize: 13, fontWeight: 600, marginBottom: 12 }}>Add New Query</div>
            <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap', alignItems: 'flex-end' }}>
              <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
                <label style={{ fontSize: 11, color: 'var(--muted)' }}>Query Name</label>
                <input value={newQuery.queryName} onChange={e => setNewQuery(p => ({ ...p, queryName: e.target.value }))} placeholder="e.g. ERP Manufacturing US" style={{ width: 200 }} />
              </div>
              <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
                <label style={{ fontSize: 11, color: 'var(--muted)' }}>Google Query / URL</label>
                <input value={newQuery.googleQuery} onChange={e => setNewQuery(p => ({ ...p, googleQuery: e.target.value }))} placeholder="manufacturing ERP implementation" style={{ width: 280 }} />
              </div>
              <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
                <label style={{ fontSize: 11, color: 'var(--muted)' }}>Source</label>
                <select value={newQuery.sourceType} onChange={e => setNewQuery(p => ({ ...p, sourceType: e.target.value }))}>
                  {SOURCE_TYPES.map(s => <option key={s}>{s}</option>)}
                </select>
              </div>
              <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
                <label style={{ fontSize: 11, color: 'var(--muted)' }}>Country</label>
                <select value={newQuery.country} onChange={e => setNewQuery(p => ({ ...p, country: e.target.value }))}>
                  {COUNTRIES.map(c => <option key={c}>{c}</option>)}
                </select>
              </div>
              <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
                <label style={{ fontSize: 11, color: 'var(--muted)' }}>Frequency</label>
                <select value={newQuery.frequency} onChange={e => setNewQuery(p => ({ ...p, frequency: e.target.value }))}>
                  {FREQUENCIES.map(f => <option key={f}>{f}</option>)}
                </select>
              </div>
              <button className="btn-primary" onClick={() => { addQuery(newQuery); setNewQuery(p => ({ ...p, queryName: '', googleQuery: '' })); }}>
                + Add Query
              </button>
            </div>
          </div>

          {/* Queries table */}
          <div className="card" style={{ overflow: 'hidden' }}>
            {loading ? <div style={{ padding: 24, color: 'var(--muted)' }}>Loading...</div> :
            queries.length === 0 ? (
              <div style={{ padding: 32, textAlign: 'center', color: 'var(--muted)' }}>
                <div style={{ marginBottom: 12 }}>No queries yet.</div>
                <button className="btn-secondary" onClick={addDefaultQueries}>+ Add Default Queries (10 pre-built)</button>
              </div>
            ) : (
              <table>
                <thead>
                  <tr>
                    <th>Enabled</th>
                    <th>Name</th>
                    <th>Query / URL</th>
                    <th>Source</th>
                    <th>Country</th>
                    <th>Frequency</th>
                    <th>Last Run</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  {queries.map(q => (
                    <tr key={q.queryId}>
                      <td>
                        <input type="checkbox" checked={q.enabled} onChange={e => toggleQuery(q.queryId, e.target.checked)} />
                      </td>
                      <td style={{ fontSize: 13, fontWeight: 500 }}>{q.queryName}</td>
                      <td style={{ maxWidth: 240 }}>
                        <div style={{ fontSize: 11, color: 'var(--muted)', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                          {q.googleQuery}
                        </div>
                      </td>
                      <td><span className="badge badge-new">{q.sourceType}</span></td>
                      <td style={{ fontSize: 12 }}>{q.country}</td>
                      <td style={{ fontSize: 12 }}>{q.frequency}</td>
                      <td style={{ fontSize: 11, color: 'var(--muted)' }}>{q.lastRun || '—'}</td>
                      <td>
                        <button className="btn-ghost" onClick={() => deleteQuery(q.queryId)} style={{ color: 'var(--danger)' }}>✕</button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
        </div>
      )}

      {/* SETTINGS TAB */}
      {tab === 'settings' && (
        <div className="card" style={{ padding: 20 }}>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
            {settings.map(s => (
              <div key={s.key} style={{ display: 'flex', gap: 16, alignItems: 'center' }}>
                <div style={{ minWidth: 220, fontSize: 13, fontWeight: 500 }}>{s.key}</div>
                <input defaultValue={s.value} onBlur={e => saveSetting(s.key, e.target.value)} style={{ flex: 1 }} />
                <div style={{ fontSize: 12, color: 'var(--muted)', minWidth: 200 }}>{s.description}</div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* SETUP TAB */}
      {tab === 'setup' && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
          {setupStatus && (
            <div className="card" style={{ padding: '12px 16px', fontSize: 13, color: 'var(--success)' }}>{setupStatus}</div>
          )}
          <SetupCard
            title="Load Signal Pack"
            desc="Seeds 8 categories, 37 signals, and 230+ keywords into the database. Safe to run multiple times."
            action={() => runSetup('signal-pack')}
            label="Load Signal Pack"
          />
          <SetupCard
            title="Load Pitch Playbook"
            desc="Seeds 16 SolidPro signal-to-pitch mappings including pitch angles, key services, and talk tracks."
            action={() => runSetup('pitch-playbook')}
            label="Load Pitch Playbook"
          />
          <SetupCard
            title="Load Everything (Recommended for first run)"
            desc="Loads signal pack + pitch playbook in one shot."
            action={() => runSetup('all')}
            label="Setup All"
            primary
          />
          <SetupCard
            title="Add Default Queries"
            desc="Adds 10 pre-built queries: ERP/SAP (US+India), greenfield, automation, PR Newswire RSS, Business Wire RSS, and hiring queries (Indeed)."
            action={addDefaultQueries}
            label="Add Default Queries"
          />
        </div>
      )}
    </div>
  );
}

function SetupCard({ title, desc, action, label, primary }: { title: string; desc: string; action: () => void; label: string; primary?: boolean }) {
  const [done, setDone] = useState(false);
  return (
    <div className="card" style={{ padding: '16px 20px', display: 'flex', alignItems: 'center', gap: 20 }}>
      <div style={{ flex: 1 }}>
        <div style={{ fontWeight: 600, fontSize: 14, marginBottom: 4 }}>{title}</div>
        <div style={{ fontSize: 13, color: 'var(--muted)' }}>{desc}</div>
      </div>
      <button className={primary ? 'btn-primary' : 'btn-secondary'} onClick={() => { action(); setDone(true); }} disabled={done}>
        {done ? '✓ Done' : label}
      </button>
    </div>
  );
}
