/**
 * Helpers SEO côté client (mise à jour des balises meta après navigation SPA).
 * Pour un SEO complet, le SSR/SSG serait préférable mais hors scope MVP.
 */
export function setSeo(opts: { title?: string; description?: string; canonical?: string }): void {
  if (opts.title) {
    document.title = opts.title;
  }
  if (opts.description) {
    let meta = document.querySelector<HTMLMetaElement>('meta[name="description"]');
    if (!meta) {
      meta = document.createElement('meta');
      meta.name = 'description';
      document.head.appendChild(meta);
    }
    meta.content = opts.description;
  }
  if (opts.canonical) {
    let link = document.querySelector<HTMLLinkElement>('link[rel="canonical"]');
    if (!link) {
      link = document.createElement('link');
      link.rel = 'canonical';
      document.head.appendChild(link);
    }
    link.href = opts.canonical;
  }
}
