/**
 * Stub analytics pour le MVP. À l'étape 11 on connectera ce module à `/api/analytics/events`.
 */
export type AnalyticsEvent =
  | { type: 'puzzle_started'; puzzleNumber: number }
  | { type: 'guess_submitted'; puzzleNumber: number; attemptIndex: number; isCorrect: boolean }
  | { type: 'puzzle_won'; puzzleNumber: number; attempts: number }
  | { type: 'puzzle_lost'; puzzleNumber: number }
  | { type: 'share_clicked'; puzzleNumber: number };

export function track(event: AnalyticsEvent): void {
  // No-op au MVP. Branchable sur /api/analytics/events sans changer le code appelant.
  if (import.meta.env.DEV) {
    console.debug('[analytics]', event);
  }
}
