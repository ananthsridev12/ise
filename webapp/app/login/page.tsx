'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';

export default function LoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      const res = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password }),
      });
      const data = await res.json();
      if (!res.ok) { setError(data.error || 'Login failed'); return; }
      router.push('/');
      router.refresh();
    } catch {
      setError('Network error');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div style={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', background: '#0f1117' }}>
      <div style={{ background: '#1a1d27', border: '1px solid #2a2d3a', borderRadius: 12, padding: '40px 36px', width: 360, boxShadow: '0 8px 32px rgba(0,0,0,0.4)' }}>
        <div style={{ marginBottom: 32 }}>
          <h1 style={{ color: '#fff', fontSize: 22, fontWeight: 700, margin: 0 }}>ISE</h1>
          <p style={{ color: '#6b7280', fontSize: 13, margin: '4px 0 0' }}>Intent Signal Engine — Sign in</p>
        </div>

        <form onSubmit={handleSubmit}>
          <div style={{ marginBottom: 16 }}>
            <label style={{ display: 'block', color: '#9ca3af', fontSize: 12, marginBottom: 6, fontWeight: 500 }}>Email</label>
            <input
              type="email"
              value={email}
              onChange={e => setEmail(e.target.value)}
              required
              autoFocus
              style={{ width: '100%', background: '#0f1117', border: '1px solid #2a2d3a', borderRadius: 8, color: '#fff', padding: '10px 12px', fontSize: 14, outline: 'none', boxSizing: 'border-box' }}
              placeholder="you@company.com"
            />
          </div>
          <div style={{ marginBottom: 24 }}>
            <label style={{ display: 'block', color: '#9ca3af', fontSize: 12, marginBottom: 6, fontWeight: 500 }}>Password</label>
            <input
              type="password"
              value={password}
              onChange={e => setPassword(e.target.value)}
              required
              style={{ width: '100%', background: '#0f1117', border: '1px solid #2a2d3a', borderRadius: 8, color: '#fff', padding: '10px 12px', fontSize: 14, outline: 'none', boxSizing: 'border-box' }}
              placeholder="••••••••"
            />
          </div>
          {error && <p style={{ color: '#f87171', fontSize: 13, marginBottom: 16, margin: '0 0 16px' }}>{error}</p>}
          <button
            type="submit"
            disabled={loading}
            style={{ width: '100%', background: '#4f46e5', color: '#fff', border: 'none', borderRadius: 8, padding: '11px 0', fontSize: 14, fontWeight: 600, cursor: loading ? 'not-allowed' : 'pointer', opacity: loading ? 0.7 : 1 }}
          >
            {loading ? 'Signing in…' : 'Sign in'}
          </button>
        </form>
      </div>
    </div>
  );
}
