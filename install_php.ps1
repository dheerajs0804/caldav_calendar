# PHP Installation Script for Windows
# Run this as Administrator

Write-Host "========================================" -ForegroundColor Green
Write-Host "    PHP Installation Script" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

# Check if running as Administrator
if (-NOT ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    Write-Host "❌ This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "Right-click PowerShell and select 'Run as Administrator'" -ForegroundColor Yellow
    Read-Host "Press Enter to continue..."
    exit
}

Write-Host "✅ Running as Administrator" -ForegroundColor Green
Write-Host ""

# Check if Chocolatey is installed
Write-Host "🔍 Checking if Chocolatey is installed..." -ForegroundColor Yellow
try {
    $chocoVersion = choco --version
    Write-Host "✅ Chocolatey is installed: $chocoVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ Chocolatey not found. Installing..." -ForegroundColor Red
    
    # Install Chocolatey
    Write-Host "📥 Installing Chocolatey..." -ForegroundColor Yellow
    Set-ExecutionPolicy Bypass -Scope Process -Force
    [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
    iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))
    
    # Refresh environment
    $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
}

Write-Host ""
Write-Host "📥 Installing PHP..." -ForegroundColor Yellow
choco install php -y

Write-Host ""
Write-Host "🔄 Refreshing environment variables..." -ForegroundColor Yellow
$env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")

Write-Host ""
Write-Host "🧪 Testing PHP installation..." -ForegroundColor Yellow
try {
    $phpVersion = php --version
    Write-Host "✅ PHP installed successfully!" -ForegroundColor Green
    Write-Host $phpVersion -ForegroundColor Cyan
} catch {
    Write-Host "❌ PHP installation failed. Please install manually:" -ForegroundColor Red
    Write-Host "1. Go to https://windows.php.net/download/" -ForegroundColor Yellow
    Write-Host "2. Download VS16 x64 Thread Safe version" -ForegroundColor Yellow
    Write-Host "3. Extract to C:\php" -ForegroundColor Yellow
    Write-Host "4. Add C:\php to PATH" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "🔧 Checking PHP extensions..." -ForegroundColor Yellow
try {
    $extensions = php -m | Select-String -Pattern "curl|json|openssl"
    Write-Host "✅ Required extensions found:" -ForegroundColor Green
    Write-Host $extensions -ForegroundColor Cyan
} catch {
    Write-Host "⚠️  Could not check extensions" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "🎉 Installation complete!" -ForegroundColor Green
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Restart your terminal" -ForegroundColor White
Write-Host "2. Test with: php --version" -ForegroundColor White
Write-Host "3. Start your PHP backend: cd backend && php start_server.php" -ForegroundColor White
Write-Host ""
Read-Host "Press Enter to continue..."
