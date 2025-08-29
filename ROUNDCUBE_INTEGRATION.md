# Roundcube Integration with Angular Calendar App

## Overview

The Angular calendar application has been successfully integrated with Roundcube webmail through a custom plugin called `calendar_link`. This integration allows users to access the calendar app directly from within Roundcube's interface.

## What Was Implemented

### 1. Calendar Link Plugin
- **Location**: `roundcube/plugins/calendar_link/`
- **Purpose**: Adds a calendar button to Roundcube's navigation
- **Features**: 
  - Configurable calendar app URL
  - Customizable button text
  - Opens calendar in new tab
  - Responsive design

### 2. Configuration Files
- **Main Plugin Config**: `roundcube/plugins/calendar_link/config.inc.php`
  - `calendar_app_url`: Points to Angular app (http://localhost:4200)
  - `calendar_link_text`: Button text ("📅 Calendar App")
  - `calendar_open_new_tab`: Always true

### 3. Integration Points
- **Taskbar Button**: Added to Roundcube's main taskbar
- **Main Menu**: Integrated into the main navigation menu
- **JavaScript Handling**: Ensures button visibility and click handling
- **CSS Styling**: Consistent with Roundcube's design

## Current Setup

### URLs
- **Angular Calendar App**: http://localhost:4200
- **PHP Backend API**: http://localhost:8000
- **Roundcube**: http://localhost:8000 (if configured)

### Ports
- **Angular**: 4200 (default Angular CLI port)
- **Backend**: 8000 (PHP development server)
- **Roundcube**: 8000 (same as backend for now)

## How to Test the Integration

### Prerequisites
1. **Angular App Running**: Ensure the Angular app is started
   ```bash
   cd frontend-angular
   npm start
   # App will be available at http://localhost:4200
   ```

2. **Backend Running**: Ensure the PHP backend is running
   ```bash
   cd backend
   php -S localhost:8000 -t . index.php
   # API will be available at http://localhost:8000
   ```

3. **Roundcube Running**: Ensure Roundcube is accessible
   - If using Docker: `docker run -p 8000:80 roundcube/roundcubemail`
   - If using local installation: Configure to run on port 8000

### Testing Steps
1. **Open Roundcube** in your browser
2. **Look for Calendar Button**: You should see "📅 Calendar App" in the navigation
3. **Click the Button**: It should open the Angular calendar app in a new tab
4. **Verify Functionality**: Test calendar features like creating/editing events

## Configuration Options

### Changing Calendar App URL
Edit `roundcube/plugins/calendar_link/config.inc.php`:
```php
$config['calendar_app_url'] = 'http://your-domain:port';
```

### Changing Button Text
Edit the same file:
```php
$config['calendar_link_text'] = 'Your Custom Text';
```

### Enabling/Disabling Plugin
In `roundcube/config/config.inc.php`:
```php
$config['plugins'] = array('calendar_link'); // Enable
// $config['plugins'] = array(); // Disable
```

## Troubleshooting

### Common Issues

1. **Calendar Button Not Visible**
   - Check if plugin is enabled in Roundcube config
   - Verify plugin files are in correct directory
   - Check browser console for JavaScript errors

2. **Wrong URL Opens**
   - Verify `calendar_app_url` in plugin config
   - Check if Angular app is running on expected port
   - Clear browser cache

3. **Styling Issues**
   - Check CSS file in `skins/elastic/calendar_link.css`
   - Verify Roundcube skin compatibility

4. **JavaScript Errors**
   - Check browser console for errors
   - Verify `calendar_link.js` is loaded
   - Check meta tag injection in page source

### Debug Steps
1. **Check Plugin Status**: Verify plugin is listed in Roundcube settings
2. **Inspect HTML**: Look for calendar button in page source
3. **Check Network**: Verify Angular app is accessible
4. **Review Logs**: Check Roundcube error logs

## File Structure

```
roundcube/
├── config/
│   └── config.inc.php          # Main Roundcube config
└── plugins/
    └── calendar_link/          # Calendar integration plugin
        ├── calendar_link.php    # Main plugin logic
        ├── calendar_link.js     # JavaScript handling
        ├── config.inc.php       # Plugin configuration
        ├── README.md            # Plugin documentation
        └── skins/
            └── elastic/
                └── calendar_link.css  # Styling
```

## Future Enhancements

### Potential Improvements
1. **Deep Integration**: Embed calendar directly in Roundcube iframe
2. **Event Sync**: Two-way synchronization between Roundcube and calendar
3. **Notifications**: Email notifications for calendar events
4. **User Preferences**: Allow users to customize calendar settings
5. **Multi-Calendar Support**: Support for multiple calendar sources

### Technical Considerations
1. **CORS Configuration**: Ensure proper cross-origin requests
2. **Authentication**: Share user sessions between apps
3. **Performance**: Optimize loading and rendering
4. **Mobile Support**: Ensure responsive design on mobile devices

## Security Notes

- **URL Validation**: Plugin validates calendar app URLs
- **XSS Protection**: Uses `htmlspecialchars()` for output
- **Access Control**: Respects Roundcube's authentication
- **HTTPS Support**: Configure for production HTTPS environments

## Support

For issues or questions about the integration:
1. Check this documentation first
2. Review Roundcube plugin logs
3. Verify Angular app functionality independently
4. Check browser console for JavaScript errors
5. Ensure all services are running on correct ports

---

**Last Updated**: December 2024  
**Version**: 1.0  
**Status**: ✅ Integration Complete
