import {Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {environment} from '../../environments/environment';
import {HttpParams} from '@angular/common/http';
import {catchError} from 'rxjs/operators';
import {throwError} from 'rxjs';
import {MatSnackBarService} from './mat-snack-bar.service';

@Injectable({ providedIn: 'root' })
export class SearchService {

  constructor(private http: HttpClient, private snackBarService: MatSnackBarService) {}

  getData() {
    return this.http.get<SearchData>(`${environment.apiUrl}search`).pipe(
      catchError(error => {
        console.error('[SearchService] Hiba az adatok betöltésekor:', error);
        this.snackBarService.error('Nem sikerült az adatokat betölteni.');
        return throwError(() => error);
      })
    );
  }

  public generateMasses(years: number[], tid: number) {
    let params = new HttpParams();
    params = params.append('tids[]', tid.toString());
    years.forEach(year => {
      params = params.append('years[]', year.toString());
    });

    return this.http.put(`${environment.apiUrl}generate`, null, { params }).pipe(
      catchError(error => {
        console.error('[SearchService] Hiba az adatok generálása során:', error);
        this.snackBarService.error('Nem sikerült az adatokat generálni.');
        return throwError(() => error);
      })
    );
  }

  public search(q: string, templom: any) {
    return this.http.post(`${environment.apiUrl}search`, {
      params: { q: q, templom: templom }
    }).pipe(
      catchError(error => {
        console.error('[SearchService] Hiba a keresésnél:', error);
        this.snackBarService.error('Hiba a keresés során. Próbálja újra.');
        return throwError(() => error);
      })
    );
  }
}

export interface SearchData {
  attributes: { [key: string]: { id: any; name: string ; group: string} };
  languages: { [key: string]: { id: any; name: string } };
  egyhazmegyek: { [key: string]: { id: number; name: string } };
  espereskeruletek: { [key: string]: { id: number; name: string } };
  orszagok: { [key: string]: { id: number; name: string } };
  megyek: { [key: string]: { id: number; name: string } };
  varosok: { [key: string]: { id: number; name: string } };
}
