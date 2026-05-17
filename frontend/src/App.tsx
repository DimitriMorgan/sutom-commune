import { lazy, Suspense } from 'react';
import { createBrowserRouter, RouterProvider } from 'react-router-dom';
import { AppLayout } from './components/AppLayout';
import { TodayPage } from './pages/TodayPage';

// Routes secondaires chargées paresseusement pour réduire le bundle initial.
const ArchivePage = lazy(() => import('./pages/ArchivePage').then((m) => ({ default: m.ArchivePage })));
const ArchiveDetailPage = lazy(() =>
  import('./pages/ArchiveDetailPage').then((m) => ({ default: m.ArchiveDetailPage })),
);
const HowToPlayPage = lazy(() => import('./pages/HowToPlayPage').then((m) => ({ default: m.HowToPlayPage })));
const AboutPage = lazy(() => import('./pages/AboutPage').then((m) => ({ default: m.AboutPage })));
const LegalPage = lazy(() => import('./pages/LegalPage').then((m) => ({ default: m.LegalPage })));

function PageFallback() {
  return (
    <div className="flex justify-center py-12" aria-busy="true" aria-live="polite">
      <span className="text-atlas-ink/60">Chargement…</span>
    </div>
  );
}

const wrap = (node: React.ReactNode) => <Suspense fallback={<PageFallback />}>{node}</Suspense>;

const router = createBrowserRouter([
  {
    path: '/',
    element: <AppLayout />,
    children: [
      { index: true, element: <TodayPage /> },
      { path: 'archive', element: wrap(<ArchivePage />) },
      { path: 'archive/:date', element: wrap(<ArchiveDetailPage />) },
      { path: 'comment-jouer', element: wrap(<HowToPlayPage />) },
      { path: 'a-propos', element: wrap(<AboutPage />) },
      { path: 'mentions-legales', element: wrap(<LegalPage />) },
    ],
  },
]);

export function App() {
  return <RouterProvider router={router} />;
}
