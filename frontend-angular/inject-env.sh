#!/bin/sh
set -e

# Environment injection script for Angular app
echo "🔧 Starting environment injection..."

# Get the API URL from environment variable
API_URL=${API_URL:-"http://localhost:8000"}

# Create assets directory if it doesn't exist
mkdir -p /usr/share/nginx/html/assets

# Create a JavaScript file with the environment variables
cat > /usr/share/nginx/html/assets/env.js << EOF
// Runtime environment configuration
window.API_URL = '${API_URL}';
console.log('🔗 Injected API URL:', window.API_URL);
EOF

echo "✅ Environment variables injected:"
echo "   API_URL: ${API_URL}"

echo "🔧 Environment injection completed successfully"
