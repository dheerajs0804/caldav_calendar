# CalDAV Server Configuration Guide
# Based on Apple's Calendar and Contacts Server Documentation

## Server Issues Identified

Based on our diagnostic tests, the CalDAV server at `rc.mithi.com:8008` has the following issues:

### 1. **Method Restrictions (405 Not Allowed)**
- **Problem**: Server rejects PROPFIND requests
- **Cause**: nginx server not configured for WebDAV/CalDAV methods
- **Solution**: Configure nginx to allow WebDAV methods

### 2. **Protocol Support**
- **Problem**: Server doesn't properly support CalDAV protocol
- **Reference**: [Calendar and Contacts Server](https://www.calendarserver.org/) standards
- **Solution**: Enable CalDAV protocol support

## Recommended Server Configuration

### For nginx Server Administrator:

```nginx
# Example nginx configuration for CalDAV support
server {
    listen 8008;
    server_name rc.mithi.com;
    
    # Allow WebDAV methods
    location / {
        # Enable WebDAV methods
        dav_methods PUT DELETE MKCOL COPY MOVE;
        dav_methods PROPFIND PROPPATCH OPTIONS;
        dav_methods REPORT;
        
        # Allow CalDAV headers
        dav_ext_methods PROPFIND OPTIONS REPORT;
        
        # Set proper content types
        dav_types text/calendar application/xml;
        
        # Authentication
        auth_basic "CalDAV Server";
        auth_basic_user_file /path/to/users.htpasswd;
        
        # Handle CalDAV requests
        if ($request_method = PROPFIND) {
            add_header Allow "GET, HEAD, POST, PUT, DELETE, TRACE, OPTIONS, COPY, MOVE, MKCOL, PROPFIND, PROPPATCH, LOCK, UNLOCK, REPORT";
        }
        
        if ($request_method = REPORT) {
            add_header Allow "GET, HEAD, POST, PUT, DELETE, TRACE, OPTIONS, COPY, MOVE, MKCOL, PROPFIND, PROPPATCH, LOCK, UNLOCK, REPORT";
        }
    }
    
    # Specific CalDAV endpoints
    location /caldav/ {
        dav_methods PUT DELETE MKCOL COPY MOVE;
        dav_methods PROPFIND PROPPATCH OPTIONS;
        dav_methods REPORT;
        dav_ext_methods PROPFIND OPTIONS REPORT;
    }
    
    location /principals/ {
        dav_methods PUT DELETE MKCOL COPY MOVE;
        dav_methods PROPFIND PROPPATCH OPTIONS;
        dav_methods REPORT;
        dav_ext_methods PROPFIND OPTIONS REPORT;
    }
}
```

### Alternative: Use Dedicated CalDAV Server

Consider using a dedicated CalDAV server like:
- **Apple Calendar and Contacts Server** ([https://www.calendarserver.org/](https://www.calendarserver.org/))
- **Radicale** (Python-based CalDAV server)
- **Baikal** (PHP-based CalDAV server)

## Testing with Apple's CalDAV Client Library

According to the [CalDAV Client Library documentation](https://www.calendarserver.org/CalDAVClientLibrary.html), you can test the server using:

```bash
# Clone the Apple CalDAV Client Library
git clone https://github.com/apple/ccs-caldavclientlibrary.git

# Run the shell tool to test your server
./runshell.py --server http://rc.mithi.com:8008 --user dheeraj.sharma@mithi.com
```

## Expected Server Response

A properly configured CalDAV server should respond to PROPFIND requests with:

```xml
<?xml version="1.0" encoding="utf-8"?>
<D:multistatus xmlns:D="DAV:">
    <D:response>
        <D:href>/</D:href>
        <D:propstat>
            <D:prop>
                <D:current-user-principal>
                    <D:href>/principals/users/dheeraj.sharma@mithi.com/</D:href>
                </D:current-user-principal>
                <C:calendar-home-set xmlns:C="urn:ietf:params:xml:ns:caldav">
                    <D:href>/calendars/users/dheeraj.sharma@mithi.com/</D:href>
                </C:calendar-home-set>
            </D:prop>
            <D:status>HTTP/1.1 200 OK</D:status>
        </D:propstat>
    </D:response>
</D:multistatus>
```

## Next Steps

1. **Contact Server Administrator** with this configuration guide
2. **Test with Apple's CalDAV Client Library** to verify server functionality
3. **Update server configuration** to support proper CalDAV protocol
4. **Re-test** with our application after server configuration changes

## References

- [Calendar and Contacts Server](https://www.calendarserver.org/)
- [CalDAV Client Library](https://www.calendarserver.org/CalDAVClientLibrary.html)
- [Apple CalDAV Client Library GitHub](https://github.com/apple/ccs-caldavclientlibrary.git)
- [RFC 4791 - CalDAV Protocol](https://tools.ietf.org/html/rfc4791)

