import type { Metadata } from 'next';
import './globals.css';
import Sidebar from '@/components/Sidebar';

export const metadata: Metadata = {
  title: 'ISE — Intent Signal Engine',
  description: 'B2B Intent Signal Engine — SolidPro',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body style={{ background: 'var(--background)', color: 'var(--foreground)', margin: 0, display: 'flex', minHeight: '100vh' }}>
        <Sidebar />
        <main style={{ flex: 1, overflow: 'auto', padding: '24px' }}>
          {children}
        </main>
      </body>
    </html>
  );
}
