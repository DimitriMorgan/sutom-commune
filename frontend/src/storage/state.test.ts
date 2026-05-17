import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { gameStorage } from './state';

describe('gameStorage', () => {
  beforeEach(() => {
    localStorage.clear();
  });
  afterEach(() => {
    localStorage.clear();
  });

  it('persists and reloads a game state by puzzleDate', () => {
    const state = {
      attempts: [
        {
          codeInsee: '75056',
          name: 'Paris',
          distanceKm: 200,
          bearing: 'NW' as const,
          bearingDegrees: 310,
          proximityTier: 'orange' as const,
          isCorrect: false,
        },
      ],
      revealedHints: [{ key: 'population', label: 'Population', value: 'env. 2M' } as const],
      completed: false,
      won: false,
    };
    gameStorage.saveState('2026-05-18', state);
    expect(gameStorage.loadState('2026-05-18')).toEqual(state);
    expect(gameStorage.loadState('2026-05-17')).toBeNull();
  });

  it('returns a default streak when none stored', () => {
    expect(gameStorage.loadStreak()).toEqual({
      current: 0,
      best: 0,
      lastPlayedDate: null,
      lastWonDate: null,
    });
  });

  it('increments streak when winning consecutive days', () => {
    gameStorage.recordResult('2026-05-17', true);
    const day2 = gameStorage.recordResult('2026-05-18', true);
    expect(day2.current).toBe(2);
    expect(day2.best).toBe(2);
  });

  it('resets streak when a day is skipped', () => {
    gameStorage.recordResult('2026-05-15', true);
    const skipped = gameStorage.recordResult('2026-05-18', true);
    expect(skipped.current).toBe(1);
    expect(skipped.best).toBe(1);
  });

  it('breaks streak on loss but keeps best', () => {
    gameStorage.recordResult('2026-05-16', true);
    gameStorage.recordResult('2026-05-17', true);
    const lost = gameStorage.recordResult('2026-05-18', false);
    expect(lost.current).toBe(0);
    expect(lost.best).toBe(2);
  });

  it('marks tutorial as seen', () => {
    expect(gameStorage.hasSeenTutorial()).toBe(false);
    gameStorage.markTutorialSeen();
    expect(gameStorage.hasSeenTutorial()).toBe(true);
  });

  it('generates a stable 64-hex session hash', () => {
    const a = gameStorage.getOrCreateSessionHash();
    const b = gameStorage.getOrCreateSessionHash();
    expect(a).toBe(b);
    expect(a).toMatch(/^[a-f0-9]{64}$/);
  });
});
