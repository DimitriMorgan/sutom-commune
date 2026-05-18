import { Link } from 'react-router-dom';

export function AppFooter() {
  return (
    <footer className="border-t border-atlas-ocre/30 bg-atlas-paper/60 mt-12">
      <div className="max-w-3xl mx-auto px-4 py-6 text-xs text-atlas-ink/70 space-y-2">
        <p>
          Données : <a className="underline hover:text-atlas-navy" href="https://geo.api.gouv.fr" target="_blank" rel="noreferrer noopener">geo.api.gouv.fr</a> (Etalab) · Cartes : © <a className="underline hover:text-atlas-navy" href="https://www.openstreetmap.org/copyright" target="_blank" rel="noreferrer noopener">OpenStreetMap contributors</a>
        </p>
        <nav className="flex flex-wrap gap-4">
          <Link to="/comment-jouer" className="hover:text-atlas-navy">Comment jouer</Link>
          <Link to="/a-propos" className="hover:text-atlas-navy">À propos</Link>
          <Link to="/mentions-legales" className="hover:text-atlas-navy">Mentions légales</Link>
          <a href="mailto:contact@sutom-commune.com" className="hover:text-atlas-navy">Contact</a>
        </nav>
      </div>
    </footer>
  );
}
