import { afterEach, describe, expect, it, vi } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import { createMemoryRouter, RouterProvider } from 'react-router-dom';
import { api } from '../api/client';
import type { CommuneDetail } from '../api/types';
import { CommunePage } from './CommunePage';

const PARIS: CommuneDetail = {
  slug: 'paris-75056',
  codeInsee: '75056',
  name: 'Paris',
  postalCodes: ['75001'],
  departmentName: 'Paris',
  departmentCode: '75',
  regionName: 'Île-de-France',
  regionCode: '11',
  latitude: 48.8589,
  longitude: 2.347,
  population: 2103778,
  altitude: 35,
  climate: 'semi-continental',
  patrimoine: ['Tour Eiffel', 'Louvre'],
  specialite: 'capitale',
  difficulty: 'easy',
  wikipediaUrl: 'https://fr.wikipedia.org/wiki/Paris',
  pastAppearances: [{ date: '2026-05-10', puzzleNumber: 42 }],
};

function renderCommune(slug: string) {
  const router = createMemoryRouter(
    [{ path: '/commune/:slug', element: <CommunePage /> }],
    { initialEntries: [`/commune/${slug}`] },
  );
  return render(<RouterProvider router={router} />);
}

describe('CommunePage', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('renders the commune facts and past appearances', async () => {
    vi.spyOn(api, 'getCommune').mockResolvedValue(PARIS);

    renderCommune('paris-75056');

    await waitFor(() => expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent('Paris'));
    // Patrimoine listé
    expect(screen.getByText('Tour Eiffel')).toBeInTheDocument();
    // Apparition passée → lien vers l'archive du jour correspondant
    const link = screen.getByRole('link', { name: /Énigme #42/ });
    expect(link).toHaveAttribute('href', '/archive/2026-05-10');
  });

  it('shows a not-found message when the API rejects', async () => {
    vi.spyOn(api, 'getCommune').mockRejectedValue(new Error('API /commune/zzz → 404'));

    renderCommune('zzz-99999');

    await waitFor(() =>
      expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent('Commune introuvable'),
    );
  });

  it('does not render an empty "past appearances" section when there are none', async () => {
    vi.spyOn(api, 'getCommune').mockResolvedValue({ ...PARIS, pastAppearances: [] });

    renderCommune('paris-75056');

    await waitFor(() => expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent('Paris'));
    expect(screen.queryByText('Déjà tombée comme énigme')).not.toBeInTheDocument();
  });
});
