import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ArrowLeft, Loader2 } from 'lucide-react';
import { GameBoard } from '../components/GameBoard';
import type { PuzzleToday } from '../api/types';
import { setSeo } from '../seo';

/**
 * Affiche une énigme passée en mode rejouable (n'affecte pas le streak).
 * Charge l'énigme via /api/puzzle/archive/{date}.
 */
export function ArchiveDetailPage() {
  const { date } = useParams<{ date: string }>();
  const [puzzle, setPuzzle] = useState<PuzzleToday | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!date) return;
    setSeo({
      title: `Énigme du ${date} — Sutom-Commune`,
      description: `Rejouez l'énigme Sutom-Commune du ${date} sans casser votre série du jour.`,
    });

    const baseUrl = import.meta.env.VITE_API_BASE_URL ?? '/api';
    fetch(`${baseUrl}/puzzle/archive/${date}`, { headers: { Accept: 'application/json' } })
      .then((res) => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json() as Promise<{ puzzle: PuzzleToday }>;
      })
      .then((r) => setPuzzle(r.puzzle))
      .catch((err: unknown) => setError(err instanceof Error ? err.message : 'erreur'));
  }, [date]);

  if (error) {
    return <div className="text-red-700 py-8">Erreur : {error}</div>;
  }
  if (!puzzle) {
    return (
      <div className="flex justify-center py-12">
        <Loader2 className="animate-spin text-atlas-ocre" />
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <Link
        to="/archive"
        className="inline-flex items-center gap-1 text-sm text-atlas-navy hover:text-atlas-ocre"
      >
        <ArrowLeft size={14} /> Retour à l'archive
      </Link>
      <GameBoard puzzle={puzzle} archived />
    </div>
  );
}
