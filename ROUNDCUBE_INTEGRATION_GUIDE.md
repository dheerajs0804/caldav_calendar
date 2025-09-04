# 🔗 Roundcube Calendar Integration Guide

## Overview
This guide explains how to test the real Roundcube integration with the CalDAV calendar application.

## 🚀 Current Status
- ✅ **Roundcube**: Running on `http://localhost:8000`
- ✅ **Calendar App**: Running on `http://localhost:4200`
- ✅ **Backend API**: Running on `http://localhost:8001`
- ✅ **Auto-Login**: Implemented and tested

## 📋 Prerequisites
1. **CalDAV Credentials**: You need valid CalDAV server credentials
2. **Browser**: Modern browser with JavaScript enabled
3. **Services Running**: All services must be running (see status above)

## 🧪 Testing the Real Integration

### Step 1: Access Roundcube
1. Open your browser and go to: `http://localhost:8000`
2. You should see the Roundcube login page

### Step 2: Login to Roundcube
1. Enter your **CalDAV username** (e.g., `your_username@mithi.com`)
2. Enter your **CalDAV password**
3. Click "Login"

### Step 3: Locate the Calendar Link
After logging in, you should see:
- **Mail interface** with your emails
- **Calendar button** in the navigation (📅 Calendar App)
- The button should be visible in the taskbar or main menu

### Step 4: Test Auto-Login
1. **Click the calendar button** (📅 Calendar App)
2. **Enter your password** when prompted (for security)
3. **A new window will open** with the calendar app
4. **The app should automatically log you in** using your credentials

## 🔧 How the Integration Works

### Roundcube Side (Plugin)
```php
// Gets current user's username from Roundcube session
$username = $rcmail->user->data['username'] ?? '';

// Creates calendar button with auto-login functionality
$args['content'] .= '<li class="calendar-link">
    <a href="' . $calendar_url . '" 
       target="_blank" 
       class="calendar-button" 
       data-username="' . htmlspecialchars($username) . '" 
       onclick="openCalendarWithCredentials(\'' . htmlspecialchars($username) . '\'); return false;">
       📅 Calendar App
    </a>
</li>';
```

### JavaScript Side (Roundcube Plugin)
```javascript
function openCalendarWithCredentials(username) {
    var password = prompt('Please enter your password to auto-login to the calendar app:');
    
    if (password) {
        // Create URL with credentials
        var calendarUrlWithAuth = calendarUrl + '?username=' + 
            encodeURIComponent(username) + '&password=' + 
            encodeURIComponent(password);
        window.open(calendarUrlWithAuth, '_blank');
    } else {
        // Fallback to regular calendar link
        window.open(calendarUrl, '_blank');
    }
}
```

### Calendar App Side (Angular)
```typescript
ngOnInit(): void {
    // Check for auto-login parameters from Roundcube
    this.route.queryParams.subscribe(params => {
        const username = params['username'];
        const password = params['password'];
        
        if (username && password) {
            console.log('Auto-login attempt from Roundcube for user:', username);
            this.authService.autoLogin(username, password).subscribe({
                next: (response) => {
                    if (response.success) {
                        console.log('Auto-login successful');
                        // Clear URL parameters after successful login
                        window.history.replaceState({}, document.title, window.location.pathname);
                    }
                }
            });
        }
    });
}
```

### Backend Side (PHP)
```php
function autoLoginFromRoundcube() {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'];
    $password = $input['password'];
    
    // Test CalDAV authentication
    $testClient = new CalDAVClient($caldavConfig['server_url'], $username, $password);
    $calendars = $testClient->discoverCalendars();
    
    if (!empty($calendars['calendars'])) {
        // Store authenticated session
        $_SESSION['user_authenticated'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['password'] = $password;
        
        echo json_encode([
            'success' => true,
            'message' => 'Auto-login successful from Roundcube'
        ]);
    }
}
```

## 🎯 Expected Results

### Successful Integration
- ✅ **Roundcube loads** with mail interface
- ✅ **Calendar button appears** in navigation
- ✅ **Clicking calendar button** prompts for password
- ✅ **Calendar app opens** in new window
- ✅ **Auto-login successful** - no manual login required
- ✅ **Calendar shows real data** from CalDAV server
- ✅ **All features work**: create/edit events, email invitations, etc.

### Troubleshooting

#### Calendar Button Not Visible
- Check Roundcube logs: `docker logs roundcube`
- Verify plugin is loaded in Roundcube configuration
- Refresh Roundcube page

#### Auto-Login Fails
- Check browser console for errors
- Verify CalDAV credentials are correct
- Check backend logs for authentication errors

#### Calendar App Shows Login Screen
- Check if URL parameters are present
- Verify backend is running on port 8001
- Check browser console for auto-login errors

## 🔒 Security Notes
- Passwords are passed via URL parameters (consider more secure methods for production)
- Credentials are cleared from URL after successful login
- Session data is stored server-side
- All communication uses HTTPS in production

## 📝 Configuration Files
- **Roundcube Config**: `roundcube-config.php`
- **Calendar Plugin**: `roundcube/plugins/calendar_link/`
- **Backend API**: `backend/index.php`
- **Frontend App**: `frontend-angular/src/app/`

## 🚀 Next Steps
1. Test with your actual CalDAV credentials
2. Verify all calendar features work after auto-login
3. Test email invitation functionality
4. Consider production security improvements
