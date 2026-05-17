import { useEffect, useRef, useState } from 'react';
import { Loader2, Search } from 'lucide-react';
import { api } from '../api/client';
import type { CommuneSearchResult } from '../api/types';

type Props = {
  onSubmit: (commune: CommuneSearchResult) => void;
  disabled?: boolean;
  attemptNumber: number;
};

const DEBOUNCE_MS = 200;

export function GuessInput({ onSubmit, disabled, attemptNumber }: Props) {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<CommuneSearchResult[]>([]);
  const [highlighted, setHighlighted] = useState(0);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const abortRef = useRef<AbortController | null>(null);
  const containerRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (query.trim().length < 2) {
      setResults([]);
      setError(null);
      return;
    }
    abortRef.current?.abort();
    const ctrl = new AbortController();
    abortRef.current = ctrl;
    setLoading(true);
    const timer = window.setTimeout(() => {
      api
        .searchCommunes(query.trim(), ctrl.signal)
        .then((r) => {
          setResults(r);
          setHighlighted(0);
          setError(null);
        })
        .catch((err: unknown) => {
          if (err instanceof DOMException && err.name === 'AbortError') return;
          setError('Recherche indisponible — réessayez.');
        })
        .finally(() => setLoading(false));
    }, DEBOUNCE_MS);
    return () => {
      window.clearTimeout(timer);
      ctrl.abort();
    };
  }, [query]);

  const handleSelect = (commune: CommuneSearchResult) => {
    onSubmit(commune);
    setQuery('');
    setResults([]);
  };

  const handleKey = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setHighlighted((h) => Math.min(results.length - 1, h + 1));
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setHighlighted((h) => Math.max(0, h - 1));
    } else if (e.key === 'Enter' && results.length > 0) {
      e.preventDefault();
      handleSelect(results[highlighted]);
    } else if (e.key === 'Escape') {
      setQuery('');
      setResults([]);
    }
  };

  return (
    <div ref={containerRef} className="relative">
      <label className="block text-xs text-atlas-ink/60 mb-1">
        Essai {attemptNumber} — tapez une commune française
      </label>
      <div className="flex items-center gap-2 bg-atlas-paper border border-atlas-ocre/40 rounded-lg px-3 focus-within:border-atlas-navy">
        <Search size={16} className="text-atlas-ink/50" />
        <input
          type="text"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          onKeyDown={handleKey}
          disabled={disabled}
          autoComplete="off"
          autoCorrect="off"
          spellCheck={false}
          placeholder="ex: Honfleur, Saint-Étienne..."
          className="flex-1 py-2.5 bg-transparent outline-none disabled:opacity-50 text-atlas-ink"
          aria-autocomplete="list"
        />
        {loading && <Loader2 size={16} className="animate-spin text-atlas-ocre" />}
      </div>

      {error && <p className="text-xs text-red-700 mt-1">{error}</p>}

      {results.length > 0 && (
        <ul
          role="listbox"
          className="absolute z-10 left-0 right-0 mt-1 bg-atlas-paper border border-atlas-ocre/40 rounded-lg shadow-lg overflow-hidden"
        >
          {results.map((r, i) => (
            <li
              key={r.id}
              role="option"
              aria-selected={i === highlighted}
              onMouseDown={(e) => {
                e.preventDefault();
                handleSelect(r);
              }}
              onMouseEnter={() => setHighlighted(i)}
              className={`px-3 py-2 cursor-pointer flex justify-between items-center ${
                i === highlighted ? 'bg-atlas-sand' : ''
              }`}
            >
              <span className="text-sm">{r.name}</span>
              <span className="text-xs text-atlas-ink/60">
                {r.departmentName} ({r.departmentCode})
              </span>
            </li>
          ))}
        </ul>
      )}

      {query.trim().length >= 2 && !loading && results.length === 0 && !error && (
        <p className="text-xs text-atlas-ink/60 mt-1">Aucune commune trouvée.</p>
      )}
    </div>
  );
}
