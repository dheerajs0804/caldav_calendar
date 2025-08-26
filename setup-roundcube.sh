#!/bin/bash

echo "Setting up Roundcube with Docker..."

# Create necessary directories
echo "Creating directory structure..."
mkdir -p roundcube/plugins roundcube/config roundcube/program roundcube/html roundcube/logs roundcube/temp

# Set proper permissions
echo "Setting permissions..."
chmod -R 755 roundcube/
chmod -R 777 roundcube/logs roundcube/temp

# Create .env file
echo "Creating .env file..."
cat > .env << EOF
# Roundcube Environment Variables
ROUNDCUBEMAIL_DEFAULT_HOST=ssl://intmail.mithi.com
ROUNDCUBEMAIL_SMTP_SERVER=tls://intmail.mithi.com
ROUNDCUBEMAIL_DEFAULT_PORT=993
ROUNDCUBEMAIL_SMTP_PORT=587
ROUNDCUBEMAIL_DB_TYPE=sqlite
ROUNDCUBEMAIL_DB_NAME=roundcube
ROUNDCUBEMAIL_DB_USERNAME=roundcube
ROUNDCUBEMAIL_DB_PASSWORD=roundcube_password

# Additional settings
ROUNDCUBEMAIL_PRODUCT_NAME=Mithi Roundcube
ROUNDCUBEMAIL_DES_KEY=your-secret-key-here-change-this
ROUNDCUBEMAIL_DEBUG_LEVEL=1
EOF

echo "Starting Roundcube container..."
docker-compose up -d

echo "Roundcube setup complete!"
echo "Access Roundcube at: http://localhost:8000"
echo ""
echo "Default configuration:"
echo "- IMAP: ssl://intmail.mithi.com:993"
echo "- SMTP: tls://intmail.mithi.com:587"
echo "- Database: SQLite"
echo ""
echo "To stop: docker-compose down"
echo "To view logs: docker-compose logs -f roundcube"
