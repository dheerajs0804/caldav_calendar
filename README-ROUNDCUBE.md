# Roundcube Docker Setup

This setup provides a complete Roundcube webmail solution using Docker.

## Prerequisites

- Docker and Docker Compose installed
- Access to your mail server (intmail.mithi.com)

## Quick Start

### Option 1: Windows
```bash
setup-roundcube.bat
```

### Option 2: Linux/Mac
```bash
chmod +x setup-roundcube.sh
./setup-roundcube.sh
```

### Option 3: Manual Setup
```bash
# Create directories
mkdir -p roundcube/{plugins,config,program,html,logs,temp}

# Start container
docker-compose up -d
```

## Configuration

### Mail Server Settings
- **IMAP Server**: ssl://intmail.mithi.com:993
- **SMTP Server**: tls://intmail.mithi.com:587
- **Database**: SQLite (stored in container)

### Environment Variables
The `.env` file contains:
- Mail server configuration
- Database settings
- Security keys
- Debug settings

## Access

- **URL**: http://localhost:8000
- **Port**: 8000 (mapped from container port 80)

## Directory Structure

```
roundcube/
├── plugins/          # Custom plugins
├── config/           # Configuration files
├── program/          # Program files
├── html/             # Web interface
├── logs/             # Log files
└── temp/             # Temporary files
```

## Docker Commands

```bash
# Start Roundcube
docker-compose up -d

# Stop Roundcube
docker-compose down

# View logs
docker-compose logs -f roundcube

# Restart container
docker-compose restart roundcube

# Update image
docker-compose pull
docker-compose up -d
```

## Customization

### Plugins
Place custom plugins in `roundcube/plugins/`

### Configuration
Edit `roundcube/config/config.inc.php` for custom settings

### Themes
Custom themes can be added to `roundcube/html/skins/`

## Troubleshooting

### Check Container Status
```bash
docker-compose ps
```

### View Logs
```bash
docker-compose logs roundcube
```

### Access Container Shell
```bash
docker-compose exec roundcube bash
```

### Reset Database
```bash
docker-compose down
rm -rf roundcube/logs/* roundcube/temp/*
docker-compose up -d
```

## Security Notes

1. Change the `ROUNDCUBEMAIL_DES_KEY` in `.env`
2. Use HTTPS in production
3. Restrict access to admin interfaces
4. Regular security updates

## Support

For issues:
1. Check container logs
2. Verify mail server connectivity
3. Check file permissions
4. Review configuration files
