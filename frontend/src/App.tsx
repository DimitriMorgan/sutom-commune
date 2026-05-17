import { useEffect, useState } from 'react';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? '/api';

type HealthStatus = {
  status: string;
  service: string;
  timestamp: string;
  apiPlatform: string;
};

export function App() {
  const [health, setHealth] = useState<HealthStatus | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetch(`${API_BASE_URL}/health`, { headers: { Accept: 'application/json' } })
      .then((res) => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json() as Promise<HealthStatus>;
      })
      .then(setHealth)
      .catch((err: unknown) => setError(err instanceof Error ? err.message : 'unknown error'));
  }, []);

  return (
    <div className="min-h-screen bg-atlas-sand text-atlas-ink font-sans flex items-center justify-center p-6">
      <div className="max-w-xl w-full text-center space-y-6">
        <h1 className="font-serif text-5xl text-atlas-navy">Sutom-Commune</h1>
        <p className="text-atlas-moss text-lg">
          Le jeu quotidien des communes de France — bientôt disponible.
        </p>

        <div className="border border-atlas-ocre/30 rounded-lg p-4 bg-white/60 text-left text-sm">
          <p className="font-semibold mb-2 text-atlas-navy">État de l'API</p>
          {error && <p className="text-red-700">Erreur : {error}</p>}
          {!error && !health && <p className="text-atlas-ink/60">Connexion à l'API…</p>}
          {health && (
            <pre className="font-mono text-xs whitespace-pre-wrap break-all text-atlas-ink/80">
              {JSON.stringify(health, null, 2)}
            </pre>
          )}
        </div>
      </div>
    </div>
  );
}
