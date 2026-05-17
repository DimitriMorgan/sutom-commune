import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { GuessRow } from './GuessRow';

describe('GuessRow', () => {
  it('renders the commune name + distance + arrow for a wrong guess', () => {
    render(
      <GuessRow
        attempt={{
          codeInsee: '75056',
          name: 'Paris',
          distanceKm: 198,
          bearing: 'NW',
          bearingDegrees: 310,
          proximityTier: 'orange',
          isCorrect: false,
        }}
      />,
    );
    expect(screen.getByText('Paris')).toBeInTheDocument();
    expect(screen.getByText('198 km')).toBeInTheDocument();
  });

  it('renders the Found! state for a correct guess', () => {
    render(
      <GuessRow
        attempt={{
          codeInsee: '14333',
          name: 'Honfleur',
          distanceKm: 0,
          bearing: 'N',
          bearingDegrees: 0,
          proximityTier: 'green',
          isCorrect: true,
        }}
      />,
    );
    expect(screen.getByText('Honfleur')).toBeInTheDocument();
    expect(screen.getByText(/Trouvée/i)).toBeInTheDocument();
  });
});
