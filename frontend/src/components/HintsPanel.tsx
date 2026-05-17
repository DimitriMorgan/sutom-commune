import type { Hint } from '../api/types';

type HintKey = Hint['key'];

const HINT_LABELS: Record<HintKey, string> = {
  population: 'Population',
  climate: 'Climat',
  specialite: 'Spécialité',
  patrimoine: 'Patrimoine',
  first_letter: 'Première lettre',
};

const HINT_ORDER: HintKey[] = [
  'population',
  'climate',
  'specialite',
  'patrimoine',
  'first_letter',
];

type Props = {
  regionName: string;
  departmentName: string;
  departmentCode: string;
  revealedHints: Hint[];
};

export function HintsPanel({ regionName, departmentName, departmentCode, revealedHints }: Props) {
  const revealedByKey = new Map(revealedHints.map((h) => [h.key, h]));

  return (
    <section className="bg-atlas-paper/70 border border-atlas-ocre/30 rounded-lg p-4 space-y-2">
      <div className="flex justify-between items-baseline">
        <span className="text-xs uppercase tracking-wider text-atlas-ink/60">Région</span>
        <span className="font-medium text-atlas-navy">{regionName}</span>
      </div>
      <div className="flex justify-between items-baseline">
        <span className="text-xs uppercase tracking-wider text-atlas-ink/60">Département</span>
        <span className="font-medium text-atlas-navy">
          {departmentName} ({departmentCode})
        </span>
      </div>
      <hr className="border-atlas-ocre/20 my-2" />
      <ul className="space-y-1.5">
        {HINT_ORDER.map((key) => {
          const hint = revealedByKey.get(key);
          return (
            <li key={key} className="flex justify-between items-baseline gap-3 text-sm">
              <span
                className={`text-xs uppercase tracking-wider ${
                  hint ? 'text-atlas-ink/60' : 'text-atlas-ink/30'
                }`}
              >
                {HINT_LABELS[key]}
              </span>
              <span
                className={`text-right truncate ${
                  hint ? 'text-atlas-navy animate-slide-in' : 'text-atlas-ink/25 italic'
                }`}
              >
                {hint?.value ?? '— masqué —'}
              </span>
            </li>
          );
        })}
      </ul>
    </section>
  );
}
