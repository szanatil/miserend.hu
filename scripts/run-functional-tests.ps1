#Requires -Version 5.1
<#
.SYNOPSIS
    Dockerized Panther functional test runner
    For CI/CD, see .github/workflows/phpunit.yml

.DESCRIPTION
    Runs Symfony Panther functional tests in a fully dockerized environment.
    Supports both docker and podman container runtimes.

.PARAMETER Runtime
    Container runtime to use (docker/podman). Auto-detected if not specified.

.PARAMETER Filter
    Run only tests matching this pattern

.PARAMETER ExcludeFilter
    Exclude tests matching this pattern

.PARAMETER Tag
    Docker image tag (default: latest)

.PARAMETER Help
    Show this help message

.EXAMPLE
    .\run-functional-tests-docker.ps1
    Run all functional tests in Docker

.EXAMPLE
    .\run-functional-tests-docker.ps1 -Filter "HomepageLogoTest"
    Run only HomepageLogoTest tests in Docker

.EXAMPLE
    .\run-functional-tests-docker.ps1 -Tag "2026.4.18" -Filter "HomePage"
    Run HomePage tests using a specific image tag

#>

param(
    [string]$Runtime,
    [string]$Filter,
    [string]$ExcludeFilter,
    [string]$Tag = "latest",
    [switch]$Help
)

$ErrorActionPreference = "Stop"

# Show help
if ($Help) {
    Get-Help $PSCommandPath -Full
    exit 0
}

# Function to detect container runtime
function Detect-Runtime {
    if ($null -ne (Get-Command docker -ErrorAction SilentlyContinue)) {
        return "docker"
    } elseif ($null -ne (Get-Command podman -ErrorAction SilentlyContinue)) {
        return "podman"
    } else {
        throw "Error: Neither docker nor podman found. Please install Docker Desktop or Podman."
    }
}

# Get project root
$ScriptDir = $PSScriptRoot
$ProjectRoot = Split-Path -Parent $ScriptDir
$WebappDir = Join-Path $ProjectRoot "webapp"
$TestsDir = Join-Path $WebappDir "tests"
$FunctionalTestDir = Join-Path $TestsDir "Functional"
$DockerDir = Join-Path $ProjectRoot "docker"
$TestResultsDir = Join-Path $ProjectRoot "test-results"

Write-Host "Dockerized Panther Functional Test Runner" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""

# Detect container runtime if not specified
if (-not $Runtime) {
    Write-Host "Detecting container runtime..." -ForegroundColor Yellow
    try {
        $Runtime = Detect-Runtime
        Write-Host "Using runtime: $Runtime" -ForegroundColor Green
    } catch {
        Write-Host "Error: $_" -ForegroundColor Red
        exit 1
    }
} else {
    # Verify specified runtime exists
    if ($null -eq (Get-Command $Runtime -ErrorAction SilentlyContinue)) {
        Write-Host "Container runtime '$Runtime' not found" -ForegroundColor Red
        exit 1
    }
}

Write-Host ""

# Check prerequisites
Write-Host "Checking prerequisites..." -ForegroundColor Yellow

# Check if tests directory exists
if (-not (Test-Path $FunctionalTestDir)) {
    Write-Host "Functional tests directory not found: $FunctionalTestDir" -ForegroundColor Red
    exit 1
}

# Check if docker-compose files exist
$ComposeBase = Join-Path $DockerDir "compose.yml"
$ComposeDev = Join-Path $DockerDir "compose.dev.yml"
$ComposeTest = Join-Path $DockerDir "compose.test.yml"

if (-not (Test-Path $ComposeBase)) {
    Write-Host "Docker compose file not found: $ComposeBase" -ForegroundColor Red
    exit 1
}

if (-not (Test-Path $ComposeDev)) {
    Write-Host "Docker compose file not found: $ComposeDev" -ForegroundColor Red
    exit 1
}

if (-not (Test-Path $ComposeTest)) {
    Write-Host "Docker compose file not found: $ComposeTest" -ForegroundColor Red
    exit 1
}

# Verify phpunit.functional.xml exists
$FunctionalConfig = Join-Path $TestsDir "phpunit.functional.xml"
if (-not (Test-Path $FunctionalConfig)) {
    Write-Host "phpunit.functional.xml not found: $FunctionalConfig" -ForegroundColor Red
    exit 1
}

Write-Host "All prerequisites met" -ForegroundColor Green
Write-Host ""

# Create test results directory
if (-not (Test-Path $TestResultsDir)) {
    New-Item -ItemType Directory -Force -Path $TestResultsDir | Out-Null
}

Write-Host "Running Panther functional tests in Docker..." -ForegroundColor Cyan
Write-Host "Container Runtime: $Runtime" -ForegroundColor Cyan
Write-Host "Image Tag: $Tag" -ForegroundColor Cyan
Write-Host ""

# Change to project root for docker compose
Push-Location $ProjectRoot

try {
    # Build PHPUnit command arguments
    # Always ensure composer install runs to get all functional test dependencies
    $phpunitCmd = "composer install --no-interaction --no-progress && php vendor/bin/phpunit -c tests/phpunit.functional.xml"
    
    if ($Filter) {
        $phpunitCmd += " --filter '$Filter'"
    }
    
    if ($ExcludeFilter) {
        $phpunitCmd += " --exclude-filter '$ExcludeFilter'"
    }
    
    $phpunitCmd += ' --log-junit /miserend/webapp/fajlok/tmp/tests/functional-junit.xml'

    Write-Host "Executing docker compose run..." -ForegroundColor DarkGray
    Write-Host ""

    # Execute docker/podman compose with the shell command
    # Following the same pattern as docker-test.sh but for functional tests
    & $Runtime compose -f docker/compose.yml -f docker/compose.dev.yml -f docker/compose.test.yml run --rm --entrypoint sh miserend -lc $phpunitCmd
    
    $exitCode = $LASTEXITCODE

} catch {
    Write-Host "Error running tests: $_" -ForegroundColor Red
    $exitCode = 1
} finally {
    Pop-Location
}

# Output results
Write-Host ""
Write-Host "=============================================" -ForegroundColor Cyan

if ($exitCode -eq 0) {
    Write-Host "All tests passed!" -ForegroundColor Green
    Write-Host "Results: $TestResultsDir\functional-junit.xml" -ForegroundColor Green
} else {
    Write-Host "Tests failed (exit code: $exitCode)" -ForegroundColor Red
}

exit $exitCode
