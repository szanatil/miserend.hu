# 🧪 Test Scripts

Ez a könyvtár a teszt futtatási scripteket tartalmazza. Háromféle teszt van:

## 📋 Script-teszt típus leképezés

| Script | Teszt típus | Leírás |
|--------|-----------|--------|
| `npm test` (calendar/) | **ng-test** | Angular unit tesztek (Karma + Jasmine) |
| `run-tests.sh` | **PHPUnit** | PHP unit/integration tesztek |
| `docker-test.sh` | **PHPUnit** | PHP tesztek Docker konténerben |
| `docker-test-panther.sh` | **Panther** | Funkcionális/browser tesztek (Selenium-like) |
| `docker-coverage.sh` | **PHPUnit + Coverage** | Coverage reporttal futó tesztek |

## 🎯 Melyik scriptet mikor használd?

### Gyors lokális tesztek
```bash
# Angular naptár tesztek
cd calendar
npm test

# PHP tesztek (meglévő dev konténerrel)
./scripts/docker-test.sh
```

### Coverage report generálás
```bash
./scripts/docker-coverage.sh
# Az eredmény: webapp/tests/coverage/html/index.html
```

### Funkcionális tesztek (Panther)
```bash
./scripts/docker-test-panther.sh
```

### CI/CD (GitHub Actions)
Automatikusan futnak a pull requestek és push-ok során:
- `ng-test.yml` — Angular tesztek
- `phpunit.yml` — PHP unit és funkcionális tesztek (egyidejűleg)

## 📝 Részletekért lásd: [webapp/tests/README.md](../webapp/tests/README.md)
