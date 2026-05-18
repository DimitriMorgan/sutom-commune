import { useEffect } from 'react';
import { setSeo } from '../seo';

export function AboutPage() {
  useEffect(() => {
    setSeo({
      title: 'À propos de Sutom-Commune — sources, philosophie, contact',
      description:
        "Sutom-Commune est un jeu quotidien gratuit pour explorer la géographie française. Données geo.api.gouv.fr (Etalab), cartes OpenStreetMap, aucun pisteur tiers.",
      path: '/a-propos',
      breadcrumbs: [
        { name: 'Accueil', path: '/' },
        { name: 'À propos', path: '/a-propos' },
      ],
    });
  }, []);

  return (
    <article className="prose-atlas space-y-4 max-w-3xl mx-auto">
      <h1 className="font-serif text-4xl text-atlas-navy">À propos de Sutom-Commune</h1>
      <p>
        <strong>Sutom-Commune</strong> est un jeu quotidien gratuit pour explorer la géographie
        française, à mi-chemin entre <em>Worldle</em> (distance + direction) et{' '}
        <em>Sutom</em> (indices progressifs).
      </p>
      <p>
        Les données sur les communes proviennent de l'API publique{' '}
        <a className="underline" href="https://geo.api.gouv.fr">geo.api.gouv.fr</a> (Etalab, INSEE).
        Les fonds de carte viennent d'<a className="underline" href="https://www.openstreetmap.org">OpenStreetMap</a>.
        Le patrimoine et les spécialités sont enrichis manuellement à partir de Wikipedia pour les
        communes les plus jouables.
      </p>
      <p>
        Aucune publicité, aucun pisteur tiers : ce que vous jouez reste dans votre navigateur.
        Pour suivre des stats agrégées anonymes, nous utilisons une session pseudonyme (hash
        SHA-256 quotidien de votre IP tronquée).
      </p>
    </article>
  );
}
