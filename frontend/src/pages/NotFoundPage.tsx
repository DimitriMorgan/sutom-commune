import { useEffect } from 'react';
import { Link } from 'react-router-dom';
import { setSeo } from '../seo';

export function NotFoundPage() {
  useEffect(() => {
    setSeo({
      title: 'Page introuvable — Sutom-Commune',
      description: "Cette page n'existe pas. Revenez à l'énigme du jour ou explorez l'archive.",
      path: '/404',
      index: false,
      breadcrumbs: [{ name: 'Accueil', path: '/' }],
    });
  }, []);

  return (
    <article className="max-w-3xl mx-auto space-y-6 py-12 text-center">
      <p className="text-xs uppercase tracking-wider text-atlas-ink/60">Erreur 404</p>
      <h1 className="font-serif text-4xl text-atlas-navy">Page introuvable</h1>
      <p className="text-atlas-ink/70">
        L'URL demandée ne correspond à aucune page de Sutom-Commune.
      </p>
      <div className="flex flex-wrap justify-center gap-3 pt-4">
        <Link
          to="/"
          className="bg-atlas-navy text-atlas-paper px-4 py-2 rounded-lg hover:bg-atlas-ocre transition-colors"
        >
          Énigme du jour
        </Link>
        <Link
          to="/archive"
          className="border border-atlas-ocre/40 text-atlas-navy px-4 py-2 rounded-lg hover:bg-atlas-sand transition-colors"
        >
          Archive
        </Link>
        <Link
          to="/comment-jouer"
          className="border border-atlas-ocre/40 text-atlas-navy px-4 py-2 rounded-lg hover:bg-atlas-sand transition-colors"
        >
          Comment jouer
        </Link>
      </div>
    </article>
  );
}
