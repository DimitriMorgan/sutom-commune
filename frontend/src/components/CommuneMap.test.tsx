import { describe, expect, it, vi } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import type { GuessPathStep, PuzzleSolution } from '../api/types';

// Mock du composant lazy : on remplace maplibre par un stub qui matérialise
// directement les pins en DOM pour qu'on puisse les asserter sans devoir
// instancier WebGL dans jsdom.
vi.mock('./CommuneMapInner', () => ({
  default: ({ guessPath, solution }: { guessPath: GuessPathStep[]; solution: PuzzleSolution | null }) => (
    <div data-testid="map-stub">
      {guessPath.map((step) => (
        <span key={step.codeInsee} data-testid="guess-pin">
          {step.name}
        </span>
      ))}
      {solution && (
        <span data-testid="target-pin">{solution.name}</span>
      )}
    </div>
  ),
}));

import { CommuneMap } from './CommuneMap';

const PARIS_GUESS: GuessPathStep = {
  codeInsee: '75056',
  name: 'Paris',
  latitude: 48.8566,
  longitude: 2.3522,
  distanceKm: 198,
  bearing: 'NW',
  bearingDegrees: 310,
  proximityTier: 'orange',
  isCorrect: false,
};

const HONFLEUR_SOLUTION: PuzzleSolution = {
  codeInsee: '14333',
  name: 'Honfleur',
  departmentName: 'Calvados',
  departmentCode: '14',
  latitude: 49.4197,
  longitude: 0.236,
  population: 7060,
  patrimoine: [],
  wikipediaUrl: null,
};

describe('CommuneMap', () => {
  it('renders guess pins but no target pin while the game is in progress', async () => {
    render(<CommuneMap guessPath={[PARIS_GUESS]} solution={null} />);

    await waitFor(() => {
      expect(screen.getByTestId('map-stub')).toBeInTheDocument();
    });
    expect(screen.getByTestId('guess-pin')).toHaveTextContent('Paris');
    expect(screen.queryByTestId('target-pin')).toBeNull();
  });

  it('reveals the target pin once gameOver provides a solution', async () => {
    render(<CommuneMap guessPath={[PARIS_GUESS]} solution={HONFLEUR_SOLUTION} />);

    await waitFor(() => {
      expect(screen.getByTestId('target-pin')).toBeInTheDocument();
    });
    expect(screen.getByTestId('target-pin')).toHaveTextContent('Honfleur');
  });
});
