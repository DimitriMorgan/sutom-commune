/**
 * Helpers SEO côté client.
 * Maintient title, meta description, canonical, OG/Twitter et un JSON-LD
 * spécifique à la page (WebPage + BreadcrumbList le plus souvent).
 *
 * Limites : c'est du SEO post-hydration. Pour les crawlers JS-light, l'index.html
 * statique embarque déjà un titre, une description, un h1 et la nav (cf. index.html).
 */

const SITE_URL = (import.meta.env.VITE_SITE_URL ?? 'https://sutom-commune.com').replace(/\/$/, '');

export type SeoOpts = {
  title: string;
  description: string;
  /** Chemin (commence par /), pas l'URL complète. */
  path: string;
  /** Override de l'image OG (path relatif au site). */
  ogImage?: string;
  ogType?: 'website' | 'article';
  /** Breadcrumb hiérarchique, du plus large au plus précis. */
  breadcrumbs?: Array<{ name: string; path: string }>;
  /** JSON-LD structuré supplémentaire (Game, Article, etc.). */
  extraJsonLd?: Record<string, unknown> | Array<Record<string, unknown>>;
  /** Si false, ajoute <meta name="robots" content="noindex"> */
  index?: boolean;
};

export function setSeo(opts: SeoOpts): void {
  const fullUrl = `${SITE_URL}${opts.path}`;
  const image = opts.ogImage ? `${SITE_URL}${opts.ogImage}` : `${SITE_URL}/og-default.svg`;

  document.title = opts.title;

  ensureMeta('name', 'description', opts.description);
  ensureMeta(
    'name',
    'robots',
    opts.index === false
      ? 'noindex, nofollow'
      : 'index, follow, max-snippet:-1, max-image-preview:large',
  );
  ensureCanonical(fullUrl);

  ensureMeta('property', 'og:title', opts.title);
  ensureMeta('property', 'og:description', opts.description);
  ensureMeta('property', 'og:url', fullUrl);
  ensureMeta('property', 'og:type', opts.ogType ?? 'website');
  ensureMeta('property', 'og:image', image);

  ensureMeta('name', 'twitter:title', opts.title);
  ensureMeta('name', 'twitter:description', opts.description);
  ensureMeta('name', 'twitter:image', image);

  // JSON-LD par page (WebPage + BreadcrumbList + structured data optionnel)
  const ldParts: Array<Record<string, unknown>> = [
    {
      '@context': 'https://schema.org',
      '@type': 'WebPage',
      '@id': `${fullUrl}#webpage`,
      url: fullUrl,
      name: opts.title,
      description: opts.description,
      inLanguage: 'fr-FR',
      isPartOf: { '@id': `${SITE_URL}/#website` },
    },
  ];

  if (opts.breadcrumbs && opts.breadcrumbs.length > 0) {
    ldParts.push({
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      itemListElement: opts.breadcrumbs.map((b, i) => ({
        '@type': 'ListItem',
        position: i + 1,
        name: b.name,
        item: `${SITE_URL}${b.path}`,
      })),
    });
  }

  if (opts.extraJsonLd) {
    if (Array.isArray(opts.extraJsonLd)) {
      ldParts.push(...opts.extraJsonLd);
    } else {
      ldParts.push(opts.extraJsonLd);
    }
  }

  setLdJson('ldjson-page', ldParts);
}

function ensureMeta(attr: 'name' | 'property', key: string, content: string): void {
  let el = document.querySelector<HTMLMetaElement>(`meta[${attr}="${key}"]`);
  if (!el) {
    el = document.createElement('meta');
    el.setAttribute(attr, key);
    document.head.appendChild(el);
  }
  el.content = content;
}

function ensureCanonical(href: string): void {
  let el = document.querySelector<HTMLLinkElement>('link[rel="canonical"]');
  if (!el) {
    el = document.createElement('link');
    el.rel = 'canonical';
    document.head.appendChild(el);
  }
  el.href = href;
}

function setLdJson(id: string, payload: unknown): void {
  let el = document.getElementById(id);
  if (!el) {
    el = document.createElement('script');
    el.id = id;
    (el as HTMLScriptElement).type = 'application/ld+json';
    document.head.appendChild(el);
  }
  el.textContent = JSON.stringify(payload);
}
