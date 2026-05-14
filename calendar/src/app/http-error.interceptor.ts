import { Injectable } from '@angular/core';
import {
  HttpRequest,
  HttpHandler,
  HttpEvent,
  HttpInterceptor,
  HttpErrorResponse
} from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { MatSnackBarService } from './services/mat-snack-bar.service';

@Injectable()
export class HttpErrorInterceptor implements HttpInterceptor {
  constructor(private snackBarService: MatSnackBarService) { }

  intercept(request: HttpRequest<unknown>, next: HttpHandler): Observable<HttpEvent<unknown>> {
    return next.handle(request).pipe(
      catchError((error: any) => {
        let errorMessage = 'Ismeretlen hiba történt';

        // Timeout hiba
        if (error.status === 408 || error.statusText === 'Request Timeout') {
          errorMessage = 'Az API szerver nem válaszolt időben. Kérjük, próbálja újra később.';
        }
        // Network hiba (CORS vagy szerver nem fut)
        else if (error.status === 0) {
          errorMessage = 'Nem lehet kapcsolódni az API szerverhez. Ellenőrizze, hogy az API szerver fut-e.';
        }
        // HTTP hiba
        else if (error instanceof HttpErrorResponse) {
          errorMessage = `API hiba ${error.status}: ${error.statusText}`;
        }

        // Egyéni üzenet ha az interceptor már beállított
        if (error.message) {
          errorMessage = error.message;
        }

        console.error('[HttpErrorInterceptor] Hibaüzenet:', errorMessage);
        this.snackBarService.error(errorMessage, 6000);

        return throwError(() => error);
      })
    );
  }
}
