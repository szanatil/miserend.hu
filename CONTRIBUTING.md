# 🙏 Közreműködés a miserend.hu fejlesztésében

Köszi hogy itt vagy! Ez a fájl a hozzájárulás gyakorlati részleteit gyűjti egybe — a fejlesztői környezet telepítése és a hozzá tartozó kvázi-szabályok a [README.md](README.md)-ben vannak.

## Tartalom

1. [Általános folyamat](#általános-folyamat)
2. [PHP rész](#-php-rész-webapp)
3. [Angular naptár](#-angular-naptár-calendar)
4. [Egyéb hasznos](#egyéb-hasznos)

---

## Általános folyamat

1. **Fork-old a `szentjozsefhackathon/miserend.hu` repót** a saját GitHub fiókodba.
2. **Klónozd** a forkodat lokálisan és add hozzá az upstream remote-ot:
   ```sh
   git clone https://github.com/<te-neved>/miserend.hu.git
   cd miserend.hu
   git remote add upstream https://github.com/szentjozsefhackathon/miserend.hu.git
   ```
3. **Új branch** minden témára külön: `git checkout -b fix/leírás-vagy-issue-szám`.
4. **Commit** apró logikai egységekben, magyarul (a projekt többi commit-üzenete is ilyen).
5. **Push** a saját forkodra: `git push -u origin <branch>`.
6. **Pull Request** a `szentjozsefhackathon/miserend.hu` `master` ellen.
7. Reagálj a review-megjegyzésekre, ha kell, push extra commitokat ugyanarra a branchre.

> Nem kell minden PR-t a maintainerrel előre egyeztetni — a kis bugfix-eket vagy egyértelmű enhancement-eket csak küldd be. Nagyobb feature-nél (új komponens, séma-változás) inkább előbb egy issue-ban beszéljük át.

---

## 🐘 PHP rész (`webapp/`)

A backend tisztán PHP, Twig templatekkel és egy Eloquent-alapú adatréteggel. Bootstrap + jQuery a frontenden, ami ide tartozik.

### Tesztek (PHPUnit)

```sh
# Minden teszt
bash scripts/docker-test.sh

# Csak egy testsuite
bash scripts/docker-test.sh --testsuite api
bash scripts/docker-test.sh --testsuite simple

# Coverage report
bash scripts/docker-coverage.sh
```

A coverage report a `webapp/tests/coverage/html/index.html`-ban érhető el.

### Tesztstruktúra

```
webapp/tests/
├── bootstrap.php              # tesztkörnyezet inicializálása
├── phpunit.xml                # PHPUnit konfiguráció
├── Unit/                      # egységtesztek
├── Api/                       # API tesztek
├── Request/                   # request helper tesztek
├── Rules/                     # szabálykezelő tesztek
├── Integration/               # integrációs tesztek
└── Functional/                # böngészős funkcionális tesztek
```

### Konvenciók

- **Fájl-elnevezés**: `<TesztelendőOsztály>Test.php`
- **Metódus-elnevezés**: `test`-tel kezdődik, pl. `testAddFavoriteAcceptsValidChurchId()`
- **Új tesztet** új fájlba a megfelelő mappába, `extends PHPUnit\Framework\TestCase`
- Docker-izolált környezetben fut, nem érinti a fejlesztői gépet

### Mit érdemes tesztelni

Prioritás szerint:
1. **API validáció és autentikáció** — biztonsági kritikus
2. **Pure functions** — könnyen tesztelhető, kevés mock
3. **Üzleti logika** — komplex döntés, számítás
4. **Edge case-ek** — határérték, hibakezelés

Amit *nem* érdemes:
- Framework / library kód (már tesztelt)
- Triviális getter/setter
- UI / template renderelés (inkább E2E)

---

## 🅰️ Angular naptár (`calendar/`)

A naptárnézet és naptár-szerkesztő Angular 19 alkalmazás, FullCalendar + Angular Material alapokon. A `miserend` PHP konténerba a `webapp/js/mcal/` alá kerül buildelve.

### Tesztek (Karma + Jasmine)

```sh
cd calendar
npx ng test --watch=false --browsers=ChromeHeadless
```

CI ugyanezt futtatja, csak `--browsers=ChromeHeadlessCI` (a `karma.conf.js`-ben definiált `--no-sandbox` flagekkel).

### Konvenciók

#### `xdescribe` — pendingben tartott stub spec

Az `ng generate component|service` automatikusan generál `*.spec.ts`-t egyetlen `should create` teszttel — de a TestBed providerek nélkül ezek a stubok futtatáskor **DI-hibára esnek** (No provider for ActivatedRoute / MatDialogRef / TranslateStore / stb.). Hogy a `ng test` ne legyen krónikusan piros, **az ilyen stubokat `xdescribe`-bal pendingbe tesszük** addig, amíg valaki rendesen ki nem egészíti őket TestBed mock-okkal.

**Promóció `describe`-bá**:
1. Add hozzá a hiányzó providereket (`MAT_DIALOG_DATA`, `MatDialogRef`, `TranslateModule.forRoot()`, mock service-ek)
2. Cseréld `xdescribe` → `describe`
3. Töröld a TODO kommentet a tetejéről
4. Adj valódi assertion-öket — nem csak `expect(component).toBeTruthy()`

#### Új teszt írása

- **Pure utility tesztek** (`src/app/util/*.spec.ts`): nem kell TestBed, csak importáld az osztályt és teszteld. Ezek a leggyorsabbak, legmegbízhatóbbak.
- **Komponens tesztek**: használj `TestBed.configureTestingModule()`-t a komponens import-jával és minden DI providerrel, amit a komponens injectál.

#### CI viselkedés

A `.github/workflows/ng-test.yml` workflow automatikusan fut minden `calendar/**`-t érintő PR-en. **A workflow akkor és csak akkor zöld, ha minden NEM-xdescribe-os teszt zöld.** Új teszt írása nem igényel workflow-módosítást.

### Naptár fejlesztése (build / deploy)

```sh
cd calendar
npm install      # ha még nem volt
ng build --configuration=localProd
npm run start:integrated
```

A `npm run start:integrated` watch módban indul: a forrás-változás után újrabuildel, majd egy Python script (`docker/miserend/calendar_deploy.py`) a kimenetet a `webapp/js/mcal/` alá másolja, ahonnan a PHP a `<script>` tag-eken keresztül betölti.

### Éles / staging build

```sh
cd calendar
ng build --configuration=production
python ../docker/miserend/calendar_deploy.py
```

---

## Egyéb hasznos

### Branching stratégia

- `master` ➜ staging környezet (`staging.miserend.hu`)
- `production` ➜ éles honlap (`miserend.hu`)

### Konténerekbe belépés

```sh
docker exec -it [mysql|mailcatcher|miserend] bash
```

### Composer használata

```sh
docker exec miserend composer install|require|update
```

### Helyi image build

```sh
docker build -t miserend:latest -f docker/miserend/Dockerfile .
```

Ha tesztelni szeretnéd, állítsd át a `docker/compose.dev.yml`-ben a `miserend` service `image` attribútumát `localhost/miserend:latest`-re.

### Dump

Adatbázis dump-hoz: `docker/mysql/dump.sh`. A szkript elején lévő változókat env-ben lehet felülbírálni — kényes adatokat előre takarít.

---

Ha bármi kérdésed van vagy elakadnál, nyiss issue-t bátran. Köszi az időt és a munkát! 🙏
