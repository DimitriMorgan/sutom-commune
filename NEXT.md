# Sutom-Commune — reste à faire & pistes d'amélioration

État au 2026-05-19. App jouable end-to-end (http://localhost:8081, http://localhost:5178).
**Tests : 121 PHPUnit / 22 Vitest / tsc strict / phpstan L8 / cs-fix / eslint — tous verts, 0 deprecation.**

---

## ✅ Fait cette session

### Anti-spoil archive + UX
- `PuzzleArchiveController` : liste limitée à 3 par défaut (paramètre `?limit=` borné à 60), n'expose plus `name`/`codeInsee` — uniquement région et département. Rejouer une archive ne donne aucun indice supplémentaire vs ce que voit un nouveau joueur.
- `ArchivePage` : nouvelle UI « Commune mystère + Région · Département (code) », SEO/JSON-LD ajustés (pas de noms de communes dans les meta-descriptions).
- `HintsPanel` : la région s'affiche d'entrée, le département est révélé après la 1ʳᵉ tentative (placeholder « — révélé au 1ᵉʳ essai — »).
- `GameBoard` : grille 2 colonnes à partir de `lg:` — énigme à gauche, carte à droite et **sticky** ; mobile garde l'ordre actuel (carte en bas).
- `CommuneMap` : taille augmentée (`h-72 sm:h-80 lg:h-[640px]`), accepte un `className` override.

### Qualité de code
- `php-cs-fixer` : 0 diff (16 fichiers normalisés au passage).
- `phpstan` level 8 : 0 erreur (7 résolues : `RegionsAndDepartments` `@var` redondant, `PuzzleTodayController::$clock` mort, `SelectTodayPuzzleCommand` `?->X ?? Y`, `ImportCommunesCommand` `@var mixed`, `CommuneSearchControllerTest` return type, `DailyPuzzleSelectorTest` assertion tautologique).
- `eslint` : 0 warning (`PuzzleSolution` import mort retiré).
- **19 deprecations PHPUnit → 0** : `DistanceCalculator::bearingToCompass` utilise désormais `fmod()` au lieu du `%` sur float.

### Couverture tests
- **PHPUnit** : +24 tests fonctionnels (121 au total, 543 assertions).
  - `AnalyticsControllerTest` : whitelist de types, filtrage des events invalides, anonymisation IP via SessionHasher.
  - `OgImageControllerTest` : nom révélé pour les archives, masqué pour le jour, cache 30j vs 1h, 404 pour énigme inconnue.
  - `SitemapControllerTest` : XML bien formé, pages statiques + entrées d'archive, cache 1h.
  - `StatsTodayControllerTest` : 404 sans énigme, sessions distinctes comptées, pas de fuite cible, cache 60s.
  - `PuzzleArchiveControllerTest` : limit=3 par défaut, anti-spoil (pas de name/codeInsee), tri DESC, validation dates, anti-cheat detail.
- **Vitest** : +8 tests (22 au total).
  - `GuessInput.test.tsx` : debounce 200ms, ignore les requêtes < 2 chars, abort de la requête précédente quand on continue à taper.
  - `CommuneMap.test.tsx` : guess pins visibles pendant la partie, target pin masqué tant que `solution=null`, dévoilé en gameOver.
  - `TodayPage.test.tsx` : flow complet — load → région visible / dept masqué → essai raté → dept dévoilé + indice → essai gagnant → modal + pin cible. `MapInner` stubbé pour éviter MapLibre/WebGL.

---

## 🎯 Reste à faire — DoD

| # | Item | Détail / commande |
|---|---|---|
| 1 | **Lighthouse mobile ≥ 90** | Jamais audité. `lighthouse` pas installé : `npx lighthouse http://localhost:8081 --view --form-factor=mobile` depuis l'hôte. Cibles probables si manque : LCP (carte lazy déjà OK), CLS (vérifier images), TBT (code splitting fait), a11y (skip-link OK, vérifier contraste tier-yellow). |
| 2 | **Playwright runnable** | `sudo apt install libnss3 libnspr4 libatk1.0-0 libcups2 libdrm2 libxkbcommon0 libxcomposite1 libxdamage1 libxfixes3 libxrandr2 libgbm1 libpango-1.0-0 libcairo2 libasound2` puis `cd e2e && npx playwright install chromium && npm test`. |
| 3 | **Enrichissement étendu** | 30/295 communes enrichies. Brief vise 500. Compléter `backend/src/DataFixtures/data/communes-enrichment.json` — 2-3h de travail manuel à partir de Wikipedia. |

---

## 🔍 SEO — pistes pour passer de "très bien" à "exceptionnel"

> **Session 2026-06-02 — S3, S5, S6, S7 livrés** (quality gate vert : 131 PHPUnit / 25 Vitest / tsc / phpstan L8 / cs-fix / eslint). Détail en bas de section.

| # | Item | Impact | Effort | État |
|---|---|---|---|---|
| S1 | **Pre-rendering au build** (`vite-plugin-prerender` ou `react-snap`) | 🔥 Gros gain : les pages /, /comment-jouer, /a-propos, /mentions-legales servies en HTML statique avec contenu hydraté. Bing/DDG/non-Googlebot voient tout. | Moyen — bloqué par même problème que Playwright (Chromium headless). Solution : pré-render dans la CI où apt-get marche. | ⏳ TODO (bloqué Chromium) |
| S2 | **SSR Symfony pour /archive/{date}** | Gros gain longue traîne. Servir le HTML pré-renderé avec nom commune + dpt + photo Wikipedia pour les bots, puis hydrater React. | Moyen — un controller Twig + détection User-Agent OU servir aux deux et hydrater. | ⏳ TODO |
| S3 | **Page individuelle par commune** `/commune/{slug}` | 🔥 295 pages longue traîne ("Honfleur", "Strasbourg", etc.) avec description, climat, patrimoine, historique des fois où ça a été l'énigme, lien Wikipedia. | Moyen — controller + page React + sitemap update. | ✅ FAIT |
| S4 | **OG image PNG (vs SVG actuel)** | Quelques crawlers/messageries ne previewent pas le SVG (Slack par exemple). | Moyen — convertir via Imagick ou GD côté Symfony, ou utiliser Satori (Node) en build. | ⛔ BLOQUÉ — ni `imagick` ni `gd` dans le conteneur. Rebuild image Docker avec librsvg, ou rendu PNG via Satori (Node) au build. |
| S5 | **FAQ structurée Schema.org** | Sitelinks Google sur la home. | Faible — ajouter une section FAQ sur HowToPlay + `FAQPage` JSON-LD. | ✅ FAIT |
| S6 | **Web Vitals tracking** | Mesurer LCP/CLS/INP réels et envoyer à analytics. | Faible — `web-vitals` npm + endpoint analytics. | ✅ FAIT |
| S7 | **Sitemap index** | Pas critique (< 50k URLs aujourd'hui) mais propre si on génère beaucoup de pages /commune/* (S3). | Faible. | ✅ FAIT |
| S8 | **Pages éducatives** ("Top 10 villes les plus longues à deviner", "Géographie de la Normandie en 5 villes") | 🔥 Longue traîne forte + autorité. | Élevé — contenu rédactionnel. | ⏳ TODO |
| S9 | **Backlinks** | Inscription annuaires Wordle-likes (Worldle community, FR puzzle hubs, r/france). | Variable — hors tech. | ⏳ TODO |

### Détail livré cette session (2026-06-02)

- **S3 — `/commune/{slug}`** : `CommuneDetailController` (`GET /api/commune/{slug}`, **singulier** pour ne pas collisionner avec `/api/communes/search`), DTO `CommuneDetail`, `CommuneRepository::findOneBySlug` + `findPlayableForSitemap`, `DailyPuzzleRepository::findPastAppearancesForCommune` (**anti-spoil : `puzzleDate < today` strict — l'énigme du jour ne fuite jamais**). 404 si slug inconnu **ou commune non jouable**. Front : `CommunePage.tsx` (fiche encyclopédique + JSON-LD `City`/`GeoCoordinates`), route `commune/:slug`, `api.getCommune`. 295 pages indexables.
- **S5 — FAQ** : section FAQ visible + `FAQPage` JSON-LD (array `[HowTo, FAQPage]`) sur `/comment-jouer`.
- **S6 — Web Vitals** : dep `web-vitals`, `webVitals.ts` (LCP/CLS/INP/TTFB/FCP) → `analytics.reportWebVital` (type `web_vitals` whitelisté backend), init **PROD only** via import dynamique dans `main.tsx`.
- **S7 — Sitemap index** : `/sitemap.xml` devient un `<sitemapindex>` → `/sitemap-pages.xml` (statiques + archive) + `/sitemap-communes.xml` (295 communes). **Nginx** (`default.conf` + `prod.conf`) : route `~ ^/sitemap(-pages|-communes)?\.xml$` vers Symfony.

> ⚠️ **Note WSL2/Docker** : éditer un fichier bind-monté (ex. `docker/nginx/*.conf`) casse le mount (l'inode est remplacé). Après modif : `docker compose --env-file .env up -d --force-recreate nginx` (un simple `restart` échoue avec "no such file or directory").

---

## 🏗️ Qualité code / production-readiness

### Backend
- [ ] **ext-intl** dans `docker/backend/Dockerfile` (dev). Actuellement intl seulement en prod. Contourné dans `OgImageController::formatDateFr` (mapping manuel), mais si on ajoute `Symfony\Component\Intl\Locales` ou similaire ça cassera.
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
- [ ] **CLAUDE.md** ou **AGENTS.md** : récap des conventions du projet pour les futures sessions Claude (palette atlas vintage, anti-cheat ne JAMAIS le casser, sessionHash règle, archive anti-spoil règle, etc.).

---

## 💡 Produit / fonctionnalités V2

| # | Item | Pourquoi |
|---|---|---|
| P1 | **Champ `theme` exploité** | DailyPuzzle a `theme` nullable mais jamais set. Brief mentionne "Bord de mer", "Stations thermales". Ajouter un type `WeeklyTheme` + un sélecteur thématique au selector. |
| P2 | **Dimanche = "spécial chef-lieu"** | Brief le demande, actuellement même règle que mer/jeu. Ajouter dans `DailyPuzzleSelector::candidatesFor` une condition `is_chef_lieu` (à dériver des codes département où `code_insee === department_code . '001'` ou via une whitelist). |
| P3 | **Mode défi entre amis** (URL partagée) | Brief refuse "multijoueur", mais une URL `?puzzle=20260518` pour rejouer une énigme spécifique entre amis sans casser le streak du jour = compromis. |
| P4 | **Stats du jour visibles** | `/api/stats/today` existe et est désormais testé, mais aucun composant frontend ne l'affiche. Ajouter dans ResultModal "X joueurs ont déjà résolu cette énigme (moyenne Y essais)". |
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

2. **Quality gate complet (devrait être tout vert)** :
   ```bash
   docker compose --env-file .env exec backend sh -lc \
     'vendor/bin/phpunit && \
      php -d memory_limit=512M vendor/bin/phpstan analyse --no-progress && \
      php -d memory_limit=512M vendor/bin/php-cs-fixer fix --dry-run'

   docker compose --env-file .env exec frontend sh -lc \
     'cd /app && npx tsc --noEmit && npx eslint src/ && npx vitest run'
   ```

3. **Lancer Lighthouse** (prochaine priorité DoD) :
   ```bash
   npx lighthouse http://localhost:8081 --form-factor=mobile --view
   ```

4. **Choisir un axe** :
   - **DoD finition** : Lighthouse, Playwright (sudo apt), enrichissement communes
   - **SEO max** : S1 (pre-rendering) ou S3 (pages /commune/{slug})
   - **Prod ready** : tile cache OSM + scripts deploy + CI

5. **Référence rapide URLs locales** :
   - Frontend : http://localhost:5178
   - API : http://localhost:8081/api
   - API docs : http://localhost:8081/api/docs
   - Sitemap : http://localhost:8081/sitemap.xml
   - OG image : http://localhost:8081/api/og/puzzle/2026-05-18.svg
   - Archive (3 dernières, anti-spoil) : http://localhost:8081/api/puzzle/archive
