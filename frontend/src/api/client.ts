import type {
  ArchiveEntry,
  CommuneSearchResult,
  GuessFeedback,
  PuzzleToday,
} from './types';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? '/api';

async function get<T>(path: string, init?: RequestInit): Promise<T> {
  const res = await fetch(`${API_BASE_URL}${path}`, {
    ...init,
    headers: { Accept: 'application/json', ...(init?.headers ?? {}) },
  });
  if (!res.ok) {
    throw new Error(`API ${path} → ${res.status}`);
  }
  return (await res.json()) as T;
}

async function post<T>(path: string, body: unknown): Promise<T> {
  const res = await fetch(`${API_BASE_URL}${path}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify(body),
  });
  if (!res.ok) {
    const text = await res.text().catch(() => '');
    throw new Error(`API ${path} → ${res.status} ${text}`);
  }
  return (await res.json()) as T;
}

export const api = {
  getToday(): Promise<PuzzleToday> {
    return get<PuzzleToday>('/puzzle/today');
  },

  searchCommunes(query: string, signal?: AbortSignal): Promise<CommuneSearchResult[]> {
    return get<{ results: CommuneSearchResult[] }>(
      `/communes/search?q=${encodeURIComponent(query)}`,
      { signal },
    ).then((r) => r.results);
  },

  submitGuess(input: {
    puzzleDate: string;
    attemptIndex: number;
    guessCodeInsee: string;
    sessionHash: string;
  }): Promise<GuessFeedback> {
    return post<GuessFeedback>('/puzzle/guess', input);
  },

  getArchive(limit = 3): Promise<{ entries: ArchiveEntry[] }> {
    return get<{ entries: ArchiveEntry[] }>(`/puzzle/archive?limit=${limit}`);
  },
};
