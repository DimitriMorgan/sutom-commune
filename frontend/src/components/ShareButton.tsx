import { useState } from 'react';
import { Check, Share2 } from 'lucide-react';
import { track } from '../analytics';

type Props = { text: string; puzzleNumber: number };

export function ShareButton({ text, puzzleNumber }: Props) {
  const [copied, setCopied] = useState(false);

  const handleClick = async () => {
    track({ type: 'share_clicked', puzzleNumber });

    if (typeof navigator.share === 'function') {
      try {
        await navigator.share({ text });
        return;
      } catch {
        // L'utilisateur a peut-être annulé — on retombe sur le presse-papier.
      }
    }

    try {
      await navigator.clipboard.writeText(text);
      setCopied(true);
      window.setTimeout(() => setCopied(false), 2000);
    } catch {
      // Pas de clipboard (HTTP non sécurisé ?) — on affiche le texte brut.
      window.prompt('Copiez votre grille :', text);
    }
  };

  return (
    <button
      onClick={handleClick}
      className="flex items-center justify-center gap-2 bg-atlas-navy text-atlas-paper px-4 py-2.5 rounded-lg hover:bg-atlas-ocre transition-colors w-full"
    >
      {copied ? <Check size={18} /> : <Share2 size={18} />}
      <span>{copied ? 'Copié dans le presse-papier' : 'Partager ma grille'}</span>
    </button>
  );
}
