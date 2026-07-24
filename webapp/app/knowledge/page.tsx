'use client';

import { useState, useEffect } from 'react';

interface Vertical { id: number; name: string; focus: string; industries: string; priority: string; }
interface Service { id: number; name: string; verticalId: number | null; description: string; signalKeywords: string; signalTypes: string; techTriggers: string; }

export default function KnowledgePage() {
  const [tab, setTab] = useState<'verticals' | 'services'>('verticals');
  const [verticals, setVerticals] = useState<Vertical[]>([]);
  const [services, setServices] = useState<Service[]>([]);
  const [showVForm, setShowVForm] = useState(false);
  const [showSForm, setShowSForm] = useState(false);
  const [vForm, setVForm] = useState({ name: '', focus: '', industries: '', priority: 'core' });
  const [sForm, setSForm] = useState({ name: '', verticalId: '', description: '', signalKeywords: '', signalTypes: '', techTriggers: '' });
  const [saving, setSaving] = useState(false);
  const [msg, setMsg] = useState('');

  async function load() {
    const [v, s] = await Promise.all([
      fetch('/api/knowledge/verticals').then(r => r.json()),
      fetch('/api/knowledge/services').then(r => r.json()),
    ]);
    setVerticals(Array.isArray(v) ? v : []);
    setServices(Array.isArray(s) ? s : []);
  }
  useEffect(() => { load(); }, []);

  async function addVertical(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    const res = await fetch('/api/knowledge/verticals', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(vForm) });
    const data = await res.json();
    setSaving(false);
    if (data.ok) { setMsg('Vertical added'); setShowVForm(false); setVForm({ name: '', focus: '', industries: '', priority: 'core' }); load(); }
    else setMsg(data.error || 'Error');
  }

  async function addService(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    const res = await fetch('/api/knowledge/services', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ...sForm, verticalId: sForm.verticalId ? parseInt(sForm.verticalId) : null }) });
    const data = await res.json();
    setSaving(false);
    if (data.ok) { setMsg('Service added'); setShowSForm(false); setSForm({ name: '', verticalId: '', description: '', signalKeywords: '', signalTypes: '', techTriggers: '' }); load(); }
    else setMsg(data.error || 'Error');
  }

  async function deleteVertical(id: number) {
    if (!confirm('Delete vertical?')) return;
    await fetch(`/api/knowledge/verticals/${id}`, { method: 'DELETE' });
    load();
  }

  async function deleteService(id: number) {
    if (!confirm('Delete service?')) return;
    await fetch(`/api/knowledge/services/${id}`, { method: 'DELETE' });
    load();
  }

  return (
    <div style={{ padding: 32, maxWidth: 900 }}>
      <div style={{ marginBottom: 24 }}>
        <h1 style={{ margin: 0, fontSize: 22, fontWeight: 700 }}>Knowledge Hub</h1>
        <p style={{ margin: '4px 0 0', color: 'var(--muted)', fontSize: 13 }}>Define your verticals and services — used for signal matching and member access control</p>
      </div>

      {msg && <div style={{ background: 'rgba(99,102,241,0.1)', border: '1px solid #4f46e5', borderRadius: 8, padding: '10px 14px', marginBottom: 20, fontSize: 13, color: '#818cf8' }}>{msg}</div>}

      <div style={{ display: 'flex', gap: 4, marginBottom: 24, background: 'var(--card)', borderRadius: 10, padding: 4, border: '1px solid var(--card-border)', width: 'fit-content' }}>
        {(['verticals', 'services'] as const).map(t => (
          <button key={t} onClick={() => setTab(t)} style={{ padding: '8px 18px', borderRadius: 7, border: 'none', cursor: 'pointer', fontSize: 13, fontWeight: 600, background: tab === t ? '#4f46e5' : 'transparent', color: tab === t ? '#fff' : 'var(--muted)', textTransform: 'capitalize' }}>{t}</button>
        ))}
      </div>

      {tab === 'verticals' && (
        <>
          <div style={{ display: 'flex', justifyContent: 'flex-end', marginBottom: 16 }}>
            <button onClick={() => setShowVForm(true)} style={btnPrimary}>+ Add Vertical</button>
          </div>
          {showVForm && (
            <div style={formCard}>
              <h3 style={{ margin: '0 0 16px', fontSize: 15 }}>New Vertical</h3>
              <form onSubmit={addVertical} style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
                <div><label style={labelStyle}>Name *</label><input value={vForm.name} onChange={e => setVForm(f => ({ ...f, name: e.target.value }))} required style={inputStyle} placeholder="ERP Transformation" /></div>
                <div><label style={labelStyle}>Priority</label>
                  <select value={vForm.priority} onChange={e => setVForm(f => ({ ...f, priority: e.target.value }))} style={inputStyle}>
                    <option value="core">Core</option><option value="growth">Growth</option><option value="emerging">Emerging</option>
                  </select></div>
                <div style={{ gridColumn: '1/-1' }}><label style={labelStyle}>Focus / Description</label><textarea value={vForm.focus} onChange={e => setVForm(f => ({ ...f, focus: e.target.value }))} style={{ ...inputStyle, height: 70, resize: 'vertical' }} placeholder="What this vertical specialises in…" /></div>
                <div style={{ gridColumn: '1/-1' }}><label style={labelStyle}>Target Industries (comma-separated)</label><input value={vForm.industries} onChange={e => setVForm(f => ({ ...f, industries: e.target.value }))} style={inputStyle} placeholder="Manufacturing, Logistics, Retail" /></div>
                <div style={{ gridColumn: '1/-1', display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
                  <button type="button" onClick={() => setShowVForm(false)} style={btnSecondary}>Cancel</button>
                  <button type="submit" disabled={saving} style={btnPrimary}>{saving ? 'Saving…' : 'Add Vertical'}</button>
                </div>
              </form>
            </div>
          )}
          <div style={{ display: 'grid', gap: 10 }}>
            {verticals.length === 0 && !showVForm && <p style={{ color: 'var(--muted)', fontSize: 13 }}>No verticals yet. Add your first vertical above.</p>}
            {verticals.map(v => (
              <div key={v.id} style={card}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                  <div>
                    <div style={{ fontSize: 15, fontWeight: 600 }}>{v.name}</div>
                    {v.focus && <div style={{ fontSize: 13, color: 'var(--muted)', marginTop: 4 }}>{v.focus}</div>}
                    {v.industries && <div style={{ fontSize: 12, color: 'var(--muted)', marginTop: 6 }}>Industries: {v.industries}</div>}
                    <span style={{ marginTop: 8, display: 'inline-block', fontSize: 10, fontWeight: 700, textTransform: 'uppercase', background: 'rgba(99,102,241,0.15)', color: '#818cf8', borderRadius: 4, padding: '2px 8px' }}>{v.priority}</span>
                  </div>
                  <button onClick={() => deleteVertical(v.id)} style={{ ...btnSmall, color: '#f87171' }}>Delete</button>
                </div>
              </div>
            ))}
          </div>
        </>
      )}

      {tab === 'services' && (
        <>
          <div style={{ display: 'flex', justifyContent: 'flex-end', marginBottom: 16 }}>
            <button onClick={() => setShowSForm(true)} style={btnPrimary}>+ Add Service</button>
          </div>
          {showSForm && (
            <div style={formCard}>
              <h3 style={{ margin: '0 0 16px', fontSize: 15 }}>New Service</h3>
              <form onSubmit={addService} style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
                <div><label style={labelStyle}>Name *</label><input value={sForm.name} onChange={e => setSForm(f => ({ ...f, name: e.target.value }))} required style={inputStyle} placeholder="SAP S/4HANA Migration" /></div>
                <div><label style={labelStyle}>Vertical</label>
                  <select value={sForm.verticalId} onChange={e => setSForm(f => ({ ...f, verticalId: e.target.value }))} style={inputStyle}>
                    <option value="">— none —</option>
                    {verticals.map(v => <option key={v.id} value={v.id}>{v.name}</option>)}
                  </select></div>
                <div style={{ gridColumn: '1/-1' }}><label style={labelStyle}>Description</label><textarea value={sForm.description} onChange={e => setSForm(f => ({ ...f, description: e.target.value }))} style={{ ...inputStyle, height: 70, resize: 'vertical' }} /></div>
                <div><label style={labelStyle}>Signal Keywords (comma-separated)</label><input value={sForm.signalKeywords} onChange={e => setSForm(f => ({ ...f, signalKeywords: e.target.value }))} style={inputStyle} placeholder="merger, erp upgrade, s4hana" /></div>
                <div><label style={labelStyle}>Signal Types (comma-separated)</label><input value={sForm.signalTypes} onChange={e => setSForm(f => ({ ...f, signalTypes: e.target.value }))} style={inputStyle} placeholder="M&A, ERP, Expansion" /></div>
                <div style={{ gridColumn: '1/-1' }}><label style={labelStyle}>Tech Triggers (comma-separated)</label><input value={sForm.techTriggers} onChange={e => setSForm(f => ({ ...f, techTriggers: e.target.value }))} style={inputStyle} placeholder="SAP ECC, Oracle EBS, Dynamics AX" /></div>
                <div style={{ gridColumn: '1/-1', display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
                  <button type="button" onClick={() => setShowSForm(false)} style={btnSecondary}>Cancel</button>
                  <button type="submit" disabled={saving} style={btnPrimary}>{saving ? 'Saving…' : 'Add Service'}</button>
                </div>
              </form>
            </div>
          )}
          <div style={{ display: 'grid', gap: 10 }}>
            {services.length === 0 && !showSForm && <p style={{ color: 'var(--muted)', fontSize: 13 }}>No services yet. Add your first service above.</p>}
            {services.map(s => (
              <div key={s.id} style={card}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                  <div>
                    <div style={{ fontSize: 15, fontWeight: 600 }}>{s.name}</div>
                    {s.verticalId && <div style={{ fontSize: 12, color: '#818cf8', marginTop: 2 }}>{verticals.find(v => v.id === s.verticalId)?.name}</div>}
                    {s.description && <div style={{ fontSize: 13, color: 'var(--muted)', marginTop: 4 }}>{s.description}</div>}
                    <div style={{ marginTop: 8, display: 'flex', flexWrap: 'wrap', gap: 6 }}>
                      {s.signalKeywords && <span style={chip}>Keywords: {s.signalKeywords}</span>}
                      {s.signalTypes && <span style={chip}>Types: {s.signalTypes}</span>}
                      {s.techTriggers && <span style={chip}>Tech: {s.techTriggers}</span>}
                    </div>
                  </div>
                  <button onClick={() => deleteService(s.id)} style={{ ...btnSmall, color: '#f87171' }}>Delete</button>
                </div>
              </div>
            ))}
          </div>
        </>
      )}
    </div>
  );
}

const inputStyle: React.CSSProperties = { width: '100%', background: 'var(--background)', border: '1px solid var(--card-border)', borderRadius: 8, color: 'var(--foreground)', padding: '9px 12px', fontSize: 13, boxSizing: 'border-box' };
const labelStyle: React.CSSProperties = { fontSize: 12, color: 'var(--muted)', display: 'block', marginBottom: 4 };
const btnPrimary: React.CSSProperties = { background: '#4f46e5', color: '#fff', border: 'none', borderRadius: 8, padding: '9px 18px', fontSize: 13, fontWeight: 600, cursor: 'pointer' };
const btnSecondary: React.CSSProperties = { background: 'transparent', color: 'var(--muted)', border: '1px solid var(--card-border)', borderRadius: 8, padding: '9px 18px', fontSize: 13, cursor: 'pointer' };
const btnSmall: React.CSSProperties = { background: 'transparent', color: 'var(--muted)', border: '1px solid var(--card-border)', borderRadius: 6, padding: '5px 10px', fontSize: 11, cursor: 'pointer' };
const card: React.CSSProperties = { background: 'var(--card)', border: '1px solid var(--card-border)', borderRadius: 10, padding: '16px 18px' };
const formCard: React.CSSProperties = { ...card, marginBottom: 20 };
const chip: React.CSSProperties = { fontSize: 11, background: 'rgba(99,102,241,0.1)', color: '#818cf8', borderRadius: 6, padding: '3px 8px' };
