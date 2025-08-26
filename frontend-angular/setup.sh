#!/bin/bash

echo "🚀 Setting up CalDev Calendar Angular Frontend..."

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed. Please install Node.js v18 or higher."
    exit 1
fi

# Check Node.js version
NODE_VERSION=$(node -v | cut -d'v' -f2 | cut -d'.' -f1)
if [ "$NODE_VERSION" -lt 18 ]; then
    echo "❌ Node.js version $NODE_VERSION is too old. Please install Node.js v18 or higher."
    exit 1
fi

echo "✅ Node.js version $(node -v) detected"

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo "❌ npm is not installed. Please install npm."
    exit 1
fi

echo "✅ npm version $(npm -v) detected"

# Install dependencies
echo "📦 Installing dependencies..."
npm install

if [ $? -eq 0 ]; then
    echo "✅ Dependencies installed successfully"
else
    echo "❌ Failed to install dependencies"
    exit 1
fi

# Check if Angular CLI is installed globally
if ! command -v ng &> /dev/null; then
    echo "📦 Installing Angular CLI globally..."
    npm install -g @angular/cli
    if [ $? -eq 0 ]; then
        echo "✅ Angular CLI installed successfully"
    else
        echo "❌ Failed to install Angular CLI"
        exit 1
    fi
else
    echo "✅ Angular CLI is already installed"
fi

echo ""
echo "🎉 Setup complete! You can now run:"
echo "   npm start    - Start the development server"
echo "   npm run build - Build for production"
echo ""
echo "The application will be available at http://localhost:4200"
echo ""
echo "Make sure your backend server is running on http://localhost:8000"
