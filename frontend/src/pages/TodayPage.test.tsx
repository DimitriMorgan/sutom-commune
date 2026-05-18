import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { render, screen, waitFor, fireEvent, act } from '@testing-library/react';
import { createMemoryRouter, RouterProvider } from 'react-router-dom';
import { api } from '../api/client';
import type {
  CommuneSearchResult,
  GuessFeedback,
  PuzzleToday,
  PuzzleSolution,
} from '../api/types';

// Stub la lazy carte pour éviter MapLibre+WebGL dans jsdom.
vi.mock('../components/CommuneMapInner', () => ({
  default: ({ solution }: { solution: PuzzleSolution | null }) => (
    <div data-testid="map-stub">{solution ? `target:${solution.name}` : 'no-target'}</div>
  ),
}));

import { TodayPage } from './TodayPage';

const PUZZLE: PuzzleToday = {
  puzzleNumber: 99,
  puzzleDate: '2026-05-19',
  regionName: 'Normandie',
  departmentName: 'Calvados',
  departmentCode: '14',
  theme: null,
  maxAttempts: 6,
};

const PARIS: CommuneSearchResult = {
  id: '75056',
  codeInsee: '75056',
  name: 'Paris',
  departmentName: 'Paris',
  departmentCode: '75',
};

const HONFLEUR: CommuneSearchResult = {
  id: '14333',
  codeInsee: '14333',
  name: 'Honfleur',
  departmentName: 'Calvados',
  departmentCode: '14',
};

function renderTodayPage() {
  const router = createMemoryRouter(
    [{ path: '/', element: <TodayPage /> }],
    { initialEntries: ['/'] },
  );
  return render(<RouterProvider router={router} />);
}

describe('TodayPage flow', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('loads the puzzle and shows region while keeping the department hidden', async () => {
    vi.spyOn(api, 'getToday').mockResolvedValue(PUZZLE);

    renderTodayPage();

    await waitFor(() => {
      expect(screen.getByText('Normandie')).toBeInTheDocument();
    });

    // Le département est masqué tant qu'aucune tentative n'a été faite.
    expect(screen.queryByText('Calvados (14)')).toBeNull();
    expect(screen.getByText(/révélé au 1.*essai/i)).toBeInTheDocument();
  });

  it('reveals the department + a hint after the first wrong guess', async () => {
    vi.spyOn(api, 'getToday').mockResolvedValue(PUZZLE);
    vi.spyOn(api, 'searchCommunes').mockResolvedValue([PARIS]);
    const wrongFeedback: GuessFeedback = {
      isCorrect: false,
      gameOver: false,
      distanceKm: 198,
      bearing: 'NW',
      bearingDegrees: 310,
      proximityTier: 'orange',
      attemptsRemaining: 5,
      guessLatitude: 48.8566,
      guessLongitude: 2.3522,
      guessName: 'Paris',
      guessCodeInsee: '75056',
      nextHint: {
        key: 'population',
        label: 'Population',
        value: 'environ 7 000 habitants',
      },
    };
    vi.spyOn(api, 'submitGuess').mockResolvedValue(wrongFeedback);

    renderTodayPage();
    await waitFor(() => expect(screen.getByText('Normandie')).toBeInTheDocument());

    // Saisie d'un essai puis sélection dans l'autocomplete.
    const input = screen.getByRole('textbox') as HTMLInputElement;
    fireEvent.change(input, { target: { value: 'paris' } });
    await waitFor(() => expect(screen.getByRole('option')).toBeInTheDocument());

    await act(async () => {
      fireEvent.mouseDown(screen.getByRole('option'));
    });

    // Après le 1er essai raté : département dévoilé + indice de population.
    await waitFor(() => {
      expect(screen.getByText('Calvados (14)')).toBeInTheDocument();
      expect(screen.getByText('environ 7 000 habitants')).toBeInTheDocument();
    });

    // 5 essais restants annoncés.
    expect(screen.getByText(/5 essais restants/i)).toBeInTheDocument();
  });

  it('shows the result modal with the solution when the player wins', async () => {
    vi.spyOn(api, 'getToday').mockResolvedValue(PUZZLE);
    vi.spyOn(api, 'searchCommunes').mockResolvedValue([HONFLEUR]);
    const winFeedback: GuessFeedback = {
      isCorrect: true,
      gameOver: true,
      solution: {
        codeInsee: '14333',
        name: 'Honfleur',
        departmentName: 'Calvados',
        departmentCode: '14',
        latitude: 49.4197,
        longitude: 0.236,
        population: 7060,
        patrimoine: ['port historique'],
        wikipediaUrl: null,
      },
      guessPath: [
        {
          codeInsee: '14333',
          name: 'Honfleur',
          latitude: 49.4197,
          longitude: 0.236,
          distanceKm: 0,
          bearing: 'N',
          bearingDegrees: 0,
          proximityTier: 'green',
          isCorrect: true,
        },
      ],
      shareGrid: 'Sutom-Commune #99 1/6\n🟩✓',
    };
    vi.spyOn(api, 'submitGuess').mockResolvedValue(winFeedback);

    renderTodayPage();
    await waitFor(() => expect(screen.getByText('Normandie')).toBeInTheDocument());

    fireEvent.change(screen.getByRole('textbox'), { target: { value: 'honfleur' } });
    await waitFor(() => expect(screen.getByRole('option')).toBeInTheDocument());

    await act(async () => {
      fireEvent.mouseDown(screen.getByRole('option'));
    });

    // Le modal de résultat est affiché avec le nom de la commune.
    await waitFor(() => {
      expect(screen.getAllByText('Honfleur').length).toBeGreaterThan(0);
    });
    // Le pin de la cible est désormais visible sur la carte (stub).
    expect(screen.getByTestId('map-stub')).toHaveTextContent('target:Honfleur');
  });
});
