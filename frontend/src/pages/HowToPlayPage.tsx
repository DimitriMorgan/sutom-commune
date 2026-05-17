import { useEffect } from 'react';
import { setSeo } from '../seo';

export function HowToPlayPage() {
  useEffect(() => {
    setSeo({
      title: 'Comment jouer à Sutom-Commune : règles complètes du jeu quotidien',
      description:
        'Règles complètes de Sutom-Commune : devinez la commune française du jour en 6 essais grâce à la distance à vol d\'oiseau, la direction cardinale et 5 indices progressifs.',
      path: '/comment-jouer',
      breadcrumbs: [
        { name: 'Accueil', path: '/' },
        { name: 'Comment jouer', path: '/comment-jouer' },
      ],
      extraJsonLd: {
        '@context': 'https://schema.org',
        '@type': 'HowTo',
        name: 'Comment jouer à Sutom-Commune',
        description: "Devinez la commune française du jour en 6 essais maximum.",
        step: [
          { '@type': 'HowToStep', position: 1, name: 'Lire la région et le département' },
          { '@type': 'HowToStep', position: 2, name: 'Proposer une commune française' },
          { '@type': 'HowToStep', position: 3, name: 'Lire la distance, la flèche et le tier coloré' },
          { '@type': 'HowToStep', position: 4, name: 'Utiliser les indices progressifs (population, climat, patrimoine)' },
          { '@type': 'HowToStep', position: 5, name: 'Partager la grille emoji style Wordle' },
        ],
      },
    });
  }, []);

  return (
    <article className="prose-atlas space-y-6">
      <h1 className="font-serif text-4xl text-atlas-navy">Comment jouer à Sutom-Commune</h1>

      <section className="space-y-2">
        <h2 className="font-serif text-2xl text-atlas-navy">1. L'énigme du jour</h2>
        <p>
          Chaque jour à minuit (heure de Paris), une commune française est tirée au sort parmi les
          ~2 500 communes de plus de 5 000 habitants. Vous avez <strong>6 essais</strong> pour la
          deviner.
        </p>
        <p>
          À l'ouverture, vous ne connaissez que la <strong>région</strong> et le{' '}
          <strong>département</strong> de la commune cible.
        </p>
      </section>

      <section className="space-y-2">
        <h2 className="font-serif text-2xl text-atlas-navy">2. Chaque essai vous rapproche</h2>
        <p>Après chaque commune proposée, vous obtenez :</p>
        <ul className="list-disc list-inside">
          <li>la <strong>distance à vol d'oiseau</strong> en km vers la cible,</li>
          <li>une <strong>flèche directionnelle</strong> (N, NE, E, SE, S, SW, W, NW),</li>
          <li>
            un <strong>code couleur</strong> : 🟩 ≤ 5 km · 🟨 ≤ 25 km · 🟧 ≤ 100 km · 🟥 &gt; 100 km.
          </li>
        </ul>
      </section>

      <section className="space-y-2">
        <h2 className="font-serif text-2xl text-atlas-navy">3. Les indices contextuels</h2>
        <p>
          À chaque essai raté, un <strong>indice supplémentaire</strong> se révèle dans l'ordre :
        </p>
        <ol className="list-decimal list-inside">
          <li>Population</li>
          <li>Climat</li>
          <li>Spécialité économique</li>
          <li>Patrimoine</li>
          <li>Première lettre</li>
        </ol>
      </section>

      <section className="space-y-2">
        <h2 className="font-serif text-2xl text-atlas-navy">4. Partage et streak</h2>
        <p>
          Une fois terminé, partagez votre grille emoji style Wordle. Votre <strong>série</strong>{' '}
          (streak) compte le nombre de jours consécutifs gagnés et est sauvegardée localement dans
          votre navigateur.
        </p>
      </section>
    </article>
  );
}
