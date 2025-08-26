# PHP CalDAV Calendar Backend

A modern PHP backend for CalDAV calendar integration with Google Calendar support.

## 🚀 Features

- **CalDAV Protocol Support** - RFC 4791 compliant
- **Google Calendar Integration** - OAuth 2.0 authentication
- **RESTful API** - JSON endpoints for frontend integration
- **OAuth 2.0 Flow** - Complete Google authentication
- **cURL-based HTTP Client** - No external dependencies

## 📋 Requirements

- **PHP 7.4+** (8.0+ recommended)
- **cURL extension** enabled
- **JSON extension** enabled
- **OpenSSL extension** enabled

## 🛠️ Installation

### 1. Install PHP on Windows

#### Option A: Download from Official Site
1. Go to [PHP Downloads](https://windows.php.net/download/)
2. Download the latest **Thread Safe** version (VS16 x64)
3. Extract to `C:\php`
4. Add `C:\php` to your system PATH

#### Option B: Use XAMPP/WAMP
1. Download [XAMPP](https://www.apachefriends.org/) or [WAMP](https://www.wampserver.com/)
2. Install and start Apache
3. PHP will be available at the installed location

### 2. Verify PHP Installation

```bash
php --version
```

### 3. Check Required Extensions

```bash
php -m | findstr -i "curl json openssl"
```

You should see:
- `curl`
- `json` 
- `openssl`

## ⚙️ Configuration

### 1. Environment Variables

Create a `.env` file in the backend directory:

```bash
# Google CalDAV Configuration
CALDAV_SERVER_URL=https://apidata.googleusercontent.com/caldav/v2/
CALDAV_USERNAME=your-email@gmail.com
CALDAV_PASSWORD=your-app-password
CALDAV_CALENDAR_PATH=your-email@gmail.com/events

# Google OAuth 2.0 Credentials
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret

# Server Configuration
PORT=8000
CORS_ORIGIN=http://localhost:3000
```

### 2. Get Google OAuth 2.0 Credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing
3. Enable **Google Calendar API**
4. Go to **Credentials** → **Create Credentials** → **OAuth 2.0 Client IDs**
5. Set **Application type** to "Web application"
6. Add **Authorized redirect URIs**: `http://localhost:8000/oauth/callback`
7. Copy **Client ID** and **Client Secret**

## 🚀 Running the Backend

### Option 1: PHP Built-in Server (Development)

```bash
cd backend
php start_server.php
```

### Option 2: Manual PHP Server

```bash
cd backend
php -S localhost:8000 -t . index.php
```

### Option 3: Apache/Nginx (Production)

1. Copy files to your web server directory
2. Configure virtual host to point to backend directory
3. Ensure `.htaccess` is enabled for URL rewriting

## 📡 API Endpoints

### Health Check
```
GET /health
```

### Calendars
```
GET    /calendars          # List all calendars
GET    /calendars/{id}     # Get specific calendar
POST   /calendars          # Create calendar
PUT    /calendars/{id}     # Update calendar
DELETE /calendars/{id}     # Delete calendar
```

### Events
```
GET    /events             # List all events
GET    /calendars/{id}/events  # Get calendar events
POST   /events             # Create event
PUT    /events/{id}        # Update event
DELETE /events/{id}        # Delete event
```

### CalDAV Integration
```
GET    /caldav/status      # Check CalDAV connection
POST   /caldav/discover    # Discover CalDAV calendars
POST   /calendars/{id}/sync # Sync calendar with CalDAV
```

### OAuth 2.0
```
GET    /oauth/setup        # Get OAuth authorization URL
GET    /oauth/callback     # Handle OAuth callback
POST   /oauth/refresh      # Refresh access token
```

## 🔧 Testing

### 1. Test PHP Installation

```bash
php -r "echo 'PHP is working!';"
```

### 2. Test Backend

```bash
curl http://localhost:8000/health
```

Expected response:
```json
{"status":"OK","message":"PHP CalDAV Calendar Backend is running"}
```

### 3. Test CalDAV Status

```bash
curl http://localhost:8000/caldav/status
```

## 🐛 Troubleshooting

### Common Issues

#### PHP Not Found
- Add PHP to your system PATH
- Restart your terminal/command prompt

#### cURL Extension Missing
```bash
# Check if cURL is enabled
php -m | findstr curl

# If missing, enable in php.ini
extension=curl
```

#### Permission Denied
- Run as Administrator (Windows)
- Check file permissions

#### Port Already in Use
```bash
# Find process using port 8000
netstat -ano | findstr :8000

# Kill the process
taskkill /PID <process_id> /F
```

## 📁 File Structure

```
backend/
├── index.php              # Main entry point
├── start_server.php       # Development server script
├── .env                   # Environment variables
├── README.md             # This file
├── classes/
│   ├── CalDAVClient.php  # CalDAV protocol client
│   └── OAuth2Client.php  # OAuth 2.0 client
└── config/
    └── database.php      # Configuration loader
```

## 🔄 Next Steps

1. **Install PHP** following the instructions above
2. **Configure your `.env` file** with Google credentials
3. **Start the backend** using `php start_server.php`
4. **Test the endpoints** using curl or your browser
5. **Connect your frontend** to `http://localhost:8000`

## 📚 Resources

- [PHP Official Documentation](https://www.php.net/docs.php)
- [CalDAV RFC 4791](https://tools.ietf.org/html/rfc4791)
- [Google Calendar API](https://developers.google.com/calendar)
- [OAuth 2.0 Specification](https://tools.ietf.org/html/rfc6749)

## 🤝 Support

If you encounter issues:

1. Check the troubleshooting section above
2. Verify PHP installation and extensions
3. Check your `.env` configuration
4. Review error logs in your terminal

---

**Happy coding! 🗓️✨**
