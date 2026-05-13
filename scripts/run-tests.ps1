#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Runs PHPUnit tests with coverage for the miserend.hu project

.DESCRIPTION
    This script starts the Docker Compose stack and executes PHPUnit tests
    with code coverage reports (HTML, JUnit XML, Cobertura XML)

.EXAMPLE
    ./run-tests.ps1
    run-tests.ps1

.NOTES
    Requires Docker and Docker Compose to be installed
    Script should be run from the project root directory
#>

[CmdletBinding()]
param(
    [switch]$Help
)

if ($Help) {
    Get-Help -Full $MyInvocation.MyCommand.Path
    exit 0
}

# Configuration
$ProjectRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$TestResultsPath = Join-Path $ProjectRoot "test-results"
$CoverageHtmlPath = Join-Path -Path (Join-Path -Path $TestResultsPath -ChildPath "coverage") -ChildPath "html"
$DockerComposeFiles = @("-f", "docker/compose.yml", "-f", "docker/compose.dev.yml", "-f", "docker/compose.coverage.yml")

# Colors for output
$ErrorColor = "Red"
$SuccessColor = "Green"
$InfoColor = "Cyan"
$WarningColor = "Yellow"

function Write-Info {
    param([string]$Message)
    Write-Host "[INFO] $Message" -ForegroundColor $InfoColor
}

function Write-Success {
    param([string]$Message)
    Write-Host "[OK] $Message" -ForegroundColor $SuccessColor
}

function Write-Error-Custom {
    param([string]$Message)
    Write-Host "[ERROR] $Message" -ForegroundColor $ErrorColor
}

function Write-Warning-Custom {
    param([string]$Message)
    Write-Host "[WARN] $Message" -ForegroundColor $WarningColor
}

try {
    Write-Info "Starting test environment..."
    
    # Create test directories
    Write-Info "Setting up test directories..."
    $null = New-Item -ItemType Directory -Path $CoverageHtmlPath -Force -ErrorAction SilentlyContinue
    Write-Success "Test directories created"

    # Check if Docker is available
    Write-Info "Checking Docker availability..."
    $DockerCheck = docker --version 2>$null
    if ($LASTEXITCODE -ne 0) {
        throw "Docker is not installed or not available in PATH"
    }
    Write-Success "Docker is available: $DockerCheck"

    # Start Docker Compose stack
    Write-Info "Starting Docker Compose stack..."
    Push-Location $ProjectRoot
    try {
        & docker compose $DockerComposeFiles up -d
        if ($LASTEXITCODE -ne 0) {
            throw "Failed to start Docker Compose stack"
        }
        Write-Success "Docker Compose stack started"
    } finally {
        Pop-Location
    }

    # Wait for application to be ready
    Write-Info "Waiting for application to be ready..."
    $MaxWaitTime = 300  # 5 minutes
    $ElapsedTime = 0
    $CheckInterval = 2

    while ($ElapsedTime -lt $MaxWaitTime) {
        try {
            $Response = Invoke-WebRequest -Uri "http://127.0.0.1:8000/" -UseBasicParsing -TimeoutSec 2 -ErrorAction SilentlyContinue
            if ($Response.StatusCode -eq 200) {
                Write-Success "Application is ready"
                break
            }
        } catch {
            # Application not ready yet
        }
        
        Start-Sleep -Seconds $CheckInterval
        $ElapsedTime += $CheckInterval
        Write-Host "." -NoNewline -ForegroundColor $InfoColor
    }

    if ($ElapsedTime -ge $MaxWaitTime) {
        throw "Application did not become ready within $MaxWaitTime seconds"
    }
    Write-Host ""  # New line after dots

    # Run PHPUnit tests with coverage
     Write-Info "Running PHPUnit tests with coverage..."
     Push-Location $ProjectRoot
     try {
         # Capture output to both display and process for deprecations
         $OutputFile = Join-Path $TestResultsPath "phpunit-output.txt"
         $null = New-Item -ItemType File -Path $OutputFile -Force -ErrorAction SilentlyContinue
         
         & docker compose $DockerComposeFiles exec -T miserend /miserend/webapp/vendor/bin/phpunit `
             -c /miserend/webapp/tests/phpunit.xml `
             --coverage-html /miserend/test-results/coverage/html `
             --log-junit /miserend/test-results/unit-junit.xml `
             --coverage-cobertura /miserend/test-results/coverage/cobertura.xml 2>&1 | Tee-Object -FilePath $OutputFile
         
         $PHPUnitExitCode = $LASTEXITCODE
         
         if ($PHPUnitExitCode -ne 0) {
             Write-Warning-Custom "PHPUnit tests completed with exit code: $PHPUnitExitCode"
         } else {
             Write-Success "PHPUnit tests completed successfully"
         }
         
         # Analyze deprecations from output and JSON log
        Write-Host ""
        Write-Info "Analyzing deprecations and warnings..."
        
        # First check for PHPUnit deprecations in the output
        $Output = Get-Content $OutputFile -Raw
        if ($Output -match "PHPUnit\s+Deprecations?:\s+(\d+)") {
            $PHPUnitDepCount = [int]$matches[1]
            if ($PHPUnitDepCount -gt 0) {
                Write-Warning-Custom "Found $PHPUnitDepCount PHPUnit deprecation(s)"
                Write-Info "These are deprecation warnings from PHPUnit itself."
            }
        }
        
        # Check for application-level deprecations
        $DeprecationLogFile = Join-Path $TestResultsPath "deprecations-by-test.json"
        
        if (Test-Path $DeprecationLogFile) {
            try {
                $DeprecationLog = Get-Content $DeprecationLogFile -Raw | ConvertFrom-Json
                
                $TotalDeprecations = 0
                foreach ($Test in $DeprecationLog.PSObject.Properties) {
                    $TotalDeprecations += $Test.Value.Count
                }
                
                if ($TotalDeprecations -gt 0) {
                    Write-Host ""
                    Write-Warning-Custom "Application deprecations found in $($DeprecationLog.PSObject.Properties.Count) test(s):"
                    
                    foreach ($Test in $DeprecationLog.PSObject.Properties | Sort-Object -Property Value -Descending) {
                        $TestName = $Test.Name
                        $DeprecationCount = $Test.Value.Count
                        Write-Host "  • $TestName ($DeprecationCount)" -ForegroundColor $WarningColor
                        
                        # Show first few deprecations for each test
                        $ShowCount = [Math]::Min(2, $DeprecationCount)
                        for ($i = 0; $i -lt $ShowCount; $i++) {
                            $Dep = $Test.Value[$i]
                            $Location = if ($Dep.file) { " at $($Dep.file):$($Dep.line)" } else { "" }
                            Write-Host "    - $($Dep.message)$Location" -ForegroundColor $WarningColor
                        }
                        
                        if ($DeprecationCount -gt 2) {
                            Write-Host "    ... and $($DeprecationCount - 2) more" -ForegroundColor $WarningColor
                        }
                    }
                    Write-Host ""
                }
            } catch {
                Write-Info "Could not parse application deprecation log: $_"
            }
        }
        
        Write-Info "Full PHPUnit output saved to: $OutputFile"
     } finally {
         Pop-Location
     }

    # Display coverage results location
    Write-Info "Test results saved to: $TestResultsPath"
    Write-Info "Coverage HTML report: $CoverageHtmlPath"
    
    if (Test-Path $CoverageHtmlPath) {
        Write-Success "Coverage HTML report generated"
        Write-Info "Open coverage report: $CoverageHtmlPath\index.html"
    }

    # Summary
    Write-Host ""
    Write-Success "Test execution completed!"
    Write-Info "Docker Compose stack is still running. Use 'docker compose ... down' to stop it."

} catch {
    Write-Error-Custom "Test execution failed: $_"
    Write-Info "Attempting to stop Docker Compose stack..."
    
    try {
        Push-Location $ProjectRoot
        & docker compose $DockerComposeFiles down -v 2>$null
        Pop-Location
    } catch {
        # Ignore cleanup errors
    }
    
    exit 1
} finally {
    Write-Host ""
}
