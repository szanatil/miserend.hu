# 🧪 Tesztelési dokumentáció

A projektben 3 féle teszt van, mindegyik más-más célt szolgál.

---

## 1️⃣ **ng-test** — Angular unit tesztek

### Jellemzői
- **Framework**: Karma + Jasmine
- **Helye**: `calendar/src/app/` (komponensek mellett `*.spec.ts` fájlok)
- **Célja**: Angular naptár komponensek logikájának tesztelése
- **Futási idő**: ~10-30 másodperc

### Milyen dolgokra van kitalálva
- Komponensek viselkedésének (logika, metódusok) tesztelése
- Service-ek funkcionalitásának ellenőrzése
- Direktívák és pipe-ok működésének verifikálása
- UI interakciók szimulálása (pl. gomb kattintás)

### Futtatás

#### Lokálisan (fejlesztés közben)
```bash
cd calendar
npm test
```
Figyel a fájlok változására és automatikusan újrafuttatja a teszteket.

#### CI/CD-ben (GitHub Actions)
```bash
cd calendar
npx ng test --watch=false --browsers=ChromeHeadlessCI
```
Workflow: `.github/workflows/ng-test.yml`

### Eredmények
- **Konzolos output** — terminálban látható az eredmény
- **Nem generál artifactot** — az output rögtön látható a GitHub Actions-ben
- **Trigger**: `calendar/**` útvonal módosítása vagy manual trigger

### Új teszt írása
```typescript
// calendar/src/app/components/my-component.spec.ts
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MyComponent } from './my-component';

describe('MyComponent', () => {
  let component: MyComponent;
  let fixture: ComponentFixture<MyComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MyComponent ]
    })
    .compileComponents();
    
    fixture = TestBed.createComponent(MyComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should do something', () => {
    // teszt logika
    expect(true).toBe(true);
  });
});
```

**Konvenció**: `xdescribe()` vagy `xit()` a kihagyandó tesztekhez (fejlesztés alatt).

---

## 2️⃣ **PHPUnit** — PHP unit és integration tesztek

### Jellemzői
- **Framework**: PHPUnit 10+
- **Helye**: `webapp/tests/` (Unit/, Integration/, Api/ mappák)
- **Célja**: PHP backend logika tesztelése (unit teszt), osztályok közötti interakciók (integration teszt)
- **Futási idő**: ~30-60 másodperc
- **Konfig**: `webapp/tests/phpunit.xml`

### Milyen dolgokra van kitalálva
- Osztályok metódusainak tesztelése (unit)
- Datbázisinterakciók tesztelése (integration)
- API végpontok ellenőrzése (API teszt)
- Üzleti logika validálása

### Futtatás

#### Gyors lokális futtatás (Docker szükséges)
```bash
./scripts/docker-test.sh
```

#### Specifikus teszt futtatása
```bash
./scripts/docker-test.sh -- --filter MyTestClassName
./scripts/docker-test.sh -- --filter testMethodName
```

#### Coverage report generálása
```bash
./scripts/docker-coverage.sh
```
Eredmény: `webapp/tests/coverage/html/index.html` (lokálisan)

#### CI/CD-ben (GitHub Actions)
Workflow: `.github/workflows/phpunit.yml`
- Unit/Integration tesztek futnak
- Coverage report generálódik
- Artifactként feltöltik: `phpunit-coverage-html`, `phpunit-coverage-cobertura`

### Eredmények
- **Coverage report**: `webapp/tests/coverage/html/index.html`
- **JUnit XML**: `webapp/tests/coverage/junit.xml` (CI-ben)
- **Cobertura XML**: `webapp/tests/coverage/cobertura.xml` (CI-ben)
- **GitHub Actions artifacts**: Letölthető az Actions runból

### Mappa struktúra
```
webapp/tests/
├── Unit/              # Izolált unit tesztek (nincs DB, csak mock)
├── Integration/       # Integration tesztek (valódi DB, adatbázis interakció)
├── Api/               # API endpoint tesztek
├── Functional/        # Funkcionális tesztek (Panther, lásd lent)
├── bootstrap.php      # Unit/Integration tesztek bootstrapja
├── phpunit.xml        # Unit/Integration konfigurálása
└── phpunit.functional.xml  # Panther konfig
```

### Új teszt írása
```php
<?php
// webapp/tests/Unit/MyClassTest.php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\MyClass;

class MyClassTest extends TestCase
{
    private MyClass $myClass;

    protected function setUp(): void
    {
        $this->myClass = new MyClass();
    }

    public function test_something(): void
    {
        $result = $this->myClass->doSomething();
        $this->assertTrue($result);
    }

    public function test_another_thing(): void
    {
        $result = $this->myClass->getNumber();
        $this->assertEquals(42, $result);
    }
}
```

**Integration teszt** (adatbázis használattal):
```php
<?php
// webapp/tests/Integration/UserTest.php
namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    public function test_can_create_user(): void
    {
        $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }
}
```

---

## 3️⃣ **Panther** — Funkcionális/E2E tesztek

### Jellemzői
- **Framework**: Symfony Panther (PHPUnit alapú, Selenium-like)
- **Helye**: `webapp/tests/Functional/` mappában
- **Célja**: Teljes böngésző automatizálás, UI interakciók, funkcionális flow-k tesztelése
- **Futási idő**: 1-3 percek (böngésző indítása miatt lassabb)
- **Konfig**: `webapp/tests/phpunit.functional.xml`

### Milyen dolgokra van kitalálva
- Teljes felhasználói flow-k tesztelése (login, adatbevitel, keresés)
- JavaScript renderelés és DOM interakció tesztelése
- Képernyő vizuális ellenőrzése (ha kell screenshot)
- API-hoz hasonló elemek működésének böngészőből való tesztelése
- Linkek, gombnyomások, forma kitöltés valós böngészőben

### Futtatás

#### Lokálisan (Docker szükséges)
```bash
./scripts/docker-test-panther.sh
```

#### Specifikus teszt futtatása
```bash
./scripts/docker-test-panther.sh -- --filter HomepageLogoTest
```

#### CI/CD-ben (GitHub Actions)
Workflow: `.github/workflows/phpunit.yml` (ugyanaz, de eltérő konfig)
- Chromium böngészőt használ
- Panther függőségeket telepíti
- `PANTHER_EXTERNAL_BASE_URI=http://miserend:8000` felhasználóval fut
- JUnit XML report generálódik

### Eredmények
- **JUnit XML**: `webapp/test-results/functional-junit.xml` (CI-ben)
- **GitHub Actions artifacts**: Letölthető az Actions runból
- **Screenshot-ok**: Ha a tesztben generálnak (`$client->takeScreenshot()`)

### Új teszt írása
```php
<?php
// webapp/tests/Functional/HomepageTest.php
namespace Tests\Functional;

use Symfony\Component\Panther\PantherTestCase;

class HomepageTest extends PantherTestCase
{
    public function test_homepage_loads(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'miserend.hu');
    }

    public function test_search_churches(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        
        // Formot kitöltés
        $form = $crawler->selectButton('Keresés')->form();
        $form['query'] = 'Budapest';
        $crawler = $client->submit($form);
        
        // Ellenőrzés
        $this->assertPageTitleContains('Keresési eredmények');
        $this->assertSelectorExists('.church-result');
    }

    public function test_javascript_interaction(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        
        // Dinamikus elem kattintás (JavaScript szükséges)
        $crawler->selectButton('Toggle Menu')->click();
        $client->waitFor('.menu.open', 5); // Maximum 5 másodpercig vár
        
        $this->assertSelectorExists('.menu.open');
    }
}
```

---

## 📊 Teszt szintaxis összehasonlítása

| Szint | ng-test | PHPUnit | Panther |
|-------|---------|---------|---------|
| **Unit** | Jasmine test() | PHPUnit test_*() | - |
| **Integration** | - | PHPUnit + TestCase | - |
| **Funkcionális** | - | - | Panther + UI |
| **Mock/Stub** | jasmine.createSpy() | Mockery/PHPUnit stubs | - |
| **DB teszt** | - | TestCase (migrations) | Panther (valós) |
| **Async** | done(), fakeAsync | - | $client->waitFor() |

---

## 🔄 CI/CD workflow

```
Pull Request / Push → GitHub Actions
│
├─→ ng-test.yml (calendar/**) 
│   └─ npm test --watch=false
│
└─→ phpunit.yml (minden más vagy manual)
    ├─ PHPUnit (Unit + Integration)
    │  └─ coverage report → artifact
    └─ Panther (Functional)
       └─ JUnit report → artifact
```

### Eredmények megtekintése
1. **GitHub Actions**: Actions tab → workflow run → "Summary"
2. **Artifacts**: Lent letölthető:
   - `phpunit-coverage-html` — Coverage HTML report
   - `phpunit-coverage-cobertura` — XML report (fejlesztő tool integrációhoz)

---

## 💡 Tesztelési best practices

### ✅ Csináld
- **Descriptive nevek**: `test_should_return_user_when_id_is_valid()`
- **Egy assert per teszt** (vagy logikailag összetartozó assertek)
- **Setup-Teardown**: setUp() és tearDown() metódusok
- **Izolálás**: Mock-olj külső függőségeket (API-k, fájlok, stb.)
- **Edge caseok**: null, üres, negativok, limit határok

### ❌ Ne csináld
- **Teszttől tesztig függőség**: Tesztek sorrendje érdektelen legyen
- **Valódi API hívások**: Mock-old le
- **Hardcoded időpontok**: Dátum-idő függőségeket injektálj
- **Hosszú tesztek**: Ha 1 perc > futási idő, refactorolj
- **Global state módosítása**: Database tranzakciók, config változók

---

## 🚀 Gyors referencia

```bash
# Angular tesztek
cd calendar && npm test

# PHP unit tesztek + coverage
./scripts/docker-coverage.sh

# Funkcionális tesztek
./scripts/docker-test-panther.sh

# Specifikus teszt
./scripts/docker-test.sh -- --filter "TestClassName"

# Meglévő konténerben
docker exec miserend php vendor/bin/phpunit -c tests/phpunit.xml
```

---

## 📖 További dokumentáció

- **Angular komponensek**: [CONTRIBUTING.md → Angular fejezet](../../CONTRIBUTING.md#-angular-naptár-calendar)
- **PHP best practices**: [CONTRIBUTING.md → PHP fejezet](../../CONTRIBUTING.md#-php-rész-webapp)
- **PHPUnit dokumentáció**: https://phpunit.de/
- **Panther dokumentáció**: https://symfony.com/doc/current/testing.html
- **Karma dokumentáció**: https://karma-runner.github.io/
