# Mithi Calendar Backend Setup

## 🔐 CalDAV Configuration Setup

### For GitHub Users (Public Repository)

1. **Copy the demo configuration:**
   ```bash
   cp config/caldav_demo.php config/caldav.php
   ```

2. **Edit the configuration file:**
   ```bash
   # Update these values in config/caldav.php:
   'server_url' => 'http://your-caldav-server.com:8008',
   'username' => 'your-email@company.com',
   'password' => 'your-actual-password',
   'calendar_path' => '/calendars/your-calendar-path/'
   ```

3. **Never commit caldav.php to Git!**
   - The file is already in `.gitignore`
   - Keep your credentials private

### For Local Development

1. **Use your existing caldav.php** (already configured)
2. **The file contains your real credentials** and is gitignored
3. **No changes needed** for local development

## 📧 Email Configuration

### Company SMTP Setup

The system uses your company's SMTP server instead of external services like SendGrid:

- **Server**: `intmail.mithi.com:587`
- **Security**: TLS
- **Authentication**: Uses CalDAV credentials
- **No external dependencies** or API keys needed

### Email Features

- ✅ **Calendar invitations** with iCalendar attachments
- ✅ **Proper multipart MIME formatting**
- ✅ **HTML and text versions**
- ✅ **Professional email structure**

## 🚀 Quick Start

1. **Ensure CalDAV config is set up** (see above)
2. **Start the PHP server:**
   ```bash
   cd backend
   php -S localhost:8000
   ```
3. **Test with your Angular frontend**

## 🔒 Security Notes

- **caldav.php is gitignored** to protect credentials
- **Use HTTPS in production** for secure connections
- **Regularly rotate CalDAV passwords**
- **Monitor SMTP logs** for any issues

## 📁 File Structure

```
backend/
├── config/
│   ├── caldav_demo.php    # Template for GitHub users
│   ├── caldav.php         # Real config (gitignored)
│   └── email.php          # Email configuration
├── classes/
│   └── CalDAVClient.php   # CalDAV client class
└── index.php              # Main API endpoint
```

## 🆘 Troubleshooting

- **CORS errors**: Ensure PHP server is running on port 8000
- **CalDAV connection**: Check server URL and credentials
- **Email sending**: Verify SMTP settings and authentication
- **Port conflicts**: Stop any Docker containers using port 8000
