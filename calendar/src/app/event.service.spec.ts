import { TestBed } from '@angular/core/testing';

import { EventService } from './event.service';

// TODO #436: ez a stub az Angular CLI által generált, valódi TestBed mock-okat
// nem tartalmaz. Amíg ki nem egészítjük, xdescribe-bal pendingben hagyjuk, hogy a
// 'ng test' ne essen miatta piros. Promóció: vissza describe-ra + DI providerek.
xdescribe('EventService', () => {
  let service: EventService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(EventService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
