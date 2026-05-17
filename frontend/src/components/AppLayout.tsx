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
      <AppHeader streak={streak} />
      <main className="flex-1 max-w-3xl w-full mx-auto px-4 py-6">
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
