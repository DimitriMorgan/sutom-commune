import { onCLS, onFCP, onINP, onLCP, onTTFB, type Metric } from 'web-vitals';
import { reportWebVital } from './analytics';

/**
 * Mesure les Core Web Vitals réels (terrain) et les pousse vers l'analytics maison.
 * À appeler une fois au démarrage. Chaque métrique est rapportée avec son `rating`
 * (good / needs-improvement / poor) et le chemin de la page, pour suivre LCP/CLS/INP
 * par route. Les valeurs définitives arrivent au pagehide (flush beacon côté analytics).
 */
export function initWebVitals(): void {
  const report = (metric: Metric): void => {
    reportWebVital({
      name: metric.name,
      value: metric.value,
      rating: metric.rating,
      id: metric.id,
      path: window.location.pathname,
    });
  };

  onLCP(report);
  onCLS(report);
  onINP(report);
  onTTFB(report);
  onFCP(report);
}
