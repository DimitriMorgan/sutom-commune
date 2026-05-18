import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { render, screen, act, fireEvent } from '@testing-library/react';
import { GuessInput } from './GuessInput';
import { api } from '../api/client';

describe('GuessInput', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    vi.useRealTimers();
  });

  it('debounces searchCommunes calls by 200ms', async () => {
    const spy = vi.spyOn(api, 'searchCommunes').mockResolvedValue([]);

    render(<GuessInput onSubmit={() => {}} attemptNumber={1} />);
    const input = screen.getByRole('textbox') as HTMLInputElement;

    fireEvent.change(input, { target: { value: 'honf' } });

    // Avant la fenêtre de 200ms : aucun appel API.
    expect(spy).not.toHaveBeenCalled();
    await act(async () => {
      vi.advanceTimersByTime(199);
    });
    expect(spy).not.toHaveBeenCalled();

    // À 200ms, exactement un appel — pas un par lettre.
    await act(async () => {
      vi.advanceTimersByTime(1);
    });
    expect(spy).toHaveBeenCalledTimes(1);
    expect(spy).toHaveBeenLastCalledWith('honf', expect.any(AbortSignal));
  });

  it('ignores queries shorter than 2 chars', async () => {
    const spy = vi.spyOn(api, 'searchCommunes').mockResolvedValue([]);

    render(<GuessInput onSubmit={() => {}} attemptNumber={1} />);
    fireEvent.change(screen.getByRole('textbox'), { target: { value: 'h' } });

    await act(async () => {
      vi.advanceTimersByTime(500);
    });
    expect(spy).not.toHaveBeenCalled();
  });

  it('aborts the previous in-flight request when the user keeps typing', async () => {
    const seenSignals: AbortSignal[] = [];
    vi.spyOn(api, 'searchCommunes').mockImplementation(
      (_q: string, signal?: AbortSignal) => {
        if (signal) seenSignals.push(signal);
        return new Promise(() => {
          /* jamais résolu — laisse l'abort se déclencher */
        });
      },
    );

    render(<GuessInput onSubmit={() => {}} attemptNumber={1} />);
    const input = screen.getByRole('textbox') as HTMLInputElement;

    fireEvent.change(input, { target: { value: 'ho' } });
    await act(async () => {
      vi.advanceTimersByTime(200);
    });
    expect(seenSignals).toHaveLength(1);
    expect(seenSignals[0].aborted).toBe(false);

    // Nouvelle frappe avant que la promesse précédente ne résolve : le
    // useEffect cleanup doit abort()er le signal précédent.
    fireEvent.change(input, { target: { value: 'hon' } });
    expect(seenSignals[0].aborted).toBe(true);

    await act(async () => {
      vi.advanceTimersByTime(200);
    });
    expect(seenSignals).toHaveLength(2);
    expect(seenSignals[1].aborted).toBe(false);
  });
});
