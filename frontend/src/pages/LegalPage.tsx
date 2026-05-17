import { useEffect } from 'react';
import { setSeo } from '../seo';

export function LegalPage() {
  useEffect(() => {
    setSeo({
      title: 'Mentions légales — Sutom-Commune',
      description: 'Mentions légales, cookies et politique de confidentialité du site Sutom-Commune.',
    });
  }, []);

  return (
    <article className="prose-atlas space-y-6">
      <h1 className="font-serif text-4xl text-atlas-navy">Mentions légales</h1>

      <section className="space-y-2">
        <h2 className="font-serif text-2xl text-atlas-navy">Éditeur</h2>
        <p>Sutom-Commune est un projet indépendant. Contact : contact@sutom-commune.fr.</p>
      </section>

      <section className="space-y-2">
        <h2 className="font-serif text-2xl text-atlas-navy">Hébergement</h2>
        <p>Hébergé en France métropolitaine.</p>
      </section>

      <section className="space-y-2">
        <h2 className="font-serif text-2xl text-atlas-navy">Cookies & tracking</h2>
        <p>
          Aucun cookie tiers. Aucun pisteur (Google Analytics, Mixpanel, etc.). Les seules données
          stockées dans votre navigateur le sont via <code>localStorage</code> :
        </p>
        <ul className="list-disc list-inside">
          <li>L'état de votre partie du jour (essais + indices),</li>
          <li>Votre série (streak),</li>
          <li>Un identifiant de session pseudonyme regénéré régulièrement.</li>
        </ul>
      </section>

      <section className="space-y-2">
        <h2 className="font-serif text-2xl text-atlas-navy">RGPD</h2>
        <p>
          Aucune donnée personnelle n'est collectée. Les analytics agrégées (nombre de joueurs,
          distribution des essais) s'appuient sur un hash SHA-256 quotidien de votre IP tronquée
          (/24 IPv4) qui ne permet pas votre identification ni votre suivi entre jours.
        </p>
        <p>
          Pour toute demande relative aux données, contactez l'éditeur.
        </p>
      </section>

      <section className="space-y-2">
        <h2 className="font-serif text-2xl text-atlas-navy">Données et licences</h2>
        <p>
          Données communes : <a className="underline" href="https://geo.api.gouv.fr">geo.api.gouv.fr</a>{' '}
          (Etalab, licence ouverte). Fonds de carte :{' '}
          <a className="underline" href="https://www.openstreetmap.org/copyright">© OpenStreetMap contributors</a>{' '}
          (licence ODbL).
        </p>
      </section>
    </article>
  );
}
