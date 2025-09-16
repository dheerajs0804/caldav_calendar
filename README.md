# CalDAV Calendar Application

A modern, responsive calendar application with **PHP backend** and **Angular frontend**, featuring seamless CalDAV synchronization, Single Sign-On (SSO) integration, and comprehensive calendar management features.

## 🏗️ **Architecture**

- **Frontend**: Angular 17 + SCSS + TypeScript
- **Backend**: PHP 8.0+ with CalDAV support
- **Database**: File-based storage (can be upgraded to MySQL/PostgreSQL)
- **Authentication**: Basic Authentication + SSO integration
- **Calendar Sync**: CalDAV protocol (RFC 4791)
- **SSO Integration**: Roundcube webmail integration

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
CALDAV_SERVER_URL=http://rc.mithi.com:8008

# Server Configuration
PORT=8000
CORS_ORIGIN=http://localhost:4200
```

### **3. Start Backend**

```bash
cd backend
php start_server.php
```

### **4. Start Frontend**

```bash
cd frontend-angular
npm install
ng serve
```

The application will be available at `http://localhost:4200`

## 📁 **Project Structure**

```
caldev_calendar/
├── backend/                     # PHP Backend
│   ├── index.php               # Main API entry point
│   ├── start_server.php        # Development server script
│   ├── .env                    # Environment variables
│   ├── classes/                # PHP Classes
│   │   ├── CalDAVClient.php    # CalDAV protocol client
│   │   └── OAuth2Client.php    # Google OAuth 2.0
│   └── config/                 # Configuration
│       └── caldav.php          # CalDAV configuration
├── frontend-angular/           # Angular Frontend
│   ├── src/
│   │   ├── app/
│   │   │   ├── components/     # Angular components
│   │   │   │   ├── calendar/   # Main calendar component
│   │   │   │   ├── day-view/   # Day view component
│   │   │   │   ├── week-view/  # Week view component
│   │   │   │   ├── month-view/ # Month view component
│   │   │   │   ├── event-detail-modal/ # Event editing modal
│   │   │   │   ├── export-modal/ # Export functionality
│   │   │   │   ├── import-modal/ # Import functionality
│   │   │   │   ├── date-navigation/ # Date navigation widget
│   │   │   │   └── reminder-notification/ # Reminder system
│   │   │   ├── services/       # Angular services
│   │   │   │   ├── auth.service.ts # Authentication
│   │   │   │   ├── color-registry.service.ts # Calendar colors
│   │   │   │   └── email.service.ts # Email functionality
│   │   │   ├── interfaces/     # TypeScript interfaces
│   │   │   ├── guards/          # Route guards
│   │   │   └── pipes/           # Custom pipes
│   │   └── styles.scss         # Global styles
│   └── package.json            # Dependencies
├── roundcube/                  # Roundcube SSO Integration
│   └── plugins/
│       └── calendar_link/      # SSO plugin
├── install_php.ps1            # PHP installation script
└── README.md                  # This file
```

## 🌟 **Features**

### **Calendar Management**
- ✅ Multiple calendar support with color coding
- ✅ Event creation, editing, and deletion
- ✅ Attendee management for events
- ✅ All-day and timed events
- ✅ Event reminders with snooze/dismiss functionality
- ✅ Persistent reminder dismissal (survives page refresh)

### **View Modes**
- ✅ **Day View**: Hourly timeline with event details
- ✅ **Week View**: 7-day grid with time slots
- ✅ **Month View**: Full month calendar grid
- ✅ **Agenda View**: List of upcoming events

### **Navigation & Date Management**
- ✅ **Date Navigation Widget**: Compact calendar for quick date selection
- ✅ **Fixed Sidebar**: Calendar management and date navigation
- ✅ **Scrollable Timeline**: Only main content scrolls, sidebar stays fixed
- ✅ **Previous/Next/Today**: Quick navigation buttons

### **Import/Export Functionality**
- ✅ **Export to iCalendar**: Download events as .ics files
- ✅ **Import from Files**: Support for .ics, .csv, .json formats
- ✅ **Calendar-specific Export**: Choose specific calendar or all calendars
- ✅ **Date Range Filtering**: Export/import events from specific time periods
- ✅ **File Size Validation**: 25MB limit with proper error handling

### **Print Functionality**
- ✅ **Single-page Print**: Optimized layout for all views
- ✅ **Ultra-compact Design**: Fits entire timeline on one page
- ✅ **Print-specific Styling**: Hides UI elements, optimizes for printing
- ✅ **Multiple View Support**: Print day, week, month, and agenda views

### **Single Sign-On (SSO) Integration**
- ✅ **Roundcube Integration**: Direct login from webmail client
- ✅ **Credential Passing**: Secure credential transfer
- ✅ **Automatic Authentication**: Skip login page when coming from Roundcube
- ✅ **Password Encryption**: Client-side password obfuscation

### **User Experience**
- ✅ **Modern Angular UI**: Clean, responsive design
- ✅ **Real-time Updates**: Events refresh automatically
- ✅ **Error Handling**: Graceful error messages and recovery
- ✅ **Loading States**: Visual feedback during operations
- ✅ **Mobile Responsive**: Works on all device sizes

## 🔧 **API Endpoints**

### **Authentication**
- `POST /auth/login` - User login
- `POST /auth/sso-token` - Create SSO token
- `POST /auth/sso-login` - SSO authentication
- `POST /auth/logout` - User logout

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

## 🎨 **Calendar Features**

### **Color Management**
- ✅ **Persistent Colors**: Calendar colors saved in localStorage
- ✅ **Color Registry Service**: Centralized color management
- ✅ **Thunderbird-style Colors**: Consistent color scheme
- ✅ **Debug Tools**: Browser console tools for color management

### **Event Management**
- ✅ **Rich Event Details**: Title, description, location, attendees
- ✅ **Time Management**: Start/end times with all-day support
- ✅ **Attendee Roles**: Required/optional attendee designation
- ✅ **Event Editing**: In-place editing with modal interface
- ✅ **Event Deletion**: Safe deletion with confirmation

### **Reminder System**
- ✅ **Smart Reminders**: Check for upcoming events
- ✅ **Snooze Functionality**: Temporarily hide reminders
- ✅ **Dismiss Permanently**: Never show reminder again
- ✅ **Persistent State**: Dismissed reminders survive page refresh
- ✅ **Multiple Reminders**: Handle multiple upcoming events

## 🖨️ **Print Features**

### **Optimized Print Layout**
- ✅ **Single Page**: All views fit on one page
- ✅ **Ultra-compact**: Minimal margins and font sizes
- ✅ **Clean Design**: Hides UI elements, shows only calendar content
- ✅ **Professional Output**: Ready for business use

### **Print Specifications**
- **Margins**: 0.1 inch (minimal)
- **Font Sizes**: 5-9px (ultra-compact)
- **Element Heights**: 15-30px (space-efficient)
- **Page Breaks**: Avoided where possible

## 📥 **Import/Export Features**

### **Supported Formats**
- **iCalendar (.ics)**: Standard calendar format
- **CSV**: Comma-separated values with flexible field mapping
- **JSON**: Multiple JSON structures supported

### **Import Capabilities**
- **File Validation**: Size and format checking
- **Error Handling**: Graceful failure handling
- **Progress Tracking**: Success/failure counts
- **Date Filtering**: Import events from specific time periods

### **Export Capabilities**
- **Calendar Selection**: Choose specific calendar or all
- **Date Range**: Export events from specific time periods
- **iCalendar Standard**: Compatible with all major calendar apps
- **Automatic Download**: Direct file download

## 🔐 **Security Features**

### **Authentication**
- ✅ **Secure Login**: Username/password authentication
- ✅ **SSO Integration**: Seamless Roundcube integration
- ✅ **Session Management**: Proper session handling
- ✅ **Route Protection**: AuthGuard for protected routes

### **Data Security**
- ✅ **Password Encryption**: Client-side password obfuscation
- ✅ **Secure Transmission**: HTTPS support
- ✅ **Input Validation**: Server-side validation
- ✅ **Error Handling**: No sensitive data exposure

## 🐛 **Troubleshooting**

### **Angular Development**
```bash
# Check Angular CLI version
ng version

# Clear Angular cache
ng cache clean

# Reinstall dependencies
rm -rf node_modules package-lock.json
npm install
```

### **PHP Backend Issues**
```bash
# Check PHP version
php --version

# Check required extensions
php -m | findstr -i "curl json openssl"

# Check port availability
netstat -ano | findstr :8000
```

### **Common Issues**
- **Calendar colors not showing**: Check browser console for color registry errors
- **SSO not working**: Verify Roundcube plugin configuration
- **Import/Export failing**: Check file format and size limits
- **Print layout issues**: Ensure print styles are loaded

## 📚 **Resources**

- [Angular Documentation](https://angular.io/docs)
- [PHP Documentation](https://www.php.net/docs.php)
- [CalDAV RFC 4791](https://tools.ietf.org/html/rfc4791)
- [iCalendar RFC 5545](https://tools.ietf.org/html/rfc5545)
- [Roundcube Plugin Development](https://github.com/roundcube/roundcubemail/wiki/Plugin-API)

## 🤝 **Support**

If you encounter issues:

1. Check the troubleshooting section above
2. Verify Angular installation: `ng version`
3. Check PHP installation: `php --version`
4. Review browser console for errors
5. Check backend error logs

---

**Happy coding! 🗓️✨**