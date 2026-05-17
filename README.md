# Sutom-Commune

Jeu quotidien type Wordle pour deviner une commune française mystère en 6 essais. Combine la distance + flèche directionnelle (style Worldle) et les indices contextuels progressifs (style Sutom).

> Données : geo.api.gouv.fr (Etalab). Cartographie : © OpenStreetMap contributors.

## Stack

- Backend : PHP 8.4, Symfony 8, API Platform 4.3, Doctrine ORM 3.5, PostgreSQL 17 (avec extension `pg_trgm`)
- Frontend : React 19, TypeScript, Vite 7, Tailwind 4, react-router-dom 7, lucide-react
- Cartographie : MapLibre GL JS + tuiles OpenStreetMap
- Infra : Docker Compose (db, backend, frontend, nginx)
- Qualité : PHPUnit 12, PHPStan max, PHP CS Fixer, Vitest, ESLint, Prettier

## Prérequis

- Docker + Docker Compose
- Make
- Optionnel hors Docker : PHP 8.4+, Composer, Node 22+

## Installation

```bash
cp .env.example .env
make build
make up
make migrate
make communes-import   # import des ~34 945 communes depuis geo.api.gouv.fr (~5 min)
make fixtures          # données de dev (sous-ensemble + 30 énigmes seed)
```

Commande pratique pour lancer backend + frontend + DB + proxy en foreground :

```bash
make dev
```

## URLs

- Frontend : http://localhost:5178
- API via proxy : http://localhost:8081/api
- API Platform docs : http://localhost:8081/api/docs
- Healthcheck : http://localhost:8081/api/health

Ports choisis pour cohabiter avec les autres projets (dim89 sur 5174/8080, climalia sur 5175, cepagle sur 5176/8090, boucle sur 5177/8083). Overridables via `FRONTEND_PORT` et `HTTP_PORT` dans `.env`.

## Commandes Make

```bash
make up                 # démarre tous les services en arrière-plan
make dev                # démarre db + backend + frontend + nginx avec logs
make dev-local          # lance l'API Symfony locale + le front Vite (hors Docker)
make down               # arrête les services
make build              # build les images
make logs               # logs docker
make sh-back            # shell backend
make sh-front           # shell frontend
make composer-install
make npm-install
make db
make migrate
make fixtures
make communes-import    # import / re-sync des communes depuis geo.api.gouv.fr
make test
make lint
make cs-fix
```

## API principale

- `GET /api/health` — healthcheck
- `GET /api/puzzle/today` — énigme du jour (sans coordonnées de la cible)
- `POST /api/puzzle/guess` — soumission d'un essai
- `GET /api/communes/search?q=...` — autocomplete fuzzy via `pg_trgm`
- `GET /api/puzzle/archive` — liste des énigmes passées
- `GET /api/puzzle/archive/{date}` — détail d'une énigme archivée
- `GET /api/stats/today` — stats agrégées du jour

## Flux MVP

1. Une énigme tirée chaque jour à 00h05 Europe/Paris parmi les communes ≥ 5 000 hab.
2. Le joueur a 6 essais pour deviner la commune cible.
3. Chaque essai renvoie distance Haversine + flèche directionnelle + tier coloré.
4. À chaque essai raté, un indice contextuel se révèle (population, climat, économie, patrimoine, première lettre).
5. La carte révèle la cible uniquement en fin de partie.
6. Grille emoji partageable façon Wordle, streak persisté en `localStorage`.

## Variables d'environnement

Backend :

- `APP_ENV`
- `APP_SECRET`
- `DATABASE_URL`
- `CORS_ALLOW_ORIGIN` regex des origines frontend autorisées. En production : `^https://(www\.)?sutom-commune\.fr$`.
- `FRONTEND_URL`
- `DEFAULT_URI`
- `POSTGRES_DB`, `POSTGRES_USER`, `POSTGRES_PASSWORD`

Frontend :

- `VITE_API_BASE_URL` URL de base de l'API. En production, utiliser `/api` pour rester en same-origin.
- `VITE_APP_NAME`
- `VITE_SITE_URL` URL publique utilisée pour les balises canonical, Open Graph et données structurées.

## Tests e2e (Playwright)

```bash
cd e2e
npm install
npx playwright install --with-deps chromium    # requiert sudo + apt (Debian/Ubuntu)
npm test
```

Les tests pointent sur `BASE_URL` (défaut `http://localhost:5178`). Sur WSL/Alpine sans apt, installer manuellement `libnss3 libnspr4 libatk1.0-0 libcups2 libdrm2 libxkbcommon0 libxcomposite1 libxdamage1 libxfixes3 libxrandr2 libgbm1 libpango-1.0-0 libcairo2 libasound2` puis `npx playwright install chromium`.

## Tests et qualité

Hors Docker, après installation :

```bash
cd backend
composer install
vendor/bin/phpunit
php -d memory_limit=512M vendor/bin/phpstan analyse --debug

cd ../frontend
npm install
npm run build
npm test -- --run
npm run lint
```

## Anti-cheat

Les coordonnées GPS, le nom et les données sensibles de la commune cible ne sont **jamais** envoyés au client tant que `gameOver=false`. Un test fonctionnel critique vérifie l'absence de fuite sur `/api/puzzle/today`.

## Limites connues

- Pas d'authentification : sessions anonymes (hash IP/UA pour analytics).
- Pas de paiement / monétisation au MVP.
- Pas de multijoueur ni de défis entre amis.
- FR uniquement (i18n prêt pour V2).
