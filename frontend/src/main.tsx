import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { App } from './App';
import './styles.css';

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <App />
  </StrictMode>,
);

// Web Vitals terrain (LCP/CLS/INP/TTFB/FCP) → analytics. Prod only pour ne pas
// polluer le dashboard avec les valeurs instables du serveur de dev.
if (import.meta.env.PROD) {
  void import('./webVitals').then((m) => m.initWebVitals());
}
