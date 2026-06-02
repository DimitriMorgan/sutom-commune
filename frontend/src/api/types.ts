export type ProximityTier = 'green' | 'yellow' | 'orange' | 'red';

export type BearingLabel = 'N' | 'NE' | 'E' | 'SE' | 'S' | 'SW' | 'W' | 'NW';

export type PuzzleToday = {
  puzzleNumber: number;
  puzzleDate: string;
  regionName: string;
  departmentName: string;
  departmentCode: string;
  theme: string | null;
  maxAttempts: number;
};

export type Hint = {
  key: 'population' | 'climate' | 'specialite' | 'patrimoine' | 'first_letter';
  label: string;
  value: string;
};

export type CommuneSearchResult = {
  id: string;
  codeInsee: string;
  name: string;
  departmentName: string;
  departmentCode: string;
};

export type GuessPathStep = {
  codeInsee: string;
  name: string;
  latitude: number;
  longitude: number;
  distanceKm: number;
  bearing: BearingLabel;
  bearingDegrees: number;
  proximityTier: ProximityTier;
  isCorrect: boolean;
};

export type PuzzleSolution = {
  codeInsee: string;
  name: string;
  departmentName: string;
  departmentCode: string;
  latitude: number;
  longitude: number;
  population: number;
  patrimoine: string[];
  wikipediaUrl: string | null;
};

export type GuessFeedback = {
  isCorrect: boolean;
  gameOver: boolean;
  distanceKm?: number;
  bearing?: BearingLabel;
  bearingDegrees?: number;
  proximityTier?: ProximityTier;
  nextHint?: Hint;
  attemptsRemaining?: number;
  guessLatitude?: number;
  guessLongitude?: number;
  guessName?: string;
  guessCodeInsee?: string;
  solution?: PuzzleSolution;
  guessPath?: GuessPathStep[];
  shareGrid?: string;
};

/** Apparition passée d'une commune comme énigme (jamais celle du jour — anti-spoil). */
export type CommunePastAppearance = {
  date: string;
  puzzleNumber: number;
};

/**
 * Fiche encyclopédique d'une commune jouable (page longue traîne /commune/{slug}).
 * Faits publics uniquement ; `pastAppearances` exclut l'énigme du jour.
 */
export type CommuneDetail = {
  slug: string;
  codeInsee: string;
  name: string;
  postalCodes: string[];
  departmentName: string;
  departmentCode: string;
  regionName: string;
  regionCode: string;
  latitude: number;
  longitude: number;
  population: number;
  altitude: number | null;
  climate: string;
  patrimoine: string[];
  specialite: string | null;
  difficulty: string | null;
  wikipediaUrl: string | null;
  pastAppearances: CommunePastAppearance[];
};

/**
 * Entrée d'archive anti-spoil : on ne révèle que la région et le département,
 * jamais le nom ni le code INSEE de la commune (sinon rejouer l'énigme n'a aucun intérêt).
 */
export type ArchiveEntry = {
  puzzleNumber: number;
  puzzleDate: string;
  regionName: string;
  regionCode: string;
  departmentName: string;
  departmentCode: string;
};
