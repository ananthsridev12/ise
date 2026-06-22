import crypto from 'crypto';
import dayjs from 'dayjs';

export function md5Hash(input: string): string {
  return crypto.createHash('md5').update(input).digest('hex');
}

export function isoDateTime(d?: Date): string {
  return dayjs(d || new Date()).format('YYYY-MM-DDTHH:mm:ss');
}

export function todayString(): string {
  return dayjs().format('YYYY-MM-DD');
}

export function ageInDays(date: Date): number {
  return (Date.now() - date.getTime()) / (1000 * 60 * 60 * 24);
}

export function parseDate(str: string): Date | null {
  if (!str) return null;
  const d = new Date(str);
  return isNaN(d.getTime()) ? null : d;
}

export function sleep(ms: number): Promise<void> {
  return new Promise(r => setTimeout(r, ms));
}

export function truncate(str: string, maxLen: number): string {
  str = String(str || '');
  return str.length > maxLen ? str.substring(0, maxLen - 1) + '…' : str;
}

export function stripHtml(str: string): string {
  if (!str) return '';
  return String(str).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
}
