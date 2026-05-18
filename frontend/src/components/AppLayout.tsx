import { useEffect, useState } from 'react';
import { Outlet } from 'react-router-dom';
import { AppFooter } from './AppFooter';
import { AppHeader } from './AppHeader';
import { HowToPlayModal } from './HowToPlayModal';
import { gameStorage, type Streak } from '../storage/state';

export function AppLayout() {
  const [streak, setStreak] = useState<Streak>(() => gameStorage.loadStreak());
  const [showTutorial, setShowTutorial] = useState(false);

  useEffect(() => {
    if (!gameStorage.hasSeenTutorial()) {
      setShowTutorial(true);
    }
  }, []);

  const refreshStreak = () => setStreak(gameStorage.loadStreak());

  return (
    <div className="min-h-screen flex flex-col bg-atlas-sand text-atlas-ink font-sans">
      <a
        href="#main-content"
        className="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:bg-atlas-navy focus:text-atlas-paper focus:px-4 focus:py-2 focus:rounded"
      >
        Aller au contenu principal
      </a>
      <AppHeader streak={streak} />
      <main
        id="main-content"
        tabIndex={-1}
        className="flex-1 max-w-3xl lg:max-w-6xl w-full mx-auto px-4 py-6 outline-none"
      >
        <Outlet context={{ refreshStreak }} />
      </main>
      <AppFooter />
      {showTutorial && (
        <HowToPlayModal
          onClose={() => {
            gameStorage.markTutorialSeen();
            setShowTutorial(false);
          }}
        />
      )}
    </div>
  );
}

export type LayoutContext = { refreshStreak: () => void };
