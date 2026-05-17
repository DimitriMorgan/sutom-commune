import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ArrowLeft, Loader2 } from 'lucide-react';
import { GameBoard } from '../components/GameBoard';
import type { PuzzleToday } from '../api/types';
import { setSeo } from '../seo';
import { formatDateLong } from '../i18n';

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
    // SEO immédiat (avant fetch) avec ce qu'on sait via l'URL
    setSeo({
      title: `Énigme Sutom-Commune du ${formatDateLong(date)} — rejouez sans casser votre série`,
      description: `Rejouez l'énigme Sutom-Commune du ${formatDateLong(date)} : devinez la commune française mystère en 6 essais sans impact sur votre streak.`,
      path: `/archive/${date}`,
      ogImage: `/api/og/puzzle/${date}.svg`,
      ogType: 'article',
      breadcrumbs: [
        { name: 'Accueil', path: '/' },
        { name: 'Archive', path: '/archive' },
        { name: formatDateLong(date), path: `/archive/${date}` },
      ],
    });

    const baseUrl = import.meta.env.VITE_API_BASE_URL ?? '/api';
    fetch(`${baseUrl}/puzzle/archive/${date}`, { headers: { Accept: 'application/json' } })
      .then((res) => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json() as Promise<{ puzzle: PuzzleToday }>;
      })
      .then((r) => {
        setPuzzle(r.puzzle);
        // Re-set SEO une fois les données chargées (ajoute Game + lieu connu)
        setSeo({
          title: `Énigme #${r.puzzle.puzzleNumber} du ${formatDateLong(date)} (${r.puzzle.regionName}) — Sutom-Commune`,
          description: `Énigme Sutom-Commune n°${r.puzzle.puzzleNumber} du ${formatDateLong(date)} : commune française à deviner dans la région ${r.puzzle.regionName}, département ${r.puzzle.departmentName} (${r.puzzle.departmentCode}).`,
          path: `/archive/${date}`,
          ogImage: `/api/og/puzzle/${date}.svg`,
          ogType: 'article',
          breadcrumbs: [
            { name: 'Accueil', path: '/' },
            { name: 'Archive', path: '/archive' },
            { name: `#${r.puzzle.puzzleNumber} — ${formatDateLong(date)}`, path: `/archive/${date}` },
          ],
          extraJsonLd: {
            '@context': 'https://schema.org',
            '@type': 'Game',
            '@id': `https://sutom-commune.fr/archive/${date}#game`,
            name: `Énigme Sutom-Commune #${r.puzzle.puzzleNumber}`,
            datePublished: date,
            inLanguage: 'fr-FR',
            location: {
              '@type': 'AdministrativeArea',
              name: r.puzzle.regionName,
              containedInPlace: { '@type': 'Country', name: 'France' },
            },
            isPartOf: { '@id': 'https://sutom-commune.fr/#game' },
          },
        });
      })
      .catch((err: unknown) => setError(err instanceof Error ? err.message : 'erreur'));
  }, [date]);

  if (error) {
    return <div className="text-red-700 py-8">Erreur : {error}</div>;
  }
  if (!puzzle) {
    return (
      <div className="space-y-4" aria-busy="true">
        <nav aria-label="fil d'ariane" className="text-xs text-atlas-ink/70">
          <Link to="/" className="hover:text-atlas-navy">Accueil</Link>
          {' › '}
          <Link to="/archive" className="hover:text-atlas-navy">Archive</Link>
          {date && <> {' › '} <span>{formatDateLong(date)}</span></>}
        </nav>
        <h1 className="font-serif text-3xl text-atlas-navy">
          Énigme archivée{date && ` du ${formatDateLong(date)}`}
        </h1>
        <div className="flex justify-center py-8">
          <Loader2 className="animate-spin text-atlas-ocre" aria-label="Chargement" />
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <nav aria-label="fil d'ariane" className="text-xs text-atlas-ink/70">
        <Link to="/" className="hover:text-atlas-navy">Accueil</Link>
        {' › '}
        <Link to="/archive" className="hover:text-atlas-navy">Archive</Link>
        {' › '}
        <span>{formatDateLong(puzzle.puzzleDate)}</span>
      </nav>
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
