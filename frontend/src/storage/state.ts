import type { BearingLabel, GuessPathStep, Hint, ProximityTier, PuzzleSolution } from '../api/types';

/**
 * État persisté pour une énigme donnée. Une clé localStorage distincte par puzzleDate
 * permet de rejouer les archives sans casser la session du jour.
 */
export type AttemptEntry = {
  codeInsee: string;
  name: string;
  distanceKm: number;
  bearing: BearingLabel;
  bearingDegrees: number;
  proximityTier: ProximityTier;
  isCorrect: boolean;
};

export type GameState = {
  attempts: AttemptEntry[];
  revealedHints: Hint[];
  completed: boolean;
  won: boolean;
  shareGrid?: string;
  solution?: PuzzleSolution;
  guessPath?: GuessPathStep[];
};

export type Streak = {
  current: number;
  best: number;
  lastPlayedDate: string | null;
  lastWonDate: string | null;
};

const STREAK_KEY = 'sutomCommune.streak';
const TUTORIAL_KEY = 'sutomCommune.tutorial_seen';
const SESSION_KEY = 'sutomCommune.sessionHash';

function stateKey(puzzleDate: string): string {
  return `sutomCommune.state.${puzzleDate}`;
}

function safeParse<T>(raw: string | null): T | null {
  if (!raw) return null;
  try {
    return JSON.parse(raw) as T;
  } catch {
    return null;
  }
}

export const gameStorage = {
  loadState(puzzleDate: string): GameState | null {
    return safeParse<GameState>(localStorage.getItem(stateKey(puzzleDate)));
  },

  saveState(puzzleDate: string, state: GameState): void {
    localStorage.setItem(stateKey(puzzleDate), JSON.stringify(state));
  },

  clearState(puzzleDate: string): void {
    localStorage.removeItem(stateKey(puzzleDate));
  },

  loadStreak(): Streak {
    return (
      safeParse<Streak>(localStorage.getItem(STREAK_KEY)) ?? {
        current: 0,
        best: 0,
        lastPlayedDate: null,
        lastWonDate: null,
      }
    );
  },

  /**
   * Met à jour le streak après une partie terminée du jour `puzzleDate`.
   * - Ne pas appeler en mode archive (rejouer une vieille énigme ne doit pas affecter le streak).
   * - Si la veille a été gagnée → +1. Sinon → reset à 1.
   */
  recordResult(puzzleDate: string, won: boolean): Streak {
    const prev = this.loadStreak();

    const yesterday = (() => {
      const d = new Date(puzzleDate + 'T00:00:00Z');
      d.setUTCDate(d.getUTCDate() - 1);
      return d.toISOString().slice(0, 10);
    })();

    let current = prev.current;
    if (won) {
      if (prev.lastWonDate === yesterday) current += 1;
      else if (prev.lastWonDate === puzzleDate) current = prev.current;
      else current = 1;
    } else {
      current = 0;
    }
    const best = Math.max(prev.best, current);

    const next: Streak = {
      current,
      best,
      lastPlayedDate: puzzleDate,
      lastWonDate: won ? puzzleDate : prev.lastWonDate,
    };
    localStorage.setItem(STREAK_KEY, JSON.stringify(next));
    return next;
  },

  hasSeenTutorial(): boolean {
    return localStorage.getItem(TUTORIAL_KEY) === '1';
  },

  markTutorialSeen(): void {
    localStorage.setItem(TUTORIAL_KEY, '1');
  },

  getOrCreateSessionHash(): string {
    let hash = localStorage.getItem(SESSION_KEY);
    if (hash && /^[a-f0-9]{64}$/.test(hash)) return hash;
    hash = randomHex(64);
    localStorage.setItem(SESSION_KEY, hash);
    return hash;
  },
};

function randomHex(length: number): string {
  const bytes = new Uint8Array(length / 2);
  crypto.getRandomValues(bytes);
  return Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');
}
