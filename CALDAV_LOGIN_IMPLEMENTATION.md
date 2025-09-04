# CalDAV Login System Implementation

## Overview
This document describes the implementation of a CalDAV login system that allows users to enter their own CalDAV credentials instead of relying solely on environment variables.

## Changes Made

### 1. Frontend Changes

#### Authentication Service (`frontend-angular/src/app/services/auth.service.ts`)
- **New file created** with complete authentication functionality
- Handles CalDAV credential storage and validation
- Provides login, logout, and credential validation methods
- Stores credentials securely in localStorage (with encryption recommendations for production)

#### Login Component (`frontend-angular/src/app/components/login.component.ts`)
- **New file created** with a beautiful, modern login interface
- Collects CalDAV server URL, username, and password
- Provides helpful examples and error messages
- Includes "Remember Me" functionality

#### Calendar Component (`frontend-angular/src/app/components/calendar.component.ts`)
- **New file created** containing all calendar functionality
- Moved from the main app component to separate component
- Includes logout functionality
- Maintains all existing calendar features

#### Auth Guard (`frontend-angular/src/app/guards/auth.guard.ts`)
- **New file created** to protect calendar routes
- Redirects unauthenticated users to login page
- Validates stored credentials on route access

#### App Routes (`frontend-angular/src/app/app.routes.ts`)
- Updated to include login and protected calendar routes
- Default route redirects to login page
- Calendar route protected by AuthGuard

#### Main App Component (`frontend-angular/src/app/app.component.ts`)
- Simplified to serve as a router outlet only
- Removed all calendar functionality (moved to CalendarComponent)

### 2. Backend Changes

#### CalDAVClient Constructor (`backend/classes/CalDAVClient.php`)
- **Modified** to accept optional credentials as parameters
- **Added comments** explaining environment variable usage as fallback
- **Added getter methods** for username, password, and server URL
- Now supports both user-provided credentials and environment variables

#### Authentication Endpoints (`backend/index.php`)
- **Added** `/auth/login` endpoint for user authentication
- **Added** `/auth/validate` endpoint for credential validation
- **Added** `/auth/logout` endpoint for user logout
- **Modified** `getCalDAVClient()` function to use session credentials instead of environment variables

#### Session Management
- **Added** session-based credential storage
- **Added** proper session cleanup on logout
- **Added** authentication state validation

## How It Works

### 1. User Flow
1. User visits the application
2. Redirected to login page if not authenticated
3. User enters CalDAV server URL, username, and password
4. Backend validates credentials against CalDAV server
5. If valid, credentials stored in session and user redirected to calendar
6. User can access calendar with their own CalDAV data

### 2. Credential Storage
- **Frontend**: Credentials stored in localStorage (with encryption recommendations)
- **Backend**: Credentials stored in PHP session
- **Security**: In production, consider encrypting stored credentials

### 3. Environment Variables (Fallback)
- Environment variables are still used as fallback when no user credentials are provided
- This allows the system to work with pre-configured credentials for development/testing
- Comments added to clarify where environment variables are used

## Security Considerations

### Current Implementation
- Credentials stored in localStorage (client-side)
- Credentials stored in PHP session (server-side)
- Basic authentication validation

### Production Recommendations
1. **Encrypt stored credentials** in localStorage
2. **Use secure session management** with proper session timeouts
3. **Implement CSRF protection** for authentication endpoints
4. **Add rate limiting** for login attempts
5. **Use HTTPS** for all communications
6. **Implement proper password hashing** if storing passwords

## Testing

### Test the Login Flow
1. Start the backend server: `cd backend && php -S localhost:8000`
2. Start the frontend: `cd frontend-angular && npm start`
3. Visit `http://localhost:4200`
4. You should be redirected to the login page
5. Enter your CalDAV credentials
6. After successful login, you should see your calendar

### Test Credential Validation
- The system validates credentials by attempting to discover calendars
- If calendar discovery fails, login is rejected
- This ensures only valid CalDAV credentials are accepted

## Benefits

1. **User Control**: Users can use their own CalDAV servers and credentials
2. **Flexibility**: System works with any CalDAV-compliant server
3. **Security**: No hardcoded credentials in the application
4. **Scalability**: Multiple users can use different CalDAV servers
5. **Maintainability**: Clear separation between authentication and calendar functionality

## Future Enhancements

1. **OAuth2 Support**: Add OAuth2 authentication for CalDAV servers that support it
2. **Credential Encryption**: Implement proper encryption for stored credentials
3. **Multi-User Support**: Add user accounts and credential management
4. **Server Discovery**: Automatically discover CalDAV server URLs
5. **Credential Management**: Allow users to manage multiple CalDAV accounts
