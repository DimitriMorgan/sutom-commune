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
};

const InnerMap = lazy(() => import('./CommuneMapInner'));

export function CommuneMap(props: Props) {
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
      className="w-full h-64 rounded-lg overflow-hidden border border-atlas-ocre/30 bg-atlas-paper/60"
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
