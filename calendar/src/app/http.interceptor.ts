import { Injectable } from '@angular/core';
import {
  HttpRequest,
  HttpHandler,
  HttpEvent,
  HttpInterceptor,
  HttpErrorResponse
} from '@angular/common/http';
import { Observable, throwError, TimeoutError } from 'rxjs';
import { catchError, timeout } from 'rxjs/operators';

@Injectable()
export class HttpTimeoutInterceptor implements HttpInterceptor {
  constructor() { }

  intercept(request: HttpRequest<unknown>, next: HttpHandler): Observable<HttpEvent<unknown>> {
    const timeoutValue = 15000; // 15 másodperc timeout

    return next.handle(request).pipe(
      timeout(timeoutValue),
      catchError((error: any) => {
        console.error('[HttpInterceptor] Hiba történt az API hívás során:', error);

        if (error instanceof TimeoutError) {
          console.error('[HttpInterceptor] Timeout: Az API nem válaszolt 15 másodperc alatt');
          return throwError(() => ({
            status: 408,
            statusText: 'Request Timeout',
            message: 'Az API szerver nem válaszolt időben. Kérjük, ellenőrizze az internet kapcsolatot és próbálja újra később.'
          }));
        }

        if (error instanceof HttpErrorResponse) {
          console.error(`[HttpInterceptor] HTTP Hiba ${error.status}: ${error.statusText}`);
          console.error('[HttpInterceptor] Hiba válasz:', error.error);

          // CORS hiba
          if (error.status === 0) {
            return throwError(() => ({
              status: 0,
              statusText: 'Network Error',
              message: 'Nem lehet kapcsolódni az API szerverhez. Ellenőrizze, hogy az API szerver fut-e és elérhető-e a localhost:8000-en.'
            }));
          }
        }

        return throwError(() => error);
      })
    );
  }
}
