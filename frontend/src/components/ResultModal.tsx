import { ExternalLink, X } from 'lucide-react';
import type { PuzzleSolution } from '../api/types';
import type { Streak } from '../storage/state';
import { formatNumber } from '../i18n';
import { ShareButton } from './ShareButton';

type Props = {
  solution: PuzzleSolution;
  shareGrid: string;
  won: boolean;
  attempts: number;
  maxAttempts: number;
  puzzleNumber: number;
  streak: Streak;
  archived?: boolean;
  onClose: () => void;
};

export function ResultModal({
  solution,
  shareGrid,
  won,
  attempts,
  maxAttempts,
  puzzleNumber,
  streak,
  archived = false,
  onClose,
}: Props) {
  return (
    <div className="fixed inset-0 z-50 bg-atlas-ink/60 flex items-center justify-center p-4">
      <div className="bg-atlas-paper rounded-lg shadow-2xl max-w-md w-full p-6 relative space-y-4">
        <button
          onClick={onClose}
          aria-label="Fermer"
          className="absolute top-3 right-3 p-1 rounded hover:bg-atlas-sand"
        >
          <X size={18} />
        </button>
        <header className="space-y-1">
          <p className="text-xs uppercase tracking-wider text-atlas-ink/60">
            Énigme #{puzzleNumber} · {won ? `Gagnée en ${attempts}/${maxAttempts}` : `Perdue (${maxAttempts}/${maxAttempts})`}
          </p>
          <h2 className="font-serif text-3xl text-atlas-navy">{solution.name}</h2>
          <p className="text-sm text-atlas-ink/70">
            {solution.departmentName} ({solution.departmentCode}) ·{' '}
            {formatNumber(solution.population)} habitants
          </p>
        </header>

        {solution.patrimoine.length > 0 && (
          <ul className="text-sm space-y-1 list-disc list-inside text-atlas-ink/80">
            {solution.patrimoine.slice(0, 3).map((p) => (
              <li key={p}>{p}</li>
            ))}
          </ul>
        )}

        <pre
          data-testid="share-grid"
          className="bg-atlas-sand/60 border border-atlas-ocre/30 rounded p-3 text-sm font-mono whitespace-pre overflow-x-auto"
        >
          {shareGrid}
        </pre>

        {!archived && (
          <div className="flex justify-between items-center text-sm">
            <span className="text-atlas-ink/70">Streak actuel</span>
            <span className="font-semibold text-atlas-navy">
              🔥 {streak.current} <span className="text-atlas-ink/50">/ {streak.best}</span>
            </span>
          </div>
        )}

        <ShareButton text={shareGrid} puzzleNumber={puzzleNumber} />

        {solution.wikipediaUrl && (
          <a
            href={solution.wikipediaUrl}
            target="_blank"
            rel="noreferrer noopener"
            className="flex items-center justify-center gap-2 text-sm text-atlas-navy hover:text-atlas-ocre transition-colors"
          >
            En savoir plus sur Wikipedia <ExternalLink size={14} />
          </a>
        )}
      </div>
    </div>
  );
}
