import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ExternalLink, Loader2, MapPin } from 'lucide-react';
import { api } from '../api/client';
import type { CommuneDetail } from '../api/types';
import { formatDateLong, formatNumber } from '../i18n';
import { setSeo } from '../seo';

const SITE_URL = 'https://sutom-commune.com';

/**
 * Fiche encyclopédique d'une commune jouable : /commune/{slug}.
 * Page longue traîne SEO (≈300 communes). Faits publics + apparitions passées
 * comme énigme (jamais celle du jour — l'API filtre côté serveur).
 */
export function CommunePage() {
  const { slug } = useParams<{ slug: string }>();
  const [commune, setCommune] = useState<CommuneDetail | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!slug) return;
    setCommune(null);
    setError(null);

    api
      .getCommune(slug)
      .then((c) => {
        setCommune(c);
        applySeo(c);
      })
      .catch((err: unknown) => setError(err instanceof Error ? err.message : 'erreur'));
  }, [slug]);

  if (error) {
    return (
      <div className="space-y-4 py-8">
        <h1 className="font-serif text-3xl text-atlas-navy">Commune introuvable</h1>
        <p className="text-atlas-ink/70">
          Cette commune n'existe pas dans Sutom-Commune ou n'est pas jouable.
        </p>
        <Link to="/" className="text-atlas-navy underline hover:text-atlas-ocre">
          Retour à l'énigme du jour
        </Link>
      </div>
    );
  }

  if (!commune) {
    return (
      <div className="flex justify-center py-12" aria-busy="true">
        <Loader2 className="animate-spin text-atlas-ocre" aria-label="Chargement" />
      </div>
    );
  }

  return (
    <article className="space-y-6 max-w-3xl mx-auto">
      <nav aria-label="fil d'ariane" className="text-xs text-atlas-ink/70">
        <Link to="/" className="hover:text-atlas-navy">
          Accueil
        </Link>
        {' › '}
        <span>Communes</span>
        {' › '}
        <span>{commune.name}</span>
      </nav>

      <header className="space-y-1">
        <h1 className="font-serif text-4xl text-atlas-navy flex items-center gap-2">
          <MapPin className="text-atlas-ocre" size={28} aria-hidden /> {commune.name}
        </h1>
        <p className="text-sm text-atlas-ink/70">
          {commune.departmentName} ({commune.departmentCode}) · {commune.regionName}
        </p>
      </header>

      <p className="prose-atlas">
        <strong>{commune.name}</strong> est une commune française de{' '}
        <strong>{formatNumber(commune.population)} habitants</strong>, située dans le département{' '}
        {commune.departmentName} ({commune.departmentCode}), en région {commune.regionName}. Elle
        fait partie des communes à deviner dans le jeu quotidien Sutom-Commune.
      </p>

      <dl className="grid grid-cols-2 gap-x-6 gap-y-3 bg-atlas-paper/60 rounded-lg border border-atlas-ocre/30 p-4">
        <Fact label="Population" value={`${formatNumber(commune.population)} hab.`} />
        <Fact label="Code INSEE" value={commune.codeInsee} />
        {commune.postalCodes.length > 0 && (
          <Fact label="Code postal" value={commune.postalCodes.join(', ')} />
        )}
        <Fact label="Climat" value={commune.climate} />
        {commune.altitude !== null && <Fact label="Altitude" value={`${commune.altitude} m`} />}
        {commune.specialite && <Fact label="Spécialité" value={commune.specialite} />}
        <Fact label="Coordonnées" value={`${commune.latitude.toFixed(4)}, ${commune.longitude.toFixed(4)}`} />
      </dl>

      {commune.patrimoine.length > 0 && (
        <section className="space-y-2">
          <h2 className="font-serif text-2xl text-atlas-navy">Patrimoine</h2>
          <ul className="list-disc list-inside text-atlas-ink/90">
            {commune.patrimoine.map((p) => (
              <li key={p}>{p}</li>
            ))}
          </ul>
        </section>
      )}

      {commune.pastAppearances.length > 0 && (
        <section className="space-y-2">
          <h2 className="font-serif text-2xl text-atlas-navy">Déjà tombée comme énigme</h2>
          <ul className="divide-y divide-atlas-ocre/20 bg-atlas-paper/60 rounded-lg border border-atlas-ocre/30">
            {commune.pastAppearances.map((a) => (
              <li key={a.date}>
                <Link
                  to={`/archive/${a.date}`}
                  className="flex items-center justify-between p-3 hover:bg-atlas-sand transition-colors"
                >
                  <span className="text-atlas-navy">Énigme #{a.puzzleNumber}</span>
                  <span className="text-xs text-atlas-ink/70">{formatDateLong(a.date)}</span>
                </Link>
              </li>
            ))}
          </ul>
        </section>
      )}

      {commune.wikipediaUrl && (
        <p>
          <a
            href={commune.wikipediaUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="inline-flex items-center gap-1 text-atlas-navy underline hover:text-atlas-ocre"
          >
            Lire l'article Wikipédia <ExternalLink size={14} aria-hidden />
          </a>
        </p>
      )}

      <p className="pt-2">
        <Link to="/" className="text-atlas-navy underline hover:text-atlas-ocre">
          Jouer à l'énigme du jour →
        </Link>
      </p>
    </article>
  );
}

function Fact({ label, value }: { label: string; value: string }) {
  return (
    <div>
      <dt className="text-xs uppercase tracking-wide text-atlas-ink/60">{label}</dt>
      <dd className="text-atlas-ink/90">{value}</dd>
    </div>
  );
}

function applySeo(c: CommuneDetail): void {
  const title = `${c.name} (${c.departmentCode}) — fiche commune | Sutom-Commune`;
  const description = `${c.name}, commune de ${formatNumber(c.population)} habitants dans le ${c.departmentName} (${c.departmentCode}), région ${c.regionName}. Population, climat, patrimoine et apparitions dans le jeu Sutom-Commune.`;

  setSeo({
    title,
    description,
    path: `/commune/${c.slug}`,
    ogType: 'article',
    breadcrumbs: [
      { name: 'Accueil', path: '/' },
      { name: c.name, path: `/commune/${c.slug}` },
    ],
    extraJsonLd: {
      '@context': 'https://schema.org',
      '@type': 'City',
      '@id': `${SITE_URL}/commune/${c.slug}#place`,
      name: c.name,
      url: `${SITE_URL}/commune/${c.slug}`,
      ...(c.wikipediaUrl ? { sameAs: c.wikipediaUrl } : {}),
      geo: {
        '@type': 'GeoCoordinates',
        latitude: c.latitude,
        longitude: c.longitude,
      },
      ...(c.altitude !== null ? { elevation: `${c.altitude} m` } : {}),
      containedInPlace: {
        '@type': 'AdministrativeArea',
        name: c.departmentName,
        containedInPlace: {
          '@type': 'AdministrativeArea',
          name: c.regionName,
          containedInPlace: { '@type': 'Country', name: 'France' },
        },
      },
      ...(c.postalCodes.length > 0
        ? {
            address: {
              '@type': 'PostalAddress',
              postalCode: c.postalCodes[0],
              addressCountry: 'FR',
            },
          }
        : {}),
    },
  });
}
