import { Calendar, HelpCircle } from 'lucide-react';
import { Link } from 'react-router-dom';
import { StreakBadge } from './StreakBadge';
import type { Streak } from '../storage/state';

export function AppHeader({ streak }: { streak: Streak }) {
  return (
    <header className="border-b border-atlas-ocre/30 bg-atlas-paper/80 backdrop-blur">
      <div className="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
        <Link to="/" className="font-serif text-2xl text-atlas-navy hover:text-atlas-ocre transition-colors">
          Sutom-Commune
        </Link>
        <div className="flex items-center gap-2">
          <StreakBadge streak={streak} />
          <Link
            to="/archive"
            className="p-2 rounded-full hover:bg-atlas-sand transition-colors text-atlas-ink/70"
            title="Archive"
          >
            <Calendar size={18} />
          </Link>
          <Link
            to="/comment-jouer"
            className="p-2 rounded-full hover:bg-atlas-sand transition-colors text-atlas-ink/70"
            title="Comment jouer"
          >
            <HelpCircle size={18} />
          </Link>
        </div>
      </div>
    </header>
  );
}
