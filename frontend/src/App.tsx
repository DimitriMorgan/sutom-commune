import { createBrowserRouter, RouterProvider } from 'react-router-dom';
import { AppLayout } from './components/AppLayout';
import { TodayPage } from './pages/TodayPage';
import { ArchivePage } from './pages/ArchivePage';
import { ArchiveDetailPage } from './pages/ArchiveDetailPage';
import { HowToPlayPage } from './pages/HowToPlayPage';
import { AboutPage } from './pages/AboutPage';
import { LegalPage } from './pages/LegalPage';

const router = createBrowserRouter([
  {
    path: '/',
    element: <AppLayout />,
    children: [
      { index: true, element: <TodayPage /> },
      { path: 'archive', element: <ArchivePage /> },
      { path: 'archive/:date', element: <ArchiveDetailPage /> },
      { path: 'comment-jouer', element: <HowToPlayPage /> },
      { path: 'a-propos', element: <AboutPage /> },
      { path: 'mentions-legales', element: <LegalPage /> },
    ],
  },
]);

export function App() {
  return <RouterProvider router={router} />;
}
