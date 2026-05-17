import { useEffect, useState } from 'react';
import { api } from '../api/client';
import type {
  BearingLabel,
  CommuneSearchResult,
  GuessFeedback,
  GuessPathStep,
  Hint,
  ProximityTier,
  PuzzleSolution,
  PuzzleToday,
} from '../api/types';
import { track } from '../analytics';
import { gameStorage, type AttemptEntry, type GameState, type Streak } from '../storage/state';
import { CommuneMap } from './CommuneMap';
import { GuessInput } from './GuessInput';
import { GuessRow } from './GuessRow';
import { HintsPanel } from './HintsPanel';
import { ResultModal } from './ResultModal';

type Props = {
  puzzle: PuzzleToday;
  /** Mode archive : ne touche pas au streak. */
  archived?: boolean;
  onStreakChange?: (streak: Streak) => void;
};

export function GameBoard({ puzzle, archived = false, onStreakChange }: Props) {
  const [state, setState] = useState<GameState>(() => {
    return (
      gameStorage.loadState(puzzle.puzzleDate) ?? {
        attempts: [],
        revealedHints: [],
        completed: false,
        won: false,
      }
    );
  });
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [showResult, setShowResult] = useState(state.completed);

  // Persistance et tracking d'ouverture
  useEffect(() => {
    track({ type: 'puzzle_started', puzzleNumber: puzzle.puzzleNumber });
  }, [puzzle.puzzleNumber]);

  useEffect(() => {
    gameStorage.saveState(puzzle.puzzleDate, state);
  }, [puzzle.puzzleDate, state]);

  const submit = async (commune: CommuneSearchResult) => {
    if (state.completed || busy) return;
    setBusy(true);
    setError(null);
    const attemptIndex = state.attempts.length + 1;

    try {
      const feedback: GuessFeedback = await api.submitGuess({
        puzzleDate: puzzle.puzzleDate,
        attemptIndex,
        guessCodeInsee: commune.codeInsee,
        sessionHash: gameStorage.getOrCreateSessionHash(),
      });

      track({
        type: 'guess_submitted',
        puzzleNumber: puzzle.puzzleNumber,
        attemptIndex,
        isCorrect: feedback.isCorrect,
      });

      const newAttempt: AttemptEntry = {
        codeInsee: commune.codeInsee,
        name: commune.name,
        distanceKm: feedback.distanceKm ?? 0,
        bearing: (feedback.bearing ?? 'N') as BearingLabel,
        bearingDegrees: feedback.bearingDegrees ?? 0,
        proximityTier: (feedback.proximityTier ?? 'red') as ProximityTier,
        isCorrect: feedback.isCorrect,
      };

      if (feedback.gameOver) {
        const finalAttempts = [...state.attempts, newAttempt];
        // Reconstruit l'historique des bearings depuis guessPath pour les essais déjà faits
        // (le serveur recompose tout en mode gameOver).
        const reconciled: AttemptEntry[] = (feedback.guessPath ?? []).map((step: GuessPathStep) => ({
          codeInsee: step.codeInsee,
          name: step.name,
          distanceKm: step.distanceKm,
          bearing: step.bearing,
          bearingDegrees: step.bearingDegrees,
          proximityTier: step.proximityTier,
          isCorrect: step.isCorrect,
        }));

        setState({
          attempts: reconciled.length === finalAttempts.length ? reconciled : finalAttempts,
          revealedHints: state.revealedHints,
          completed: true,
          won: feedback.isCorrect,
          shareGrid: feedback.shareGrid,
          solution: feedback.solution,
          guessPath: feedback.guessPath,
        });
        track({
          type: feedback.isCorrect ? 'puzzle_won' : 'puzzle_lost',
          puzzleNumber: puzzle.puzzleNumber,
          attempts: finalAttempts.length,
        });
        if (!archived) {
          const next = gameStorage.recordResult(puzzle.puzzleDate, feedback.isCorrect);
          onStreakChange?.(next);
        }
        setShowResult(true);
      } else {
        const nextHints: Hint[] = feedback.nextHint
          ? [...state.revealedHints, feedback.nextHint]
          : state.revealedHints;

        setState({
          ...state,
          attempts: [...state.attempts, newAttempt],
          revealedHints: nextHints,
        });
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erreur inconnue');
    } finally {
      setBusy(false);
    }
  };

  const remaining = puzzle.maxAttempts - state.attempts.length;
  const guessPath = state.guessPath ?? buildGuessPathFromAttempts(state.attempts);

  return (
    <div className="space-y-4">
      <header className="space-y-1">
        <p className="text-xs uppercase tracking-wider text-atlas-ink/60">
          Énigme #{puzzle.puzzleNumber}{archived && ' · Archive'}
        </p>
        <h1 className="font-serif text-3xl text-atlas-navy">
          Quelle commune se cache ici ?
        </h1>
      </header>

      <HintsPanel
        regionName={puzzle.regionName}
        departmentName={puzzle.departmentName}
        departmentCode={puzzle.departmentCode}
        revealedHints={state.revealedHints}
      />

      <div className="space-y-2">
        {state.attempts.map((a, i) => (
          <GuessRow key={`${a.codeInsee}-${i}`} attempt={a} />
        ))}
      </div>

      {!state.completed ? (
        <>
          <GuessInput
            onSubmit={submit}
            disabled={busy}
            attemptNumber={state.attempts.length + 1}
          />
          <p className="text-xs text-atlas-ink/60 text-right">
            {remaining} essai{remaining > 1 ? 's' : ''} restant{remaining > 1 ? 's' : ''}
          </p>
          {error && <p className="text-sm text-red-700">{error}</p>}
        </>
      ) : (
        <button
          onClick={() => setShowResult(true)}
          className="w-full bg-atlas-navy text-atlas-paper py-2.5 rounded-lg hover:bg-atlas-ocre transition-colors"
        >
          Voir le résultat
        </button>
      )}

      <CommuneMap
        guessPath={guessPath}
        solution={state.completed && state.solution ? state.solution : null}
      />

      {showResult && state.solution && state.shareGrid && (
        <ResultModal
          solution={state.solution}
          shareGrid={state.shareGrid}
          won={state.won}
          attempts={state.attempts.length}
          maxAttempts={puzzle.maxAttempts}
          puzzleNumber={puzzle.puzzleNumber}
          streak={gameStorage.loadStreak()}
          archived={archived}
          onClose={() => setShowResult(false)}
        />
      )}
    </div>
  );
}

function buildGuessPathFromAttempts(_attempts: AttemptEntry[]): GuessPathStep[] {
  // En cours de partie, on n'a pas les lat/long depuis le serveur (anti-cheat).
  // La carte reste donc sans pins pendant le jeu — ils sont ajoutés en game over.
  return [];
}

export type { Streak };
