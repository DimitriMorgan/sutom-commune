import { useEffect } from 'react';
import { setSeo } from '../seo';

/** Questions fréquentes — alimentent à la fois la section visible et le FAQPage JSON-LD. */
const FAQ: Array<{ question: string; answer: string }> = [
  {
    question: "Combien d'essais pour deviner la commune ?",
    answer:
      'Vous disposez de 6 essais maximum par énigme. Chaque commune proposée vous indique la distance à vol d’oiseau, la direction cardinale et un code couleur de proximité.',
  },
  {
    question: 'À quelle heure tombe la nouvelle énigme ?',
    answer:
      "Une nouvelle commune est tirée au sort chaque jour à minuit, heure de Paris. L’énigme est la même pour tous les joueurs.",
  },
  {
    question: 'Quelles communes peuvent tomber ?',
    answer:
      'Toutes les communes françaises de plus de 5 000 habitants, soit environ 2 500 communes. Les plus peuplées sont les plus faciles, les plus petites les plus expertes.',
  },
  {
    question: 'Rejouer une ancienne énigme casse-t-il ma série ?',
    answer:
      "Non. Les énigmes de l’archive sont rejouables librement et n’affectent jamais votre série (streak) du jour, qui ne dépend que de l’énigme quotidienne.",
  },
  {
    question: 'Le jeu est-il gratuit et sans inscription ?',
    answer:
      'Oui, Sutom-Commune est entièrement gratuit, sans compte ni inscription. Votre progression et votre série sont sauvegardées localement dans votre navigateur, sans cookie de pistage.',
  },
];

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
      extraJsonLd: [
        {
          '@context': 'https://schema.org',
          '@type': 'HowTo',
          name: 'Comment jouer à Sutom-Commune',
          description: 'Devinez la commune française du jour en 6 essais maximum.',
          step: [
            { '@type': 'HowToStep', position: 1, name: 'Lire la région et le département' },
            { '@type': 'HowToStep', position: 2, name: 'Proposer une commune française' },
            { '@type': 'HowToStep', position: 3, name: 'Lire la distance, la flèche et le tier coloré' },
            { '@type': 'HowToStep', position: 4, name: 'Utiliser les indices progressifs (population, climat, patrimoine)' },
            { '@type': 'HowToStep', position: 5, name: 'Partager la grille emoji style Wordle' },
          ],
        },
        {
          '@context': 'https://schema.org',
          '@type': 'FAQPage',
          mainEntity: FAQ.map((f) => ({
            '@type': 'Question',
            name: f.question,
            acceptedAnswer: { '@type': 'Answer', text: f.answer },
          })),
        },
      ],
    });
  }, []);

  return (
    <article className="prose-atlas space-y-6 max-w-3xl mx-auto">
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

      <section className="space-y-4">
        <h2 className="font-serif text-2xl text-atlas-navy">Questions fréquentes</h2>
        <dl className="space-y-4">
          {FAQ.map((f) => (
            <div key={f.question} className="space-y-1">
              <dt className="font-medium text-atlas-navy">{f.question}</dt>
              <dd className="text-atlas-ink/90">{f.answer}</dd>
            </div>
          ))}
        </dl>
      </section>
    </article>
  );
}
