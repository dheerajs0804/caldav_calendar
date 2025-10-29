# Calendar Deletion Issue in Docker - Fix Guide

## 🐛 **Problem**
Calendar deletion works on localhost but fails in Docker containers with error: `"Calendar not found"`

## 🔍 **Root Causes**

### 1. **URL Matching Issue** (PRIMARY ISSUE - FIXED)
- **Problem**: Calendar URL comparison uses exact string match (`===`)
- **Why it fails**: URLs may have different encoding, trailing slashes, or formatting in container vs localhost
- **Fix Applied**: Enhanced URL matching with normalization in `backend/index.php` (lines 2522-2553)

### 2. **Session/Authentication Issue** (LIKELY CAUSE)
- **Problem**: PHP sessions may not persist properly in Docker
- **Symptoms**: 
  - `$_SESSION['caldav_credentials']` empty or missing
  - Falls back to environment variables (which might have wrong/empty credentials)
  - Calendar discovery returns empty or different results
  
### 3. **Calendar Discovery Differences**
- **Problem**: CalDAV client discovers calendars differently due to auth credentials
- **Why**: Different credentials return different calendar lists
- **Result**: Calendar URL doesn't match because it's not in the discovered list

## ✅ **Fixes Applied**

### Fix 1: Improved URL Matching (Already Applied)
```php
// Now handles:
// - Exact match
// - Normalized match (trailing slash removed)
// - URL contains match
// - URL decoding differences
```

### Fix 2: Enhanced Logging (Already Applied)
- Added authentication source tracking (session vs environment)
- Added detailed calendar discovery logging
- Better error messages

## 🔧 **Additional Fixes Needed**

### Fix 3: Ensure Session Persistence in Docker

**Update `backend/Dockerfile`:**
```dockerfile
# Add session directory configuration
RUN mkdir -p /var/www/html/sessions \
    && chown -R www-data:www-data /var/www/html/sessions \
    && chmod -R 775 /var/www/html/sessions

# Configure PHP sessions
RUN echo "session.save_path = \"/var/www/html/sessions\"" >> /usr/local/etc/php/conf.d/sessions.ini \
    && echo "session.gc_probability = 1" >> /usr/local/etc/php/conf.d/sessions.ini \
    && echo "session.gc_divisor = 1000" >> /usr/local/etc/php/conf.d/sessions.ini
```

**Mount session directory as volume:**
```yaml
# In docker-compose.yml or docker run command
volumes:
  - ./sessions:/var/www/html/sessions
```

### Fix 4: Verify Session Cookie Configuration

**Check `backend/index.php` session settings** (around line 302-310):
```php
// Ensure these are set BEFORE session_start():
ini_set('session.cookie_samesite', 'Lax');  // Changed from empty
ini_set('session.cookie_secure', 0);         // 0 for HTTP, 1 for HTTPS
ini_set('session.cookie_httponly', 0);       // 0 to allow JavaScript access
```

### Fix 5: Direct Calendar Deletion Fallback

**If calendar discovery fails, try direct deletion:**
```php
// In deleteCalendar() function, if discovery fails:
// Try to delete directly using the provided calendar URL
```

## 🧪 **Debugging Steps**

### Step 1: Check Session Status
```bash
# Add temporary debug logging
docker exec -it your-backend-container tail -f /var/www/html/logs/php_errors.log | grep -i session
```

**Or add to backend/index.php before getCalDAVClient():**
```php
simpleLog('DEBUG', 'Session status check', [
    'session_id' => session_id(),
    'has_credentials' => isset($_SESSION['caldav_credentials']),
    'session_data' => $_SESSION ?? []
]);
```

### Step 2: Check Calendar Discovery
```bash
# Check backend logs for calendar discovery
docker exec -it your-backend-container tail -f /var/www/html/logs/php_errors.log | grep -i "calendars discovered"
```

**Expected log output:**
```
Calendars discovered: {
  "calendar_id": "...",
  "calendars_count": X,
  "auth_source": "session" or "environment"
}
```

### Step 3: Compare URLs
**Check the "Comparing calendar URLs" debug logs:**
- Are target_url and calendar_url different?
- Is there a trailing slash difference?
- Are they URL encoded differently?

### Step 4: Verify Authentication
```bash
# Check if credentials are in session
docker exec -it your-backend-container php -r "
session_start();
var_dump(\$_SESSION['caldav_credentials'] ?? 'NOT SET');
"
```

## 🚀 **Immediate Workaround**

If the issue persists, try this workaround:

**Option 1: Force session persistence**
```bash
# Run container with session directory mounted:
docker run -d \
  -v $(pwd)/sessions:/var/www/html/sessions \
  -v $(pwd)/data:/var/www/html/data \
  your-backend-image
```

**Option 2: Use environment variables for credentials**
```bash
# Pass CalDAV credentials as environment variables:
docker run -d \
  -e CALDAV_SERVER_URL="http://rc.mithi.com:18008" \
  -e CALDAV_USERNAME="your-username" \
  -e CALDAV_PASSWORD="your-password" \
  your-backend-image
```

**Note**: Option 2 works but less secure - credentials in environment variables.

## 📊 **Expected Behavior After Fix**

1. ✅ Session credentials persist between requests
2. ✅ Calendar discovery finds the same calendars as localhost
3. ✅ URL matching works even with encoding/slash differences
4. ✅ Calendar deletion succeeds in Docker containers

## 🔍 **How to Verify the Fix**

1. **Delete a calendar** from the Docker-hosted app
2. **Check logs** for:
   - `auth_source: session` (not `environment`)
   - `Calendars discovered: X` (same count as localhost)
   - `Calendar found by URL match` (not `Calendar not found`)
3. **Verify deletion** on CalDAV server

## ⚠️ **If Still Failing**

1. **Check CalDAV server logs** - Is the request reaching the server?
2. **Check network connectivity** - Can container reach CalDAV server?
3. **Compare credentials** - Are session credentials same as localhost?
4. **Check URL format** - Log the exact URLs being compared

