# CalDAV Calendar Application

A modern, responsive calendar application with **PHP backend** and **React frontend**, featuring seamless CalDAV synchronization and Google Calendar integration.

## 🏗️ **Architecture**

- **Frontend**: React 18 + Tailwind CSS
- **Backend**: PHP 8.0+ with CalDAV support
- **Database**: File-based storage (can be upgraded to MySQL/PostgreSQL)
- **Authentication**: Basic Authentication (username/password)
- **Calendar Sync**: CalDAV protocol (RFC 4791)

## 🚀 **Quick Start**

### **1. Install PHP Backend**

#### **Option A: Automatic Installation (Recommended)**
```powershell
# Run as Administrator
.\install_php.ps1
```

#### **Option B: Manual Installation**
1. Download PHP from [windows.php.net](https://windows.php.net/download/)
2. Extract to `C:\php`
3. Add `C:\php` to system PATH
4. Restart terminal

### **2. Configure Environment**

Create `backend/.env` file:
```bash
# CalDAV Server Configuration
CALDAV_SERVER_URL=http://rc.mithi.com:8008/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/calendar/
CALDAV_USERNAME=your_username
CALDAV_PASSWORD=your_password
CALDAV_CALENDAR_PATH=/calendars/__uids__/80b5d808-0553-1040-8d6f-0f1266787052/calendar/

# Server Configuration
PORT=8000
CORS_ORIGIN=http://localhost:3000
```

### **3. Start Backend**

```bash
cd backend
php start_server.php
```

### **4. Start Frontend**

```bash
cd frontend
npm install
npm start
```

## 📁 **Project Structure**

```
caldev_calendar/
├── backend/                 # PHP Backend
│   ├── index.php           # Main API entry point
│   ├── start_server.php    # Development server script
│   ├── .env                # Environment variables
│   ├── classes/            # PHP Classes
│   │   ├── CalDAVClient.php    # CalDAV protocol client
│   │   └── OAuth2Client.php    # Google OAuth 2.0
│   └── config/             # Configuration
│       └── database.php    # Environment loader
├── frontend/               # React Frontend
│   ├── src/
│   │   ├── App.js          # Main application
│   │   └── App.css         # Styles
│   └── package.json        # Dependencies
├── install_php.ps1         # PHP installation script
└── README.md               # This file
```

## 🌟 **Features**

### **Calendar Management**
- ✅ Multiple calendar support
- ✅ Color-coded calendars
- ✅ Event creation and editing
- ✅ Drag & drop scheduling

### **CalDAV Integration**
- ✅ Google Calendar sync
- ✅ OAuth 2.0 authentication
- ✅ Real-time synchronization
- ✅ Cross-platform compatibility

### **User Experience**
- ✅ Modern, responsive design
- ✅ Multiple view modes (Month, Week, Day)
- ✅ Search and filtering
- ✅ Mobile-friendly interface

## 🔧 **API Endpoints**

### **Calendars**
- `GET /calendars` - List all calendars
- `POST /calendars` - Create calendar
- `PUT /calendars/{id}` - Update calendar
- `DELETE /calendars/{id}` - Delete calendar

### **Events**
- `GET /events` - List all events
- `POST /events` - Create event
- `PUT /events/{id}` - Update event
- `DELETE /events/{id}` - Delete event

### **CalDAV**
- `GET /caldav/status` - Check connection
- `POST /caldav/discover` - Discover calendars
- `POST /calendars/{id}/sync` - Sync calendar

### **OAuth 2.0**
- `GET /oauth/setup` - Get authorization URL
- `GET /oauth/callback` - Handle callback
- `POST /oauth/refresh` - Refresh token

## 🐛 **Troubleshooting**

### **PHP Not Found**
- Ensure PHP is in your system PATH
- Restart terminal after installation
- Run `php --version` to verify

### **Missing Extensions**
```bash
# Check required extensions
php -m | findstr -i "curl json openssl"
```

### **Port Conflicts**
```bash
# Find process using port 8000
netstat -ano | findstr :8000

# Kill process
taskkill /PID <process_id> /F
```

## 📚 **Resources**

- [PHP Documentation](https://www.php.net/docs.php)
- [CalDAV RFC 4791](https://tools.ietf.org/html/rfc4791)
- [Google Calendar API](https://developers.google.com/calendar)
- [React Documentation](https://reactjs.org/docs/)

## 🤝 **Support**

If you encounter issues:

1. Check the troubleshooting section above
2. Verify PHP installation: `php --version`
3. Check your `.env` configuration
4. Review backend error logs

---

**Happy coding! 🗓️✨**
