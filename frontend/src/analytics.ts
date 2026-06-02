import { gameStorage } from './storage/state';

/**
 * Bufferise les événements côté client (batch toutes les 2s + au unload),
 * puis envoie un POST à /api/analytics/events. Aucun cookie, aucun pisteur tiers.
 */
export type AnalyticsEvent =
  | { type: 'puzzle_started'; puzzleNumber: number }
  | { type: 'guess_submitted'; puzzleNumber: number; attemptIndex: number; isCorrect: boolean }
  | { type: 'puzzle_won'; puzzleNumber: number; attempts: number }
  | { type: 'puzzle_lost'; puzzleNumber: number }
  | { type: 'share_clicked'; puzzleNumber: number };

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? '/api';
const FLUSH_INTERVAL_MS = 2000;

// Script Umami (cf. index.html). Présent uniquement après chargement du tag et
// sur les domaines autorisés (data-domains) ; sinon window.umami est absent.
declare global {
  interface Window {
    umami?: {
      track: (eventName: string, eventData?: Record<string, unknown>) => void;
    };
  }
}

/** Pousse l'event custom vers Umami si le tracker est présent (best-effort). */
function sendToUmami(type: string, payload: Record<string, unknown>): void {
  if (typeof window === 'undefined' || !window.umami) return;
  try {
    window.umami.track(type, payload);
  } catch {
    // best-effort : on n'embête pas l'utilisateur
  }
}

let buffer: Array<{ type: string; payload: Record<string, unknown> }> = [];
let timer: number | null = null;

function flush(useBeacon = false): void {
  if (buffer.length === 0) return;
  const payload = {
    sessionHash: gameStorage.getOrCreateSessionHash(),
    events: buffer,
  };
  buffer = [];

  const url = `${API_BASE_URL}/analytics/events`;
  const body = JSON.stringify(payload);

  if (useBeacon && typeof navigator.sendBeacon === 'function') {
    navigator.sendBeacon(url, new Blob([body], { type: 'application/json' }));
    return;
  }

  void fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body,
    keepalive: true,
  }).catch(() => {
    // analytics best-effort, on n'embête pas l'utilisateur
  });
}

if (typeof window !== 'undefined') {
  window.addEventListener('pagehide', () => flush(true));
}

export function track(event: AnalyticsEvent): void {
  if (import.meta.env.DEV) {
    console.debug('[analytics]', event);
  }

  const { type, ...payload } = event;
  buffer.push({ type, payload: payload as Record<string, unknown> });

  // Double envoi : Umami (dashboard cloud) en plus du backend maison.
  sendToUmami(type, payload as Record<string, unknown>);

  if (timer !== null) return;
  timer = window.setTimeout(() => {
    timer = null;
    flush();
  }, FLUSH_INTERVAL_MS);
}
