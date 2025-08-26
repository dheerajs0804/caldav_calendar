@echo off
echo Setting up Roundcube with Docker...

REM Create necessary directories
echo Creating directory structure...
if not exist "roundcube" mkdir roundcube
if not exist "roundcube\plugins" mkdir roundcube\plugins
if not exist "roundcube\config" mkdir roundcube\config
if not exist "roundcube\program" mkdir roundcube\program
if not exist "roundcube\html" mkdir roundcube\html
if not exist "roundcube\logs" mkdir roundcube\logs
if not exist "roundcube\temp" mkdir roundcube\temp

REM Create .env file
echo Creating .env file...
(
echo # Roundcube Environment Variables
echo ROUNDCUBEMAIL_DEFAULT_HOST=ssl://intmail.mithi.com
echo ROUNDCUBEMAIL_SMTP_SERVER=tls://intmail.mithi.com
echo ROUNDCUBEMAIL_DEFAULT_PORT=993
echo ROUNDCUBEMAIL_SMTP_PORT=587
echo ROUNDCUBEMAIL_DB_TYPE=sqlite
echo ROUNDCUBEMAIL_DB_NAME=roundcube
echo ROUNDCUBEMAIL_DB_USERNAME=roundcube
echo ROUNDCUBEMAIL_DB_PASSWORD=roundcube_password
echo.
echo # Additional settings
echo ROUNDCUBEMAIL_PRODUCT_NAME=Mithi Roundcube
echo ROUNDCUBEMAIL_DES_KEY=your-secret-key-here-change-this
echo ROUNDCUBEMAIL_DEBUG_LEVEL=1
) > .env

echo Starting Roundcube container...
docker-compose up -d

echo.
echo Roundcube setup complete!
echo Access Roundcube at: http://localhost:8000
echo.
echo Default configuration:
echo - IMAP: ssl://intmail.mithi.com:993
echo - SMTP: tls://intmail.mithi.com:587
echo - Database: SQLite
echo.
echo To stop: docker-compose down
echo To view logs: docker-compose logs -f roundcube
pause
