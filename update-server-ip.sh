#!/bin/bash

# Quick Server IP Update Script
# Use this to update the server IP for existing deployments

echo "🔧 CalDAV Calendar App - Server IP Update"
echo "========================================"

# Get current server IP
CURRENT_IP=$(grep "SERVER_IP=" .env 2>/dev/null | cut -d'=' -f2)
if [ -n "$CURRENT_IP" ]; then
    echo "Current server IP: $CURRENT_IP"
fi

# Get new server IP
read -p "Enter new server IP address: " NEW_IP

if [ -z "$NEW_IP" ]; then
    echo "❌ No IP address provided. Exiting."
    exit 1
fi

# Update .env file
if [ -f ".env" ]; then
    sed -i "s/SERVER_IP=.*/SERVER_IP=$NEW_IP/" .env
else
    cat > .env << EOF
SERVER_IP=$NEW_IP
SERVER_DOMAIN=
CALDAV_SERVER_URL=http://rc.mithi.com:8008
APP_DEBUG=false
APP_ENV=production
EOF
fi

echo "✅ Updated .env file with new IP: $NEW_IP"

# Restart containers with new configuration
echo "🔄 Restarting containers with new configuration..."
docker compose down
docker compose up -d

echo "✅ Application restarted with new server IP"
echo ""
echo "Application URLs:"
echo "  Frontend: http://$NEW_IP:4200"
echo "  Backend:  http://$NEW_IP:8000"
