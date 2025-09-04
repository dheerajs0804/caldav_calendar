# Mithi Calendar Backend Test Runner - PowerShell Version
Write-Host "=== Mithi Calendar Backend Test Runner ===" -ForegroundColor Cyan
Write-Host ""

# Try to find PHP in common locations
$phpPaths = @(
    "C:\xampp\php\php.exe",
    "C:\wamp\bin\php\php8.1.0\php.exe", 
    "C:\wamp64\bin\php\php8.1.0\php.exe",
    "C:\laragon\bin\php\php-8.1.0-Win32-VS16-x64\php.exe",
    "php.exe"
)

$phpFound = $false

foreach ($path in $phpPaths) {
    if (Test-Path $path) {
        Write-Host "Found PHP at: $path" -ForegroundColor Green
        Write-Host ""
        
        try {
            & $path "tests\backend\simple_test_runner.php"
            $phpFound = $true
            break
        }
        catch {
            Write-Host "Error running PHP: $_" -ForegroundColor Red
        }
    }
}

if (-not $phpFound) {
    Write-Host "ERROR: PHP not found in common locations." -ForegroundColor Red
    Write-Host "Please ensure XAMPP, WAMP, or Laragon is installed and PHP is in your PATH." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "You can also manually run:" -ForegroundColor Yellow
    Write-Host "  C:\xampp\php\php.exe tests\backend\simple_test_runner.php" -ForegroundColor White
    Write-Host ""
}

Write-Host "Press any key to continue..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
