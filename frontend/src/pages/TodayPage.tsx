import { useEffect, useState } from 'react';
import { useOutletContext } from 'react-router-dom';
import { Loader2 } from 'lucide-react';
import { api } from '../api/client';
import type { PuzzleToday } from '../api/types';
import { setSeo } from '../seo';
import { GameBoard } from '../components/GameBoard';
import type { LayoutContext } from '../components/AppLayout';

export function TodayPage() {
  const [puzzle, setPuzzle] = useState<PuzzleToday | null>(null);
  const [error, setError] = useState<string | null>(null);
  const ctx = useOutletContext<LayoutContext | undefined>();

  useEffect(() => {
    setSeo({
      title: 'Sutom-Commune — Le jeu quotidien des communes de France',
      description:
        "Devinez la commune française mystère du jour en 6 essais. Distance, direction, indices : un défi géo pour les passionnés.",
    });
    api
      .getToday()
      .then(setPuzzle)
      .catch((err: unknown) => setError(err instanceof Error ? err.message : 'erreur'));
  }, []);

  if (error) {
    return (
      <div className="text-center py-12 text-red-700">
        Impossible de charger l'énigme du jour : {error}
      </div>
    );
  }

  if (!puzzle) {
    return (
      <div className="flex justify-center py-12">
        <Loader2 className="animate-spin text-atlas-ocre" />
      </div>
    );
  }

  return <GameBoard puzzle={puzzle} onStreakChange={() => ctx?.refreshStreak()} />;
}
