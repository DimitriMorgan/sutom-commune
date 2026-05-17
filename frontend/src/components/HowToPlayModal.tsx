import { useState } from 'react';
import { ArrowLeft, ArrowRight, X } from 'lucide-react';

const SCREENS: Array<{ title: string; body: React.ReactNode }> = [
  {
    title: 'Devinez la commune mystère du jour',
    body: (
      <>
        <p>Une commune française à deviner en <strong>6 essais</strong>.</p>
        <p>À l'ouverture, vous savez seulement sa <strong>région</strong> et son <strong>département</strong>.</p>
      </>
    ),
  },
  {
    title: 'Chaque essai vous rapproche',
    body: (
      <>
        <p>Tapez le nom d'une commune française. À chaque essai :</p>
        <ul className="list-disc list-inside space-y-1">
          <li><strong>Distance</strong> à vol d'oiseau (km)</li>
          <li><strong>Flèche</strong> directionnelle vers la cible</li>
          <li><strong>Couleur</strong> 🟩 ≤5 km · 🟨 ≤25 · 🟧 ≤100 · 🟥 &gt;100</li>
        </ul>
        <p>À chaque raté, un <strong>indice contextuel</strong> se révèle (population, climat, patrimoine…).</p>
      </>
    ),
  },
  {
    title: 'Partagez votre grille',
    body: (
      <>
        <p>Une fois terminée, partagez votre grille emoji façon Wordle :</p>
        <pre className="bg-atlas-paper/60 px-3 py-2 rounded text-xs font-mono">
          {`Sutom-Commune #42 · 4/6\n🟥↗️\n🟧⬆️\n🟨↖️\n🟩✓`}
        </pre>
        <p>Une nouvelle énigme chaque jour à minuit (Europe/Paris).</p>
      </>
    ),
  },
];

export function HowToPlayModal({ onClose }: { onClose: () => void }) {
  const [step, setStep] = useState(0);
  const screen = SCREENS[step];
  const isLast = step === SCREENS.length - 1;

  return (
    <div className="fixed inset-0 z-50 bg-atlas-ink/60 flex items-center justify-center p-4">
      <div className="bg-atlas-paper rounded-lg shadow-2xl max-w-md w-full p-6 relative space-y-4">
        <button
          onClick={onClose}
          aria-label="Fermer"
          className="absolute top-3 right-3 p-1 rounded hover:bg-atlas-sand"
        >
          <X size={18} />
        </button>
        <h2 className="font-serif text-2xl text-atlas-navy pr-8">{screen.title}</h2>
        <div className="space-y-3 text-sm">{screen.body}</div>
        <div className="flex items-center justify-between pt-3">
          <button
            onClick={() => setStep(Math.max(0, step - 1))}
            disabled={step === 0}
            className="flex items-center gap-1 text-sm text-atlas-ink/70 disabled:opacity-30 hover:text-atlas-navy"
          >
            <ArrowLeft size={16} /> Précédent
          </button>
          <div className="flex gap-1.5">
            {SCREENS.map((_, i) => (
              <span
                key={i}
                className={`w-2 h-2 rounded-full ${i === step ? 'bg-atlas-navy' : 'bg-atlas-ocre/40'}`}
              />
            ))}
          </div>
          {isLast ? (
            <button
              onClick={onClose}
              className="bg-atlas-navy text-atlas-paper px-3 py-1.5 rounded text-sm hover:bg-atlas-ocre transition-colors"
            >
              Jouer
            </button>
          ) : (
            <button
              onClick={() => setStep(step + 1)}
              className="flex items-center gap-1 text-sm text-atlas-navy hover:text-atlas-ocre"
            >
              Suivant <ArrowRight size={16} />
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
