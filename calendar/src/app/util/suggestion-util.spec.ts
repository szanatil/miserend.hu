import {SuggestionUtil} from './suggestion-util';
import {Mass} from '../model/mass';
import {Rite} from '../enum/rites';

function makeMass(overrides: Partial<Mass> = {}): Mass {
  return {
    id: 1,
    churchId: 100,
    title: 'Szentmise',
    rite: Rite.ROMAN_CATHOLIC,
    startDate: '2026-03-01T07:00:00',
    lang: 'hu',
    ...overrides,
  };
}

describe('SuggestionUtil.generateSuggestions', () => {

  it('returns an empty array when there are no changes and no deletions', () => {
    const masses = new Map<number, Mass>([[1, makeMass()]]);
    const changes = new Map<number, Mass>();
    const deletedMasses: number[] = [];

    const result = SuggestionUtil.generateSuggestions(masses, changes, deletedMasses);

    expect(result).toEqual([]);
  });

  it('returns an empty array when changes contain only a no-op modification', () => {
    // #419: if the user opens an edit dialog and saves without changing anything,
    // the mass ends up in `changes` but its diff vs. the original is empty.
    // generateSuggestions must drop it so the package is detectable as empty.
    const original = makeMass({id: 42, title: 'Szentmise', startDate: '2026-03-01T07:00:00'});
    const unchanged = makeMass({id: 42, title: 'Szentmise', startDate: '2026-03-01T07:00:00'});

    const masses = new Map<number, Mass>([[42, original]]);
    const changes = new Map<number, Mass>([[42, unchanged]]);

    const result = SuggestionUtil.generateSuggestions(masses, changes, []);

    expect(result).toEqual([]);
  });

  it('returns a MODIFIED suggestion when the changed mass actually differs', () => {
    const original = makeMass({id: 42, startDate: '2026-03-01T07:00:00'});
    const modified = makeMass({id: 42, startDate: '2026-03-01T08:00:00'});

    const result = SuggestionUtil.generateSuggestions(
      new Map([[42, original]]),
      new Map([[42, modified]]),
      [],
    );

    expect(result.length).toBe(1);
    expect(result[0].massState).toBe('MODIFIED' as any);
    expect(result[0].massId).toBe(42);
    expect(result[0].changes['startDate']).toBe('2026-03-01T08:00:00');
  });

  it('returns a NEW suggestion when a change has a negative (tmp) id', () => {
    const tmpId = -123;
    const newMass = makeMass({id: tmpId, title: 'Új mise'});

    const result = SuggestionUtil.generateSuggestions(
      new Map(),
      new Map([[tmpId, newMass]]),
      [],
    );

    expect(result.length).toBe(1);
    expect(result[0].massState).toBe('NEW' as any);
    expect(result[0].changes.id).toBeUndefined();
    expect(result[0].changes.title).toBe('Új mise');
  });

  it('returns a DELETED suggestion for each id in deletedMasses', () => {
    const masses = new Map<number, Mass>([[7, makeMass({id: 7, periodId: 3})]]);

    const result = SuggestionUtil.generateSuggestions(masses, new Map(), [7]);

    expect(result.length).toBe(1);
    expect(result[0].massState).toBe('DELETED' as any);
    expect(result[0].massId).toBe(7);
    expect(result[0].periodId).toBe(3);
  });
});

describe('SuggestionUtil.isMassUnchanged (#352)', () => {

  it('returns true for two identical masses', () => {
    const a = makeMass({id: 5, title: 'Szentmise', startDate: '2026-03-01T07:00:00'});
    const b = makeMass({id: 5, title: 'Szentmise', startDate: '2026-03-01T07:00:00'});

    expect(SuggestionUtil.isMassUnchanged(a, b)).toBe(true);
  });

  it('returns false when a scalar field differs (startDate)', () => {
    const original = makeMass({id: 5, startDate: '2026-03-01T07:00:00'});
    const modified = makeMass({id: 5, startDate: '2026-03-01T08:00:00'});

    expect(SuggestionUtil.isMassUnchanged(original, modified)).toBe(false);
  });

  it('returns false when the modified mass adds a new field', () => {
    const original = makeMass({id: 5});
    const modified = makeMass({id: 5, comment: 'új megjegyzés'});

    expect(SuggestionUtil.isMassUnchanged(original, modified)).toBe(false);
  });

  it('returns false when an optional field becomes null', () => {
    const original = makeMass({id: 5, comment: 'régi'});
    const modified = makeMass({id: 5, comment: null});

    expect(SuggestionUtil.isMassUnchanged(original, modified)).toBe(false);
  });
});
