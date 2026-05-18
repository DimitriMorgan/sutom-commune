import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Loader2 } from 'lucide-react';
import { api } from '../api/client';
import type { ArchiveEntry } from '../api/types';
import { formatDateLong } from '../i18n';
import { setSeo } from '../seo';

export function ArchivePage() {
  const [entries, setEntries] = useState<ArchiveEntry[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setSeo({
      title: 'Archive des énigmes Sutom-Commune — les 3 dernières à rejouer',
      description:
        "Les 3 dernières énigmes Sutom-Commune à rejouer sans casser votre série du jour. Région et département révélés, le reste à deviner — aucun spoil de la commune mystère.",
      path: '/archive',
      breadcrumbs: [
        { name: 'Accueil', path: '/' },
        { name: 'Archive', path: '/archive' },
      ],
    });

    api
      .getArchive(3)
      .then((r) => {
        setEntries(r.entries);
        if (r.entries.length > 0) {
          setSeo({
            title: 'Archive des énigmes Sutom-Commune — les 3 dernières à rejouer',
            description: `${r.entries.length} énigmes récentes à rejouer (régions ${[...new Set(r.entries.map((e) => e.regionName))].join(', ')}). Identité de la commune mystère cachée pour ne pas spoiler.`,
            path: '/archive',
            breadcrumbs: [
              { name: 'Accueil', path: '/' },
              { name: 'Archive', path: '/archive' },
            ],
            extraJsonLd: {
              '@context': 'https://schema.org',
              '@type': 'ItemList',
              itemListOrder: 'https://schema.org/ItemListOrderDescending',
              numberOfItems: r.entries.length,
              itemListElement: r.entries.map((e, i) => ({
                '@type': 'ListItem',
                position: i + 1,
                url: `https://sutom-commune.com/archive/${e.puzzleDate}`,
                name: `Énigme #${e.puzzleNumber} — ${e.regionName}`,
              })),
            },
          });
        }
      })
      .catch((err: unknown) => setError(err instanceof Error ? err.message : 'erreur'));
  }, []);

  if (error) {
    return <div className="text-red-700 py-8">Erreur de chargement : {error}</div>;
  }

  if (!entries) {
    return (
      <div className="flex justify-center py-12">
        <Loader2 className="animate-spin text-atlas-ocre" />
      </div>
    );
  }

  return (
    <article className="space-y-6 max-w-3xl mx-auto">
      <header className="space-y-1">
        <h1 className="font-serif text-4xl text-atlas-navy">Archive des énigmes</h1>
        <p className="text-sm text-atlas-ink/70">
          Les 3 dernières énigmes à rejouer. L'identité de la commune reste cachée — seuls
          la région et le département sont indiqués pour éviter tout spoil. Rejouer une
          archive ne casse pas votre série du jour.
        </p>
      </header>

      {entries.length === 0 ? (
        <p className="text-atlas-ink/60">Aucune énigme archivée pour le moment.</p>
      ) : (
        <ul className="divide-y divide-atlas-ocre/20 bg-atlas-paper/60 rounded-lg border border-atlas-ocre/30">
          {entries.map((e) => (
            <li key={e.puzzleDate}>
              <Link
                to={`/archive/${e.puzzleDate}`}
                className="flex items-center justify-between p-3 hover:bg-atlas-sand transition-colors"
              >
                <div>
                  <p className="font-medium text-atlas-navy">Commune mystère</p>
                  <p className="text-xs text-atlas-ink/70">
                    {e.regionName} · {e.departmentName} ({e.departmentCode})
                  </p>
                </div>
                <div className="text-right text-xs text-atlas-ink/70">
                  <p>#{e.puzzleNumber}</p>
                  <p>{formatDateLong(e.puzzleDate)}</p>
                </div>
              </Link>
            </li>
          ))}
        </ul>
      )}
    </article>
  );
}
