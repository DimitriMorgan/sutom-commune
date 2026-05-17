import {
  ArrowDown,
  ArrowDownLeft,
  ArrowDownRight,
  ArrowLeft,
  ArrowRight,
  ArrowUp,
  ArrowUpLeft,
  ArrowUpRight,
  Check,
} from 'lucide-react';
import type { AttemptEntry } from '../storage/state';

const BEARING_ICONS = {
  N: ArrowUp,
  NE: ArrowUpRight,
  E: ArrowRight,
  SE: ArrowDownRight,
  S: ArrowDown,
  SW: ArrowDownLeft,
  W: ArrowLeft,
  NW: ArrowUpLeft,
} as const;

const TIER_CLASSES = {
  green: 'bg-tier-green/15 border-tier-green/40 text-tier-green',
  yellow: 'bg-tier-yellow/20 border-tier-yellow/50 text-amber-700',
  orange: 'bg-tier-orange/15 border-tier-orange/40 text-tier-orange',
  red: 'bg-tier-red/15 border-tier-red/40 text-tier-red',
} as const;

export function GuessRow({ attempt }: { attempt: AttemptEntry }) {
  const Icon = BEARING_ICONS[attempt.bearing];
  const tierClass = TIER_CLASSES[attempt.proximityTier];

  return (
    <div
      className={`flex items-center justify-between gap-3 p-3 border-2 rounded-lg ${tierClass} animate-flip-in`}
      data-testid="guess-row"
    >
      <span className="font-semibold flex-1 truncate text-atlas-ink">{attempt.name}</span>
      <div className="flex items-center gap-2">
        {attempt.isCorrect ? (
          <span className="flex items-center gap-1 font-semibold text-tier-green">
            <Check size={20} />
            Trouvée !
          </span>
        ) : (
          <>
            <span className="font-mono text-sm tabular-nums">
              {attempt.distanceKm.toLocaleString('fr-FR')} km
            </span>
            <Icon size={22} className="shrink-0" />
          </>
        )}
      </div>
    </div>
  );
}
