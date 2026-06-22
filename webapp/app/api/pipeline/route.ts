import { NextRequest } from 'next/server';
import { runPipeline } from '@/lib/pipeline/pipeline';
import { ensureDefaultSettings } from '@/lib/pipeline/settings';

export const dynamic = 'force-dynamic';
export const maxDuration = 300;

export async function POST(req: NextRequest) {
  const { searchParams } = new URL(req.url);
  const type = (searchParams.get('type') || 'full') as 'full' | 'hourly' | 'daily';

  await ensureDefaultSettings();

  const encoder = new TextEncoder();
  const stream = new ReadableStream({
    async start(controller) {
      const send = (data: object) => {
        controller.enqueue(encoder.encode(`data: ${JSON.stringify(data)}\n\n`));
      };

      try {
        const stats = await runPipeline(type, (progress) => {
          send(progress);
        });
        send({ phase: 'complete', message: 'Done', stats });
      } catch (e: any) {
        send({ phase: 'error', message: e.message || 'Pipeline failed' });
      } finally {
        controller.close();
      }
    },
  });

  return new Response(stream, {
    headers: {
      'Content-Type': 'text/event-stream',
      'Cache-Control': 'no-cache',
      'Connection': 'keep-alive',
    },
  });
}
