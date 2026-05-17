import { Flame } from 'lucide-react';
import type { Streak } from '../storage/state';

export function StreakBadge({ streak }: { streak: Streak }) {
  return (
    <div
      className="flex items-center gap-2 px-3 py-1.5 rounded-full bg-atlas-paper/70 border border-atlas-ocre/30 text-sm"
      title={`Meilleur streak : ${streak.best}`}
    >
      <Flame size={16} className="text-atlas-ocre" />
      <span className="font-semibold text-atlas-navy">{streak.current}</span>
      <span className="text-atlas-ink/60 text-xs">/ {streak.best}</span>
    </div>
  );
}
