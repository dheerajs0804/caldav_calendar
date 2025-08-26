@echo off
echo 🚀 Setting up CalDev Calendar Angular Frontend...

REM Check if Node.js is installed
node --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Node.js is not installed. Please install Node.js v18 or higher.
    pause
    exit /b 1
)

echo ✅ Node.js version detected
node --version

REM Check if npm is installed
npm --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ npm is not installed. Please install npm.
    pause
    exit /b 1
)

echo ✅ npm version detected
npm --version

REM Install dependencies
echo 📦 Installing dependencies...
npm install

if %errorlevel% neq 0 (
    echo ❌ Failed to install dependencies
    pause
    exit /b 1
)

echo ✅ Dependencies installed successfully

REM Check if Angular CLI is installed globally
ng --version >nul 2>&1
if %errorlevel% neq 0 (
    echo 📦 Installing Angular CLI globally...
    npm install -g @angular/cli
    if %errorlevel% neq 0 (
        echo ❌ Failed to install Angular CLI
        pause
        exit /b 1
    )
    echo ✅ Angular CLI installed successfully
) else (
    echo ✅ Angular CLI is already installed
)

echo.
echo 🎉 Setup complete! You can now run:
echo    npm start    - Start the development server
echo    npm run build - Build for production
echo.
echo The application will be available at http://localhost:4200
echo.
echo Make sure your backend server is running on http://localhost:8000
pause
