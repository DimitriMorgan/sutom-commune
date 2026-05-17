import { test, expect } from '@playwright/test';

/**
 * Golden path : ouverture du jeu → soumission d'un essai → feedback distance/bearing.
 * Pré-requis : backend up + fixtures chargées (cf. README).
 */
test.describe('Sutom-Commune golden path', () => {
  test.beforeEach(async ({ page }) => {
    // S'assure que le tutoriel n'apparaîtra pas (fresh storage) puis on lui dit qu'il a été vu.
    await page.addInitScript(() => {
      window.localStorage.setItem('sutomCommune.tutorial_seen', '1');
    });
  });

  test('charge la page du jour avec région + département', async ({ page }) => {
    await page.goto('/');
    // Le titre est affiché
    await expect(page.getByRole('heading', { name: /commune se cache ici/i })).toBeVisible();
    // La région et le département de la cible sont visibles
    await expect(page.getByText(/Région/)).toBeVisible();
    await expect(page.getByText(/Département/)).toBeVisible();
    // 6 essais restants
    await expect(page.getByText(/6 essais restants/)).toBeVisible();
  });

  test('autocomplete fonctionne et soumet un essai', async ({ page }) => {
    await page.goto('/');
    const input = page.getByPlaceholder(/Honfleur/i);
    await input.fill('paris');
    // attend les résultats de l'autocomplete (debounce 200ms + roundtrip réseau)
    await expect(page.getByRole('option', { name: /Paris/i }).first()).toBeVisible({ timeout: 3000 });
    await input.press('Enter');

    // Une ligne de résultat apparaît avec la distance
    await expect(page.getByTestId('guess-row')).toBeVisible();
    await expect(page.getByText(/km/)).toBeVisible();
  });

  test("anti-cheat : la cible n'est pas révélée tant que la partie n'est pas finie", async ({ page }) => {
    await page.goto('/');

    // Vérifie au niveau API que /api/puzzle/today ne contient ni latitude ni population
    const apiResponse = await page.request.get('http://localhost:8081/api/puzzle/today');
    expect(apiResponse.ok()).toBeTruthy();
    const body = await apiResponse.text();
    expect(body).not.toContain('"latitude"');
    expect(body).not.toContain('"longitude"');
    expect(body).not.toContain('"population"');
    expect(body).not.toContain('"name"');
    expect(body).not.toContain('"codeInsee"');
    expect(body).not.toContain('"patrimoine"');
  });

  test('la carte est rendue (DOM MapLibre présent)', async ({ page }) => {
    await page.goto('/');
    await expect(page.getByTestId('commune-map')).toBeVisible();
    // Pas de pin cible visible tant que la partie n'est pas terminée
    await expect(page.getByTestId('target-pin')).toHaveCount(0);
  });

  test('navigue vers /archive et liste les énigmes passées', async ({ page }) => {
    await page.goto('/archive');
    await expect(page.getByRole('heading', { name: 'Archive' })).toBeVisible();
    // Au moins une entrée (les fixtures dev créent 30 jours)
    const links = page.locator('ul a[href^="/archive/"]');
    await expect(links.first()).toBeVisible({ timeout: 3000 });
  });

  test('le tutoriel apparaît à la première visite', async ({ page }) => {
    // Override le init script pour ne PAS marquer le tutoriel comme vu
    await page.addInitScript(() => {
      window.localStorage.removeItem('sutomCommune.tutorial_seen');
    });
    await page.goto('/');
    await expect(page.getByRole('heading', { name: /commune mystère/i })).toBeVisible();
  });
});
