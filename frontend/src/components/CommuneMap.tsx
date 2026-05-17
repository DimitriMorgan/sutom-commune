import { useEffect, useRef } from 'react';
import maplibregl, { type Map as MapLibreMap, type Marker } from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import type { GuessPathStep, PuzzleSolution } from '../api/types';

/**
 * Carte centrée sur la France métropolitaine.
 * - Pins gris-bleu pour chaque essai
 * - Pin doré + trait pour la cible UNIQUEMENT si `solution` est fourni (game over)
 * - Tuiles raster OSM (libres, ODbL — attribution conservée par MapLibre par défaut)
 */
type Props = {
  guessPath: GuessPathStep[];
  solution: PuzzleSolution | null;
};

const STYLE = {
  version: 8,
  sources: {
    osm: {
      type: 'raster',
      tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
      tileSize: 256,
      attribution: '© OpenStreetMap contributors',
      maxzoom: 19,
    },
  },
  layers: [{ id: 'osm', type: 'raster', source: 'osm' }],
} as const;

export function CommuneMap({ guessPath, solution }: Props) {
  const containerRef = useRef<HTMLDivElement>(null);
  const mapRef = useRef<MapLibreMap | null>(null);
  const markersRef = useRef<Marker[]>([]);

  useEffect(() => {
    if (!containerRef.current || mapRef.current) return;
    const map = new maplibregl.Map({
      container: containerRef.current,
      style: STYLE as unknown as maplibregl.StyleSpecification,
      center: [2.5, 46.5],
      zoom: 4.5,
      attributionControl: { compact: true },
    });
    mapRef.current = map;
    return () => {
      map.remove();
      mapRef.current = null;
    };
  }, []);

  // Met à jour les markers à chaque changement de l'historique ou de la cible.
  useEffect(() => {
    const map = mapRef.current;
    if (!map) return;

    markersRef.current.forEach((m) => m.remove());
    markersRef.current = [];

    guessPath.forEach((step) => {
      const el = document.createElement('div');
      el.className = `w-3 h-3 rounded-full border-2 ${
        step.isCorrect
          ? 'bg-tier-green border-white'
          : 'bg-atlas-navy/70 border-atlas-paper'
      }`;
      const marker = new maplibregl.Marker({ element: el })
        .setLngLat([step.longitude, step.latitude])
        .setPopup(new maplibregl.Popup({ offset: 12 }).setText(step.name))
        .addTo(map);
      markersRef.current.push(marker);
    });

    if (solution) {
      const el = document.createElement('div');
      el.className = 'w-4 h-4 rounded-full bg-atlas-ocre border-2 border-white shadow-lg';
      el.setAttribute('data-testid', 'target-pin');
      const marker = new maplibregl.Marker({ element: el })
        .setLngLat([solution.longitude, solution.latitude])
        .setPopup(new maplibregl.Popup({ offset: 14 }).setText(solution.name))
        .addTo(map);
      markersRef.current.push(marker);

      // Recentre et zoom pour englober cible + dernier essai
      const bounds = new maplibregl.LngLatBounds(
        [solution.longitude, solution.latitude],
        [solution.longitude, solution.latitude],
      );
      guessPath.forEach((s) => bounds.extend([s.longitude, s.latitude]));
      map.fitBounds(bounds, { padding: 60, maxZoom: 10, duration: 600 });
    }
  }, [guessPath, solution]);

  return (
    <div
      ref={containerRef}
      data-testid="commune-map"
      className="w-full h-64 rounded-lg overflow-hidden border border-atlas-ocre/30"
    />
  );
}
