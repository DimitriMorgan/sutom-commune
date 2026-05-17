# Sutom-Commune — reste à faire & pistes d'amélioration

État au 2026-05-18, 8 commits, app jouable end-to-end (http://localhost:8081, http://localhost:5178).
**Tests : 97 PHPUnit / 12 Vitest / tsc strict, tous verts.**

---

## 🎯 Priorité — finir la DoD du brief

| # | Item | Détail / commande |
|---|---|---|
| 1 | **PHPStan level 8 sans erreur** | `make lint` jamais exécuté. À lancer : `docker compose exec backend php -d memory_limit=512M vendor/bin/phpstan analyse`. Corriger toutes les remontées (probablement quelques `mixed` à typer dans Controllers + DataFixtures). |
| 2 | **PHP CS Fixer sans diff** | `make cs-fix` jamais exécuté. À faire avant chaque commit (ou en pre-commit hook). |
| 3 | **ESLint sans warning** | `make lint` côté front non testé. Lancer `docker compose exec frontend sh -lc 'npx eslint .'`. |
| 4 | **Lighthouse mobile ≥ 90** | Jamais audité. Test : `npx lighthouse http://localhost:8081 --view --form-factor=mobile`. Cibles probables si manque : LCP (carte lazy déjà OK), CLS (vérifier images), TBT (bundle size — code splitting fait), a11y (skip-link fait, vérifier contraste tier-yellow). |
| 5 | **19 deprecations PHPUnit** | Suite à PHP 8.4 / PHPUnit 12. Faire tourner `phpunit --display-deprecations` et corriger. Probablement des `Doctrine\DBAL\*` ou `Symfony\Bridge\*` à mettre à jour. |
| 6 | **GuessInput tests Vitest** | Brief demande `GuessInput.test.tsx` : autocomplete + debounce 200ms + abort previous request. Pas écrit. Mocker `api.searchCommunes` avec `vi.useFakeTimers()`. |
| 7 | **CommuneMap tests Vitest** | Brief demande : "pin target masqué tant que gameOver false". Lazy-load complique le test → mock l'import dynamique ou test le wrapper directement. |
| 8 | **TodayPage flow complet** | Brief demande un test fonctionnel React du flow complet. À écrire avec MSW pour mocker l'API. |
| 9 | **Tests pour endpoints non testés** | AnalyticsController, OgImageController, SitemapController, PuzzleArchiveController, StatsTodayController — aucun test fonctionnel. Couverture cible brief = 80% sur Service/ et Controller/. |
| 10 | **Playwright runnable** | `sudo apt install libnss3 libnspr4 libatk1.0-0 libcups2 libdrm2 libxkbcommon0 libxcomposite1 libxdamage1 libxfixes3 libxrandr2 libgbm1 libpango-1.0-0 libcairo2 libasound2` puis `cd e2e && npx playwright install chromium && npm test`. |
| 11 | **Enrichissement étendu** | 30/295 communes enrichies. Brief vise 500. Compléter `backend/src/DataFixtures/data/communes-enrichment.json` — 2-3h de travail manuel à partir de Wikipedia. |

---

## 🔍 SEO — pistes pour passer de "très bien" à "exceptionnel"

| # | Item | Impact | Effort |
|---|---|---|---|
| S1 | **Pre-rendering au build** (`vite-plugin-prerender` ou `react-snap`) | 🔥 Gros gain : les pages /, /comment-jouer, /a-propos, /mentions-legales servies en HTML statique avec contenu hydraté. Bing/DDG/non-Googlebot voient tout. | Moyen — bloqué par même problème que Playwright (Chromium headless). Solution : pré-render dans la CI où apt-get marche. |
| S2 | **SSR Symfony pour /archive/{date}** | Gros gain longue traîne. Servir le HTML pré-renderé avec nom commune + dpt + photo Wikipedia pour les bots, puis hydrater React. | Moyen — un controller Twig + détection User-Agent OU servir aux deux et hydrater. |
| S3 | **Page individuelle par commune** `/commune/{slug}` | 🔥 295 pages longue traîne ("Honfleur", "Strasbourg", etc.) avec description, distance moyenne d'erreur, historique des fois où ça a été l'énigme, lien Wikipedia. | Moyen — controller + page React + sitemap update. |
| S4 | **OG image PNG (vs SVG actuel)** | Quelques crawlers/messageries ne previewent pas le SVG (Slack par exemple). | Moyen — convertir via Imagick ou GD côté Symfony, ou utiliser Satori (Node) en build. |
| S5 | **FAQ structurée Schema.org** | Sitelinks Google sur la home. | Faible — ajouter une section FAQ sur HowToPlay + `FAQPage` JSON-LD. |
| S6 | **Web Vitals tracking** | Mesurer LCP/CLS/INP réels et envoyer à analytics. | Faible — `web-vitals` npm + endpoint analytics. |
| S7 | **Sitemap index** | Pas critique (< 50k URLs aujourd'hui) mais propre si on génère beaucoup de pages /commune/* (S3). | Faible. |
| S8 | **Pages éducatives** ("Top 10 villes les plus longues à deviner", "Géographie de la Normandie en 5 villes") | 🔥 Longue traîne forte + autorité. | Élevé — contenu rédactionnel. |
| S9 | **Backlinks** | Inscription annuaires Wordle-likes (Worldle community, FR puzzle hubs, r/france). | Variable — hors tech. |

---

## 🏗️ Qualité code / production-readiness

### Backend
- [ ] **ext-intl** dans `docker/backend/Dockerfile` (dev). Actuellement intl seulement en prod. J'ai contourné dans `OgImageController::formatDateFr` (mapping manuel), mais si on ajoute `Symfony\Component\Intl\Locales` ou similaire ça cassera.
- [ ] **Rate limiting** sur `/api/puzzle/guess` (anti-spam) et `/api/communes/search` (anti-DDoS). Symfony Rate Limiter bundle.
- [ ] **Validation côté Symfony Validator** sur les bodies JSON (actuellement validation manuelle dans les controllers).
- [ ] **Migration purge analytics > 90 jours** (RGPD) : commande cron `app:analytics:purge`.
- [ ] **Pas de healthcheck Docker** sur backend/frontend (uniquement db). Ajouter dans compose.

### Frontend
- [ ] **Service Worker** pour vraie PWA offline (manifest fait, mais sw absent).
- [ ] **`<picture>` + WebP/AVIF** quand on ajoutera des images (Wikipedia thumbnails dans ResultModal par ex).
- [ ] **Lazy load `lucide-react` icons** par tree-shaking (déjà imports nommés, devrait marcher mais à vérifier dans le bundle).
- [ ] **Error boundaries** React (actuellement un crash blanc).

### Infrastructure
- [ ] **Tile cache OSM** : la policy OSM demande de ne pas hotlink. Setup un proxy nginx avec cache 7 jours vers `tile.openstreetmap.org`. Sinon risque de blocage.
- [ ] **CSP** (Content-Security-Policy) en prod.
- [ ] **HSTS** en prod (via Caddy).
- [ ] **`scripts/deploy.sh`** à porter depuis dim89 (rsync + docker compose -f compose.prod.yaml).
- [ ] **`scripts/backup.sh`** : pg_dump quotidien.
- [ ] **Cron `app:puzzle:select-today`** à 00h05 Europe/Paris en prod (`crontab -e` sur le VPS).

### CI/CD
- [ ] **.github/workflows/ci.yml** : phpunit + phpstan + cs-fix --dry-run + vitest + tsc + playwright (avec apt-get sur ubuntu-latest runner).
- [ ] **Pre-commit hook** local (husky ou git hooks) qui lance `make cs-fix` + `make lint` rapide.

### Doc
- [ ] **README** à mettre à jour avec les endpoints récents (`/api/og/...`, `/sitemap.xml`, `/api/analytics/events`) + section déploiement prod complète.
- [ ] **CLAUDE.md** ou **AGENTS.md** : récap des conventions du projet pour les futures sessions Claude (palette atlas vintage, anti-cheat ne JAMAIS le casser, sessionHash règle, etc.).

---

## 💡 Produit / fonctionnalités V2

| # | Item | Pourquoi |
|---|---|---|
| P1 | **Champ `theme` exploité** | DailyPuzzle a `theme` nullable mais jamais set. Brief mentionne "Bord de mer", "Stations thermales". Ajouter un type `WeeklyTheme` + un sélecteur thématique au selector. |
| P2 | **Dimanche = "spécial chef-lieu"** | Brief le demande, actuellement même règle que mer/jeu. Ajouter dans `DailyPuzzleSelector::candidatesFor` une condition `is_chef_lieu` (à dériver des codes département où `code_insee === department_code . '001'` ou via une whitelist). |
| P3 | **Mode défi entre amis** (URL partagée) | Brief refuse "multijoueur", mais une URL `?puzzle=20260518` pour rejouer une énigme spécifique entre amis sans casser le streak du jour = compromis. |
| P4 | **Stats du jour visibles** | `/api/stats/today` existe mais aucun composant frontend ne l'affiche. Ajouter dans ResultModal "X joueurs ont déjà résolu cette énigme (moyenne Y essais)". |
| P5 | **Mode dark toggle explicite** | Actuellement uniquement `prefers-color-scheme`. Ajouter un toggle pour override + sauver en localStorage. |
| P6 | **EN locale** | i18n.ts prêt en structure mais FR only. Ajouter `en-US` pour ouvrir à diaspora + traduction des communes (juste les UI strings ; les noms communes restent FR). |
| P7 | **Notification quand la nouvelle énigme tombe** | Brief refuse "notifications push" — alternative : badge `<title>` qui flash "Nouvelle énigme !" quand minuit Paris passe. |

---

## 📌 Comment reprendre demain

1. **Vérifier que l'env tourne** :
   ```bash
   cd ~/projects/sutom-commune
   docker compose --env-file .env ps
   # Si down : docker compose --env-file .env up -d
   curl -s http://localhost:8081/api/health
   ```

2. **Lancer lint + qualité** (à fixer en premier) :
   ```bash
   make lint   # phpstan + eslint
   make cs-fix # php-cs-fixer
   ```
   → travailler les erreurs jusqu'à zéro.

3. **Lancer Lighthouse** (priorité brief) :
   ```bash
   npx lighthouse http://localhost:8081 --form-factor=mobile --view
   ```
   → traiter les items qui font tomber sous 90.

4. **Choisir un axe** :
   - **DoD finition** : items 1-10 ci-dessus
   - **SEO max** : S1 (pre-rendering) ou S3 (pages /commune/{slug})
   - **Prod ready** : tile cache OSM + scripts deploy + CI

5. **Référence rapide URLs locales** :
   - Frontend : http://localhost:5178
   - API : http://localhost:8081/api
   - API docs : http://localhost:8081/api/docs
   - Sitemap : http://localhost:8081/sitemap.xml
   - OG image : http://localhost:8081/api/og/puzzle/2026-05-18.svg

6. **Git** : 8 commits propres, dernier = `2a73949 step SEO`. Travailler par branche (`git checkout -b feat/lighthouse`) puis merge.

---

## 🗂️ Récap fichiers ajoutés cette session

```
backend/src/
├── ApiResource/HealthResource.php
├── Command/{ImportCommunes,SelectTodayPuzzle}Command.php
├── Controller/{CommuneSearch,PuzzleToday,PuzzleGuess,PuzzleArchive,StatsToday,Analytics,OgImage,Sitemap}Controller.php
├── DataFixtures/{Commune,DailyPuzzle}Fixtures.php + data/{communes-dev-sample,communes-enrichment}.json
├── Dto/{Health,CommuneSearchResult,PuzzleToday,PuzzleGuess,GuessPathStep,PuzzleSolution}Output.php
├── Entity/{Commune,DailyPuzzle,GuessEvent,AnalyticsEvent}.php
├── Enum/{Climate,Difficulty}.php
├── Repository/* (4 repos)
├── Service/{Clock,SessionHasher,NameNormalizer,RegionsAndDepartments,
│            CommuneImporter,DistanceCalculator,ProximityTierResolver,HintProvider,
│            ShareGridGenerator,DailyPuzzleSelector,GuessOrchestrator,InvalidGuessException,ImportSummary}.php
├── State/HealthProvider.php
└── ValueObject/{Coordinates,DistanceAndBearing}.php

backend/migrations/Version2026051{7210000,8000000,8100000}.php

backend/tests/
├── Controller/{CommuneSearch,PuzzleAntiCheat,PuzzleGuess}ControllerTest.php
├── Enum/DifficultyTest.php
├── Service/{NameNormalizer,CommuneImporter,DistanceCalculator,ProximityTier,HintProvider,
│           ShareGrid,DailyPuzzleSelector}Test.php
└── data/communes-sample-10.json

frontend/src/
├── App.tsx (router lazy)
├── analytics.ts (batch + sendBeacon)
├── i18n.ts, seo.ts (étendu)
├── api/{client,types}.ts
├── components/{AppLayout,AppHeader,AppFooter,StreakBadge,HowToPlayModal,
│              GuessInput,GuessRow,HintsPanel,CommuneMap,CommuneMapInner,
│              GameBoard,ResultModal,ShareButton}.tsx
├── pages/{Today,Archive,ArchiveDetail,HowToPlay,About,Legal}Page.tsx
├── storage/state.ts (+ tests)
└── test/setup.ts

frontend/public/{favicon.svg, og-default.svg, manifest.webmanifest, robots.txt}

e2e/{playwright.config.ts, tests/golden-path.spec.ts}
```
