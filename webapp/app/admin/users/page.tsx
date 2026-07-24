'use client';

import { useState, useEffect } from 'react';

interface User { id: number; email: string; displayName: string; role: string; active: boolean; createdAt: string; }
interface Vertical { id: number; name: string; }
interface Service { id: number; name: string; verticalId: number | null; }

export default function UsersPage() {
  const [users, setUsers] = useState<User[]>([]);
  const [verticals, setVerticals] = useState<Vertical[]>([]);
  const [services, setServices] = useState<Service[]>([]);
  const [showAdd, setShowAdd] = useState(false);
  const [selectedUser, setSelectedUser] = useState<User | null>(null);
  const [permissions, setPermissions] = useState<{ verticalIds: number[]; serviceIds: number[] }>({ verticalIds: [], serviceIds: [] });
  const [form, setForm] = useState({ email: '', password: '', displayName: '', role: 'member' });
  const [saving, setSaving] = useState(false);
  const [msg, setMsg] = useState('');

  async function load() {
    const [u, v, s] = await Promise.all([
      fetch('/api/admin/users').then(r => r.json()),
      fetch('/api/knowledge/verticals').then(r => r.json()),
      fetch('/api/knowledge/services').then(r => r.json()),
    ]);
    setUsers(Array.isArray(u) ? u : []);
    setVerticals(Array.isArray(v) ? v : []);
    setServices(Array.isArray(s) ? s : []);
  }

  useEffect(() => { load(); }, []);

  async function loadPermissions(user: User) {
    const p = await fetch(`/api/admin/permissions?user_id=${user.id}`).then(r => r.json());
    setPermissions(p);
    setSelectedUser(user);
  }

  async function addUser(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    const res = await fetch('/api/admin/users', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(form) });
    const data = await res.json();
    setSaving(false);
    if (data.ok) { setMsg('User created'); setShowAdd(false); setForm({ email: '', password: '', displayName: '', role: 'member' }); load(); }
    else setMsg(data.error || 'Error');
  }

  async function toggleActive(user: User) {
    await fetch(`/api/admin/users/${user.id}`, { method: 'PATCH', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ active: !user.active }) });
    load();
  }

  async function deleteUser(user: User) {
    if (!confirm(`Delete ${user.email}?`)) return;
    await fetch(`/api/admin/users/${user.id}`, { method: 'DELETE' });
    load();
  }

  async function savePermissions() {
    if (!selectedUser) return;
    setSaving(true);
    await fetch('/api/admin/permissions', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ userId: selectedUser.id, verticalIds: permissions.verticalIds, serviceIds: permissions.serviceIds }),
    });
    setSaving(false);
    setMsg('Permissions saved');
  }

  function toggleVertical(id: number) {
    setPermissions(p => ({ ...p, verticalIds: p.verticalIds.includes(id) ? p.verticalIds.filter(x => x !== id) : [...p.verticalIds, id] }));
  }
  function toggleService(id: number) {
    setPermissions(p => ({ ...p, serviceIds: p.serviceIds.includes(id) ? p.serviceIds.filter(x => x !== id) : [...p.serviceIds, id] }));
  }

  return (
    <div style={{ padding: 32, maxWidth: 900 }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
        <div>
          <h1 style={{ margin: 0, fontSize: 22, fontWeight: 700 }}>Users</h1>
          <p style={{ margin: '4px 0 0', color: 'var(--muted)', fontSize: 13 }}>Manage team members and their access</p>
        </div>
        <button onClick={() => setShowAdd(true)} style={{ background: '#4f46e5', color: '#fff', border: 'none', borderRadius: 8, padding: '9px 18px', fontSize: 13, fontWeight: 600, cursor: 'pointer' }}>+ Add User</button>
      </div>

      {msg && <div style={{ background: 'rgba(99,102,241,0.1)', border: '1px solid #4f46e5', borderRadius: 8, padding: '10px 14px', marginBottom: 20, fontSize: 13, color: '#818cf8' }}>{msg}</div>}

      {showAdd && (
        <div style={{ background: 'var(--card)', border: '1px solid var(--card-border)', borderRadius: 12, padding: 24, marginBottom: 24 }}>
          <h3 style={{ margin: '0 0 16px', fontSize: 15 }}>New User</h3>
          <form onSubmit={addUser} style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
            <div><label style={{ fontSize: 12, color: 'var(--muted)', display: 'block', marginBottom: 4 }}>Display Name</label>
              <input value={form.displayName} onChange={e => setForm(f => ({ ...f, displayName: e.target.value }))} style={inputStyle} placeholder="Jane Smith" /></div>
            <div><label style={{ fontSize: 12, color: 'var(--muted)', display: 'block', marginBottom: 4 }}>Email</label>
              <input type="email" value={form.email} onChange={e => setForm(f => ({ ...f, email: e.target.value }))} required style={inputStyle} placeholder="jane@company.com" /></div>
            <div><label style={{ fontSize: 12, color: 'var(--muted)', display: 'block', marginBottom: 4 }}>Password</label>
              <input type="password" value={form.password} onChange={e => setForm(f => ({ ...f, password: e.target.value }))} required style={inputStyle} placeholder="Temporary password" /></div>
            <div><label style={{ fontSize: 12, color: 'var(--muted)', display: 'block', marginBottom: 4 }}>Role</label>
              <select value={form.role} onChange={e => setForm(f => ({ ...f, role: e.target.value }))} style={inputStyle}>
                <option value="member">Member</option>
                <option value="admin">Admin</option>
              </select></div>
            <div style={{ gridColumn: '1/-1', display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
              <button type="button" onClick={() => setShowAdd(false)} style={btnSecondary}>Cancel</button>
              <button type="submit" disabled={saving} style={btnPrimary}>{saving ? 'Creating…' : 'Create User'}</button>
            </div>
          </form>
        </div>
      )}

      <div style={{ display: 'grid', gap: 12, gridTemplateColumns: selectedUser ? '1fr 1fr' : '1fr' }}>
        <div>
          {users.map(u => (
            <div key={u.id} onClick={() => u.role === 'member' ? loadPermissions(u) : undefined} style={{ background: 'var(--card)', border: `1px solid ${selectedUser?.id === u.id ? '#4f46e5' : 'var(--card-border)'}`, borderRadius: 10, padding: '14px 16px', marginBottom: 10, cursor: u.role === 'member' ? 'pointer' : 'default', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
              <div>
                <div style={{ fontSize: 14, fontWeight: 600 }}>{u.displayName || u.email}</div>
                <div style={{ fontSize: 12, color: 'var(--muted)', marginTop: 2 }}>{u.email}</div>
                <div style={{ marginTop: 6, display: 'flex', gap: 6 }}>
                  <span style={roleBadge(u.role)}>{u.role}</span>
                  <span style={statusBadge(u.active)}>{u.active ? 'Active' : 'Inactive'}</span>
                  {u.role === 'member' && <span style={{ fontSize: 11, color: '#4f46e5' }}>Click to manage access →</span>}
                </div>
              </div>
              <div style={{ display: 'flex', gap: 8 }} onClick={e => e.stopPropagation()}>
                <button onClick={() => toggleActive(u)} style={btnSmall}>{u.active ? 'Deactivate' : 'Activate'}</button>
                <button onClick={() => deleteUser(u)} style={{ ...btnSmall, color: '#f87171' }}>Delete</button>
              </div>
            </div>
          ))}
        </div>

        {selectedUser && (
          <div style={{ background: 'var(--card)', border: '1px solid #4f46e5', borderRadius: 10, padding: 20 }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
              <h3 style={{ margin: 0, fontSize: 14 }}>Access for {selectedUser.displayName || selectedUser.email}</h3>
              <button onClick={() => setSelectedUser(null)} style={btnSmall}>✕</button>
            </div>
            <div style={{ marginBottom: 16 }}>
              <div style={{ fontSize: 12, fontWeight: 600, color: 'var(--muted)', marginBottom: 8, textTransform: 'uppercase', letterSpacing: '0.05em' }}>Verticals</div>
              {verticals.length === 0 && <p style={{ fontSize: 12, color: 'var(--muted)' }}>No verticals yet — add them in Knowledge Hub</p>}
              {verticals.map(v => (
                <label key={v.id} style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 8, cursor: 'pointer', fontSize: 13 }}>
                  <input type="checkbox" checked={permissions.verticalIds.includes(v.id)} onChange={() => toggleVertical(v.id)} />
                  {v.name}
                </label>
              ))}
            </div>
            <div style={{ marginBottom: 20 }}>
              <div style={{ fontSize: 12, fontWeight: 600, color: 'var(--muted)', marginBottom: 8, textTransform: 'uppercase', letterSpacing: '0.05em' }}>Services</div>
              {services.length === 0 && <p style={{ fontSize: 12, color: 'var(--muted)' }}>No services yet — add them in Knowledge Hub</p>}
              {services.map(s => (
                <label key={s.id} style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 8, cursor: 'pointer', fontSize: 13 }}>
                  <input type="checkbox" checked={permissions.serviceIds.includes(s.id)} onChange={() => toggleService(s.id)} />
                  {s.name}
                </label>
              ))}
            </div>
            <button onClick={savePermissions} disabled={saving} style={btnPrimary}>{saving ? 'Saving…' : 'Save Permissions'}</button>
          </div>
        )}
      </div>
    </div>
  );
}

const inputStyle: React.CSSProperties = { width: '100%', background: 'var(--background)', border: '1px solid var(--card-border)', borderRadius: 8, color: 'var(--foreground)', padding: '9px 12px', fontSize: 13, boxSizing: 'border-box' };
const btnPrimary: React.CSSProperties = { background: '#4f46e5', color: '#fff', border: 'none', borderRadius: 8, padding: '9px 18px', fontSize: 13, fontWeight: 600, cursor: 'pointer' };
const btnSecondary: React.CSSProperties = { background: 'transparent', color: 'var(--muted)', border: '1px solid var(--card-border)', borderRadius: 8, padding: '9px 18px', fontSize: 13, cursor: 'pointer' };
const btnSmall: React.CSSProperties = { background: 'transparent', color: 'var(--muted)', border: '1px solid var(--card-border)', borderRadius: 6, padding: '5px 10px', fontSize: 11, cursor: 'pointer' };
const roleBadge = (role: string): React.CSSProperties => ({ fontSize: 10, fontWeight: 700, textTransform: 'uppercase', borderRadius: 4, padding: '2px 6px', background: role === 'admin' ? 'rgba(99,102,241,0.2)' : 'rgba(34,197,94,0.15)', color: role === 'admin' ? '#818cf8' : '#4ade80' });
const statusBadge = (active: boolean): React.CSSProperties => ({ fontSize: 10, fontWeight: 700, textTransform: 'uppercase', borderRadius: 4, padding: '2px 6px', background: active ? 'rgba(34,197,94,0.15)' : 'rgba(239,68,68,0.15)', color: active ? '#4ade80' : '#f87171' });
