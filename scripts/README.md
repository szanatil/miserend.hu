# 🧪 Test Runner Scripts

Gyűjtemény PowerShell és Bash scriptekről a PHPUnit és Panther funkcionális tesztek futtatásához.

## 📚 Elérhető scriptlek

### 1. **run-tests.ps1** 🪟 Windows PowerShell (Docker)
PHPUnit unit tesztek futtatása Docker Compose containerben code coverage reportokkal.

**Követelmények:**
- Docker és Docker Compose
- PowerShell 5.1+

**Alapvető használat:**
```powershell
.\run-tests.ps1
```

**Paraméterek:**
```powershell
.\run-tests.ps1 -Help          # Help megjelenítése
```

**Kimenet:**
- Code coverage HTML report: `test-results/coverage/html/`
- JUnit XML report: `test-results/unit-junit.xml`
- Cobertura XML report: `test-results/coverage/cobertura.xml`

---

### 2. **run-functional-tests.ps1** 🪟 Windows PowerShell
Symfony Panther funkcionális tesztek futtatása a Chromium/Chrome böngészővel.

**Követelmények:**
- PHP 8.2+
- Google Chrome / Chromium
- ChromeDriver
- PowerShell 5.1+

**Alapvető használat:**
```powershell
.\run-functional-tests.ps1
```

**Paraméterek:**
```powershell
.\run-functional-tests.ps1 -Help          # Help megjelenítése
.\run-functional-tests.ps1 -Filter "Test" # Szűrés teszt név alapján
.\run-functional-tests.ps1 -BaseUri "http://localhost:8001"
.\run-functional-tests.ps1 -NoSandbox     # No-sandbox Chrome módban
.\run-functional-tests.ps1 -ChromeDriverPath "C:\path\to\chromedriver.exe"
```

**Dokumentáció:**
- **Gyors kezdés:** `FUNCTIONAL-TESTS-QUICKSTART.md`
- **Teljes útmutató:** `FUNCTIONAL-TESTS-README.md`

---

### 3. **run-tests.sh** 🐧 Bash (Linux/macOS/WSL)
PHPUnit unit tesztek futtatása Docker containerben.

**Követelmények:**
- Docker vagy Podman
- Bash shell

**Alapvető használat:**
```bash
./run-tests.sh
```

**Paraméterek:**
```bash
./run-tests.sh --help              # Help megjelenítése
./run-tests.sh --coverage          # Code coverage generálás
./run-tests.sh --tag 2026.4.18     # Specifikus image tag
./run-tests.sh --filter MyTest     # Szűrés teszt név alapján
./run-tests.sh -- --filter MyTest  # PHPUnit paraméterek
```

**Docker runtime:**
```bash
./run-tests.sh --runtime docker    # Docker explicit megadása
./run-tests.sh --runtime podman    # Podman explicit megadása
```

---

### 4. **docker-test.sh** 🐳 Docker Test Runner
Docker Compose alapú PHPUnit unit tesztek futtatása anélkül, hogy manuálisan telepítené a függőségeket.

**Követelmények:**
- Docker és Docker Compose
- Bash shell

**Alapvető használat:**
```bash
./docker-test.sh
```

**Funkciók:**
- Automatikusan felépíti a Docker image-et
- Futtatja a PHPUnit teszteket
- Megjeleníti az eredményeket a konzolban
- Tökéletes CI/CD pipeline-okhoz

**Kimenet:**
- JUnit XML report: `test-results/unit-junit.xml`

---

### 5. **docker-coverage.sh** 📊 Docker Coverage Runner
Docker Compose alapú PHPUnit unit tesztek futtatása code coverage reporttal.

**Követelmények:**
- Docker és Docker Compose
- Bash shell

**Alapvető használat:**
```bash
./docker-coverage.sh
```

**Funkciók:**
- Automatikusan felépíti a Docker image-et
- Futtatja a PHPUnit teszteket code coverage analízissel
- HTML coverage report generálása
- XML Cobertura format a CI/CD integrációhoz

**Kimenet:**
- Code coverage HTML report: `test-results/coverage/html/`
- JUnit XML report: `test-results/unit-junit.xml`
- Cobertura XML report: `test-results/coverage/cobertura.xml`

**Használati eset:**
```bash
# Code coverage report generálása
./docker-coverage.sh

# Eredmény megtekintése böngészőben
open test-results/coverage/html/index.html  # macOS
xdg-open test-results/coverage/html/index.html  # Linux
```

---

## 🔍 Script kiválasztás

| Forgatókönyv | Script | Platform |
|-------------|--------|----------|
| Unit tesztek Docker-ben (Windows) | `run-tests.ps1` | Windows |
| Helyi fejlesztés Windows-on | `run-functional-tests.ps1` | Windows |
| Unit tesztek Docker-ben | `run-tests.sh` | Linux/WSL/macOS |
| Funkcionális tesztek Docker-ben | `run-tests.sh` + config | Linux/WSL/macOS |
| CI/CD pipeline | `.github/workflows/phpunit.yml` | GitHub Actions |
| Gyors lokális tesztek | `run-functional-tests.ps1` | Windows |
| Code coverage report | `run-tests.ps1` (Windows) vagy `run-tests.sh --coverage` | Windows / Linux/WSL/macOS |

---

## 🛠️ Telepítés előfeltételei

### Windows (PowerShell scriptekhez)
```powershell
# Chocolatey-vel (ajánlott)
choco install php googlechrome chromedriver composer

# Vagy manuálisan letöltve
# - PHP: https://windows.php.net/download/
# - Chrome: https://www.google.com/chrome/
# - ChromeDriver: https://chromedriver.chromium.org/
# - Composer: https://getcomposer.org/
```

### Linux/WSL (Bash scriptekhez)
```bash
# Docker telepítése
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Vagy WSL2-ben
# PowerShell-ben: wsl --install
```

---

## 📋 Teszt típusok

### Unit Tesztek (`webapp/tests/Unit/`)
```bash
./run-tests.sh
```
Tesztelik az egyes osztályokat és függvényeket izoláltan.

### Integrációs Tesztek (`webapp/tests/Integration/`)
```bash
./run-tests.sh --filter Integration
```
Tesztelik az összetevők közötti interakciót.

### API Tesztek (`webapp/tests/Api/`)
```bash
./run-tests.sh --filter Api
```
Tesztelik az API végpontokat.

### Funkcionális Tesztek (`webapp/tests/Functional/`)
```powershell
# Windows
.\run-functional-tests.ps1

# Linux/WSL
./run-tests.sh -c phpunit.functional.xml
```
Tesztelik a teljes felhasználói felületi interakciókat böngészőben.

---

## 📊 Eredmények és Reportok

### JUnit XML Reports
```
test-results/
├── unit-junit.xml           # Unit tesztek eredménye
└── functional-junit.xml     # Funkcionális tesztek eredménye
```

### Coverage Reports (HTML)
```
test-results/coverage/
└── html/
    └── index.html           # Code coverage vizualizáció
```

---

## 🚀 CI/CD Integráció

### GitHub Actions
Lásd `.github/workflows/phpunit.yml` a teljes workflow-hez.

```yaml
# Automatikusan futnak:
# - Unit tesztek: `run-tests.sh`
# - Funkcionális tesztek: PowerShell script
# - Coverage reportok generálása
# - JUnit XML publikálása
```

---

## 🔧 Fejlesztői workflow

### 1. Fejlesztés
```powershell
# Módosítson egy fájlt és szeretné tesztelni
.\run-functional-tests.ps1 -Filter "YourTest"
```

### 2. Code Review előtt
```powershell
# Összes teszt futtatása
.\run-functional-tests.ps1

# Vagy Docker-ben
./run-tests.sh --coverage
```

### 3. Push előtt
```powershell
# Végső ellenőrzés
.\run-functional-tests.ps1 -NoVendor  # feltételezve, hogy vendor már telepítve van
```

---

## 🎯 Tipikus parancsok

```powershell
# Windows - Unit tesztek Docker-ben
.\run-tests.ps1

# Windows - Összes funkcionális teszt
.\run-functional-tests.ps1

# Windows - Csak egy teszt
.\run-functional-tests.ps1 -Filter "HomepageLogoTest"

# Windows - Egyedi szerverrel
.\run-functional-tests.ps1 -BaseUri "http://myapp.local:8000"

# Windows - No-sandbox (Docker/CI)
.\run-functional-tests.ps1 -NoSandbox

# Linux/WSL - Unit tesztek
./run-tests.sh

# Linux/WSL - Coverage report
./run-tests.sh --coverage

# Linux/WSL - Specifikus teszt
./run-tests.sh -- --filter MyTestClass
```

---

## 🆘 Hibaelhárítás

### Teljes hibaelhárítási útmutató
Windows PowerShell: `FUNCTIONAL-TESTS-README.md` → Hibaelhárítás

### Gyakori problémák

| Hiba | Script | Megoldás |
|------|--------|----------|
| "PHP not found" | run-functional-tests.ps1 | `choco install php` |
| "Chrome not found" | run-functional-tests.ps1 | `choco install googlechrome` |
| "ChromeDriver not found" | run-functional-tests.ps1 | Töltse le: https://chromedriver.chromium.org/ |
| "Docker not running" | run-tests.sh | `docker-compose up -d` |
| "Permission denied" | run-tests.sh | `chmod +x run-tests.sh` |
| Port foglalatban | Mindkettő | `netstat -ano \| findstr :8000` (Windows) |

---

## 📖 Dokumentáció

- **Funkcionális tesztek (Windows):**
  - Gyors kezdés: `FUNCTIONAL-TESTS-QUICKSTART.md`
  - Teljes útmutató: `FUNCTIONAL-TESTS-README.md`
  
- **Unit tesztek (Docker):**
  - Dokumentáció: `run-tests.sh -h`

- **GitHub Actions CI/CD:**
  - Workflow: `.github/workflows/phpunit.yml`

---

## 🤝 Közreműködés

A teszt scriptek a GitHub Actions workflow-kal szinkronban tartva lesznek. Módosítások esetén:

1. Frissítse mindkét scriptet (ha szükséges)
2. Tesztelje lokálisan
3. Pull request benyújtása

---

## 📅 Verzió történet

| Verzió | Dátum | Módosítások |
|--------|-------|------------|
| 1.1 | 2026-05-13 | `run-tests.ps1` hozzáadása Docker-ben futó unit tesztekhez |
| 1.0 | 2026-05-13 | Kezdeti release |

---

## 📞 Támogatás

- **Hibajelentés**: GitHub Issues
- **Dokumentáció**: Ez a fájl és a linked README-k
- **Kérdések**: Nézze meg a megfelelő `README.md` fájlt az adott script mellett

---

**Utolsó frissítés**: 2026-05-13
