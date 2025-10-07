# 📋 Professional Logging Framework Guide

## 🎯 Overview

This application now uses a comprehensive, professional logging framework with proper levels, security, and structured data. The logging system is designed to help with debugging while maintaining security and performance.

## 🏗️ Architecture

### **Backend Logging (PHP)**
- **Framework**: Monolog (PSR-3 compliant)
- **Location**: `backend/src/Services/LoggerService.php`
- **Helper**: `backend/src/Helpers/Log.php`
- **Configuration**: `backend/config/logging.php`

### **Frontend Logging**
- **React**: `frontend/src/services/LoggingService.js`
- **Angular**: `frontend-angular/src/app/services/logging.service.ts`
- **Library**: Loglevel with custom formatting

## 📊 Log Levels

### **DEBUG Level** 🔍
**Purpose**: Detailed application processing, variable values, step-by-step execution
**When to use**: 
- Processing user requests in detail
- Variable values and state changes
- Algorithm execution steps
- CalDAV protocol details

**Example**:
```php
Log::debug('Processing calendar discovery', [
    'server_url' => $serverUrl,
    'username' => $username, // Will be sanitized
    'step' => 'principal_lookup'
]);
```

### **INFO Level** ℹ️
**Purpose**: User actions, successful operations, general application flow
**When to use**:
- User login/logout
- Calendar creation/deletion
- Event creation/updates
- Successful API operations

**Example**:
```php
Log::info('User created calendar', [
    'user_id' => $userId,
    'calendar_name' => $calendarName,
    'calendar_id' => $calendarId
]);
```

### **WARN Level** ⚠️
**Purpose**: Warning conditions, recoverable errors, deprecated usage
**When to use**:
- Authentication failures
- CalDAV server connectivity issues
- Invalid input data
- Performance warnings

**Example**:
```php
Log::warning('CalDAV server response slow', [
    'server_url' => $serverUrl,
    'response_time' => 5000, // ms
    'threshold' => 3000
]);
```

### **ERROR Level** ❌
**Purpose**: Error conditions, exceptions, failed operations
**When to use**:
- Database connection failures
- CalDAV protocol errors
- Application exceptions
- Critical system failures

**Example**:
```php
Log::error('CalDAV authentication failed', [
    'server_url' => $serverUrl,
    'username' => $username, // Will be sanitized
    'error_code' => $errorCode
]);
```

## 🔒 Security Features

### **Automatic Data Sanitization**
The logging system automatically redacts sensitive data from log entries:

```php
// These fields are automatically redacted:
$sensitiveFields = [
    'password', 'passwd', 'pwd', 'secret', 'token', 'key', 'auth',
    'authorization', 'cookie', 'session', 'csrf', 'api_key', 'private_key'
];
```

**Example**:
```php
Log::info('User login attempt', [
    'username' => 'john_doe',
    'password' => 'secret123', // Will be logged as '[REDACTED]'
    'ip_address' => '192.168.1.1'
]);
```

### **Environment-Based Logging**
- **Development**: All log levels (DEBUG, INFO, WARN, ERROR)
- **Production**: INFO, WARN, ERROR only (no DEBUG)

## 🚀 Usage Examples

### **Backend PHP Usage**

```php
use CalDev\Calendar\Helpers\Log;

// User actions
Log::userAction('calendar_created', [
    'calendar_name' => 'Work Calendar',
    'user_id' => 123
], true); // success = true

// Processing details
Log::processing('caldav_discovery', [
    'server_url' => $serverUrl,
    'step' => 'principal_lookup'
]);

// CalDAV operations
Log::caldav('calendar_sync', [
    'calendar_count' => 5,
    'sync_duration' => 1200
], true);

// Security events
Log::security('failed_login_attempt', [
    'username' => 'john_doe',
    'ip_address' => '192.168.1.1',
    'attempt_count' => 3
]);
```

### **Frontend React Usage**

```javascript
import LoggingService from './services/LoggingService';

// User actions
LoggingService.logUserAction('event_created', {
  event_title: 'Meeting with Client',
  calendar_id: 'work_calendar',
  user_id: 123
}, true);

// Processing details
LoggingService.logProcessing('calendar_rendering', {
  view_type: 'month',
  event_count: 25,
  render_time: 150
});

// API operations
LoggingService.logApiOperation('fetch_events', {
  calendar_id: 'work_calendar',
  date_range: '2024-01-01 to 2024-01-31'
}, true);
```

### **Frontend Angular Usage**

```typescript
import { LoggingService } from './services/logging.service';

// User actions
this.loggingService.logUserAction('calendar_selected', {
  calendar_name: 'Personal Calendar',
  calendar_id: 'personal_calendar'
}, true);

// Processing details
this.loggingService.logProcessing('event_expansion', {
  recurring_event_id: 'event_123',
  occurrence_count: 12
});
```

## 📁 Log Files Structure

```
backend/logs/
├── app.log          # General application logs
├── error.log        # Error-level logs only
├── caldav.log       # CalDAV-specific operations
└── security.log     # Security events (kept longer)
```

## 🔧 Configuration

### **Backend Configuration** (`backend/config/logging.php`)

```php
return [
    'environment' => env('APP_ENV', 'production'),
    'channels' => [
        'file' => [
            'driver' => 'file',
            'path' => __DIR__ . '/../logs/app.log',
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 30, // Keep logs for 30 days
        ],
        'security' => [
            'driver' => 'file',
            'path' => __DIR__ . '/../logs/security.log',
            'level' => 'warning',
            'days' => 90, // Keep security logs longer
        ],
    ],
];
```

### **Environment Variables**

```bash
# Backend
APP_ENV=development|production
LOG_LEVEL=debug|info|warning|error
CALDAV_LOG_LEVEL=debug|info|warning|error

# Frontend
NODE_ENV=development|production
```

## 📈 Best Practices

### **✅ Do**
- Use appropriate log levels
- Include relevant context data
- Log user actions with success/failure status
- Use structured data (arrays/objects)
- Log security events for monitoring

### **❌ Don't**
- Log sensitive data (passwords, tokens, etc.)
- Use DEBUG level in production
- Log excessive data that impacts performance
- Log personal information (PII)
- Use console.log in production code

## 🔍 Debugging Workflow

### **1. Identify the Issue**
```php
Log::info('User reported calendar sync issue', [
    'user_id' => $userId,
    'calendar_name' => $calendarName,
    'last_sync' => $lastSyncTime
]);
```

### **2. Trace the Processing**
```php
Log::debug('Starting calendar sync process', [
    'step' => 'authentication',
    'server_url' => $serverUrl
]);

Log::debug('CalDAV authentication successful', [
    'step' => 'calendar_discovery'
]);

Log::debug('Found calendars', [
    'step' => 'event_fetching',
    'calendar_count' => count($calendars)
]);
```

### **3. Log Results**
```php
Log::info('Calendar sync completed', [
    'user_id' => $userId,
    'calendars_synced' => $syncedCount,
    'events_fetched' => $eventCount,
    'duration' => $syncDuration
]);
```

## 🚨 Monitoring & Alerts

### **Error Monitoring**
- Monitor `error.log` for ERROR level entries
- Set up alerts for CRITICAL level logs
- Track security events in `security.log`

### **Performance Monitoring**
- Log API response times
- Monitor CalDAV operation durations
- Track user action success rates

## 🔄 Migration from Old Logging

### **Replace error_log() calls:**
```php
// Old way
error_log("User logged in: " . $username);

// New way
Log::info('User logged in', ['username' => $username]);
```

### **Replace console.log() calls:**
```javascript
// Old way
console.log('Calendar loaded:', calendarData);

// New way
LoggingService.info('Calendar loaded', { 
  calendar_count: calendarData.length,
  calendar_names: calendarData.map(c => c.name)
});
```

## 📊 Log Analysis

### **Common Log Patterns**
- **User Actions**: Look for `User action completed/failed`
- **CalDAV Operations**: Look for `CalDAV operation successful/failed`
- **Security Events**: Look for `Security event`
- **Performance**: Look for `Performance:` entries

### **Troubleshooting**
1. Check `error.log` for ERROR level entries
2. Use DEBUG level in development for detailed tracing
3. Monitor `security.log` for authentication issues
4. Check API response times in INFO logs

---

## 🎉 Benefits

✅ **Structured Logging**: Easy to parse and analyze  
✅ **Security**: Automatic sensitive data redaction  
✅ **Performance**: Environment-based log levels  
✅ **Debugging**: Detailed processing information  
✅ **Monitoring**: Proper error and security tracking  
✅ **Maintenance**: Log rotation and management  

This logging framework provides professional-grade logging capabilities while maintaining security and performance standards.
