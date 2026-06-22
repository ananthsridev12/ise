'use client';
import { useState } from 'react';

interface Props {
  onComplete?: () => void;
}

export default function PipelineButton({ onComplete }: Props) {
  const [running, setRunning] = useState(false);
  const [messages, setMessages] = useState<string[]>([]);
  const [phase, setPhase] = useState('');

  async function run(type: 'full' | 'hourly' | 'daily') {
    setRunning(true);
    setMessages([]);
    setPhase('');

    const res = await fetch(`/api/pipeline?type=${type}`, { method: 'POST' });
    if (!res.body) { setRunning(false); return; }

    const reader = res.body.getReader();
    const decoder = new TextDecoder();
    let buf = '';

    while (true) {
      const { done, value } = await reader.read();
      if (done) break;
      buf += decoder.decode(value, { stream: true });
      const lines = buf.split('\n');
      buf = lines.pop() || '';
      for (const line of lines) {
        if (!line.startsWith('data: ')) continue;
        try {
          const data = JSON.parse(line.slice(6));
          setPhase(data.phase || '');
          setMessages(prev => [...prev.slice(-8), data.message]);
          if (data.phase === 'complete' || data.phase === 'error') {
            setRunning(false);
            onComplete?.();
          }
        } catch {}
      }
    }
    setRunning(false);
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 8, alignItems: 'flex-end' }}>
      <div style={{ display: 'flex', gap: 8 }}>
        <button className="btn-secondary" onClick={() => run('hourly')} disabled={running} style={{ fontSize: 12 }}>
          Hourly
        </button>
        <button className="btn-secondary" onClick={() => run('daily')} disabled={running} style={{ fontSize: 12 }}>
          Daily
        </button>
        <button className="btn-primary" onClick={() => run('full')} disabled={running}>
          {running ? '⏳ Running...' : '▶ Run Pipeline'}
        </button>
      </div>
      {messages.length > 0 && (
        <div style={{
          background: 'var(--card)', border: '1px solid var(--card-border)',
          borderRadius: 8, padding: '10px 14px', fontSize: 12, color: 'var(--muted)',
          maxWidth: 420, width: '100%', maxHeight: 120, overflowY: 'auto',
        }}>
          {messages.map((m, i) => (
            <div key={i} style={{ color: i === messages.length - 1 ? 'var(--foreground)' : 'var(--muted)', marginBottom: 2 }}>
              {i === messages.length - 1 && running ? '⏳ ' : '✓ '}{m}
            </div>
          ))}
          {phase === 'error' && <div style={{ color: 'var(--danger)', marginTop: 4 }}>Pipeline failed.</div>}
        </div>
      )}
    </div>
  );
}
