import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { HintsPanel } from './HintsPanel';

describe('HintsPanel', () => {
  it('always shows region + department', () => {
    render(
      <HintsPanel
        regionName="Normandie"
        departmentName="Calvados"
        departmentCode="14"
        revealedHints={[]}
      />,
    );
    expect(screen.getByText('Normandie')).toBeInTheDocument();
    expect(screen.getByText('Calvados (14)')).toBeInTheDocument();
  });

  it('masks unrevealed hint slots', () => {
    render(
      <HintsPanel
        regionName="Normandie"
        departmentName="Calvados"
        departmentCode="14"
        revealedHints={[]}
      />,
    );
    expect(screen.getAllByText('— masqué —').length).toBeGreaterThan(0);
  });

  it('reveals hints in the correct slots', () => {
    render(
      <HintsPanel
        regionName="Normandie"
        departmentName="Calvados"
        departmentCode="14"
        revealedHints={[
          { key: 'population', label: 'Population', value: 'environ 7 000 habitants' },
          { key: 'climate', label: 'Climat', value: 'océanique' },
        ]}
      />,
    );
    expect(screen.getByText('environ 7 000 habitants')).toBeInTheDocument();
    expect(screen.getByText('océanique')).toBeInTheDocument();
    expect(screen.getAllByText('— masqué —').length).toBe(3);
  });
});
