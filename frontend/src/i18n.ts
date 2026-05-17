/**
 * Helpers d'internationalisation minimaux — FR uniquement au MVP, structure prête pour EN.
 */
export const LOCALE = 'fr-FR' as const;

export function formatNumber(value: number): string {
  return new Intl.NumberFormat(LOCALE).format(value);
}

export function formatDateLong(isoDate: string): string {
  const d = new Date(isoDate + 'T00:00:00Z');
  return new Intl.DateTimeFormat(LOCALE, {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(d);
}

export function formatDateShort(isoDate: string): string {
  const d = new Date(isoDate + 'T00:00:00Z');
  return new Intl.DateTimeFormat(LOCALE, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  }).format(d);
}
