import { lazy, Suspense, useEffect, useRef, useState } from 'react';
import type { GuessPathStep, PuzzleSolution } from '../api/types';

/**
 * Carte centrée sur la France métropolitaine.
 * MapLibre GL JS est lazy-loadé (~200 ko gzip) seulement quand le composant
 * entre dans le viewport — gain Lighthouse significatif sur la home.
 */
type Props = {
  guessPath: GuessPathStep[];
  solution: PuzzleSolution | null;
  /** Override les dimensions du conteneur. Par défaut : haute sur desktop, modérée sur mobile. */
  className?: string;
};

const InnerMap = lazy(() => import('./CommuneMapInner'));

// Sur lg+ on borne par le viewport (sticky-friendly) plutôt qu'une hauteur fixe :
// avec la nouvelle largeur 6xl + ratio 1.7fr, la carte gagne déjà ~2× de surface
// par rapport à l'ancienne. min-h garde une présence minimum, max-h évite que ça
// dépasse l'écran et casse le comportement sticky.
const DEFAULT_CLASS =
  'w-full h-72 sm:h-80 lg:h-[calc(100vh-7rem)] lg:min-h-[760px] lg:max-h-[920px] rounded-lg overflow-hidden border border-atlas-ocre/30 bg-atlas-paper/60';

export function CommuneMap({ className, ...props }: Props) {
  const sentinelRef = useRef<HTMLDivElement>(null);
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    if (!sentinelRef.current) return;
    if (typeof IntersectionObserver === 'undefined') {
      setVisible(true);
      return;
    }
    const obs = new IntersectionObserver(
      (entries) => {
        if (entries.some((e) => e.isIntersecting)) {
          setVisible(true);
          obs.disconnect();
        }
      },
      { rootMargin: '200px' },
    );
    obs.observe(sentinelRef.current);
    return () => obs.disconnect();
  }, []);

  return (
    <div
      ref={sentinelRef}
      data-testid="commune-map"
      className={className ?? DEFAULT_CLASS}
    >
      {visible ? (
        <Suspense fallback={<MapPlaceholder />}>
          <InnerMap {...props} />
        </Suspense>
      ) : (
        <MapPlaceholder />
      )}
    </div>
  );
}

function MapPlaceholder() {
  return (
    <div className="w-full h-full flex items-center justify-center text-sm text-atlas-ink/40">
      Carte de France
    </div>
  );
}
