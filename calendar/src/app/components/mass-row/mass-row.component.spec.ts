import {ComponentFixture, TestBed} from '@angular/core/testing';
import {MassRowComponent} from './mass-row.component';
import {ReadableMass} from '../../model/readable-mass';

function makeMass(overrides: Partial<ReadableMass> = {}): ReadableMass {
  return {
    massId: 1,
    time: '10:00',
    startDate: '2026-03-15',
    lang: 'magyar',
    ...overrides,
  };
}

describe('MassRowComponent', () => {
  let component: MassRowComponent;
  let fixture: ComponentFixture<MassRowComponent>;
  let el: HTMLElement;

  async function setup(mass: ReadableMass, showDetails = false) {
    await TestBed.configureTestingModule({
      imports: [MassRowComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(MassRowComponent);
    component = fixture.componentInstance;
    component.mass = mass;
    component.showDetails = showDetails;
    fixture.detectChanges();
    el = fixture.nativeElement as HTMLElement;
  }

  it('renders christmas line when mass.christmas is set', async () => {
    await setup(makeMass({christmas: 'Szenteste', time: '22:00'}));
    expect(el.textContent).toContain('Szenteste');
    expect(el.textContent).toContain('22:00');
  });

  it('renders easter line when mass.easter is set (takes precedence over week/month)', async () => {
    await setup(makeMass({easter: 'Húsvétvasárnap', time: '09:30', week: 'minden héten'}));
    expect(el.textContent).toContain('Húsvétvasárnap');
    expect(el.textContent).toContain('09:30');
    // a week branch ne aktiválódjon ha easter van
    expect(el.textContent).not.toContain('minden héten');
  });

  it('renders weekly recurring mass with period + week + days + time', async () => {
    await setup(makeMass({
      period: 'Évközi',
      week: 'minden héten',
      days: 'hétfőn',
      time: '07:00',
    }));
    expect(el.textContent).toContain('Évközi');
    expect(el.textContent).toContain('minden héten');
    expect(el.textContent).toContain('hétfőn');
    expect(el.textContent).toContain('07:00');
  });

  it('renders monthly recurring mass with period + month-label + days', async () => {
    await setup(makeMass({
      period: 'Évközi',
      month: 'minden hónapban az első',
      days: 'szombaton',
      time: '18:00',
    }));
    expect(el.textContent).toContain('Évközi');
    expect(el.textContent).toContain('minden hónapban az első');
    expect(el.textContent).toContain('szombaton');
  });

  it('renders the "(nem ismétlődő)" placeholder when no recurrence info present', async () => {
    await setup(makeMass({period: 'Egyszeri', time: '10:00'}));
    expect(el.textContent).toContain('(nem ismétlődő)');
  });

  it('renders startDate when period is empty', async () => {
    await setup(makeMass({period: undefined, startDate: '2026-04-12', time: '10:00'}));
    expect(el.textContent).toContain('2026-04-12');
  });

  it('hides details block when showDetails=false', async () => {
    await setup(makeMass({title: 'Szentmise', lang: 'magyar', period: 'Évközi', week: 'minden héten', days: 'vasárnap'}), false);
    expect(el.textContent).not.toContain('Liturgia neve');
  });

  it('renders details block when showDetails=true', async () => {
    await setup(makeMass({title: 'Szentmise', lang: 'magyar', period: 'Évközi', week: 'minden héten', days: 'vasárnap'}), true);
    expect(el.textContent).toContain('Liturgia neve: Szentmise');
    expect(el.textContent).toContain('nyelv: magyar');
  });

  it('renders deleted-occasional dates when mass.mDates is non-empty', async () => {
    await setup(makeMass({
      period: 'Évközi',
      week: 'minden héten',
      days: 'kedden',
      mDates: ['2026-04-01', '2026-04-08'],
    }));
    expect(el.textContent).toContain('2026-04-01');
    expect(el.textContent).toContain('2026-04-08');
  });
});
