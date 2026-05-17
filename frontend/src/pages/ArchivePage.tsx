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
      title: 'Archive des énigmes — Sutom-Commune',
      description:
        'Retrouvez les énigmes passées de Sutom-Commune : les 30 derniers jours sont rejouables sans impact sur votre streak.',
    });

    const today = new Date();
    const to = today.toISOString().slice(0, 10);
    const fromDate = new Date(today);
    fromDate.setDate(fromDate.getDate() - 60);
    const from = fromDate.toISOString().slice(0, 10);

    api
      .getArchive(from, to)
      .then((r) => setEntries(r.entries))
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
    <article className="space-y-6">
      <header className="space-y-1">
        <h1 className="font-serif text-4xl text-atlas-navy">Archive</h1>
        <p className="text-sm text-atlas-ink/70">
          Les énigmes des jours passés. Rejouer une archive ne casse pas votre série du jour.
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
                  <p className="font-medium text-atlas-navy">{e.commune.name}</p>
                  <p className="text-xs text-atlas-ink/60">
                    {e.commune.departmentName} ({e.commune.departmentCode})
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
