import { useEffect, useState } from 'react';
import { useOutletContext } from 'react-router-dom';
import { Loader2 } from 'lucide-react';
import { api } from '../api/client';
import type { PuzzleToday } from '../api/types';
import { setSeo } from '../seo';
import { formatDateLong } from '../i18n';
import { GameBoard } from '../components/GameBoard';
import type { LayoutContext } from '../components/AppLayout';

export function TodayPage() {
  const [puzzle, setPuzzle] = useState<PuzzleToday | null>(null);
  const [error, setError] = useState<string | null>(null);
  const ctx = useOutletContext<LayoutContext | undefined>();

  useEffect(() => {
    setSeo({
      title: "Sutom-Commune — Énigme du jour : devinez la commune française mystère",
      description:
        "Devinez la commune française mystère du jour en 6 essais. Distance à vol d'oiseau, direction cardinale et indices contextuels progressifs (population, climat, patrimoine, première lettre).",
      path: '/',
      breadcrumbs: [{ name: 'Accueil', path: '/' }],
    });
    api
      .getToday()
      .then((p) => {
        setPuzzle(p);
        // Enrichit le SEO une fois qu'on connaît la région/département
        setSeo({
          title: `Énigme #${p.puzzleNumber} du ${formatDateLong(p.puzzleDate)} — Sutom-Commune`,
          description: `Devinez la commune française du jour (région ${p.regionName}, département ${p.departmentName}) en 6 essais. Distance, direction, indices progressifs.`,
          path: '/',
          breadcrumbs: [{ name: 'Accueil', path: '/' }],
          extraJsonLd: {
            '@context': 'https://schema.org',
            '@type': 'Game',
            '@id': `https://sutom-commune.fr/#puzzle-${p.puzzleNumber}`,
            name: `Énigme Sutom-Commune #${p.puzzleNumber}`,
            datePublished: p.puzzleDate,
            inLanguage: 'fr-FR',
            isPartOf: { '@id': 'https://sutom-commune.fr/#game' },
          },
        });
      })
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
      <div className="space-y-4" aria-busy="true">
        <header className="space-y-1">
          <p className="text-xs uppercase tracking-wider text-atlas-ink/60">Énigme du jour</p>
          <h1 className="font-serif text-3xl text-atlas-navy">
            Quelle commune se cache ici ?
          </h1>
          <p className="text-sm text-atlas-ink/70">
            Chargement de l'énigme du jour…
          </p>
        </header>
        <div className="flex justify-center py-8">
          <Loader2 className="animate-spin text-atlas-ocre" aria-label="Chargement" />
        </div>
      </div>
    );
  }

  return <GameBoard puzzle={puzzle} onStreakChange={() => ctx?.refreshStreak()} />;
}
