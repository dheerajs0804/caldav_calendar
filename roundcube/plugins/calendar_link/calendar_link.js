// Calendar Link Plugin JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Function to ensure calendar button is visible
    function ensureCalendarButtonVisible() {
        // Ensure taskbar calendar button is visible
        var calendarButtons = document.querySelectorAll('.button-calendar, .calendar-button');
        calendarButtons.forEach(function(button) {
            button.style.display = 'block';
            button.style.visibility = 'visible';
            button.style.opacity = '1';
            // Remove any highlighting
            button.classList.remove('button-selected', 'selected');
        });
    }
    
    // Check if we just logged in and store credentials for calendar access
    function checkAndStoreLoginCredentials() {
        // This will be called after successful login to ensure credentials are available
        var username = sessionStorage.getItem('roundcube_login_username');
        var password = sessionStorage.getItem('roundcube_login_password');
        
        if (username && password) {
            console.log('Calendar plugin: Login credentials available for SSO');
            // Credentials are already stored in sessionStorage by login form handler
        }
    }
    
    // Call on page load
    checkAndStoreLoginCredentials();
    
    // Get calendar URL from configuration or use default
    var calendarUrl = 'http://localhost:4200'; // Default fallback
    
    // Try to get URL from meta tag (set by PHP)
    var metaTag = document.querySelector('meta[name="calendar-app-url"]');
    if (metaTag) {
        calendarUrl = metaTag.getAttribute('content');
    }
    
    // Try to get URL from data attribute or configuration
    var configElement = document.querySelector('meta[name="calendar-app-url"]');
    if (configElement) {
        calendarUrl = configElement.getAttribute('content');
    }
    
    // Function to open calendar with auto-login using direct credentials
    function openCalendarWithCredentials(username) {
        console.log('🎯 openCalendarWithCredentials called with username:', username);
        console.log('Calendar plugin: Starting credential resolution for user:', username);
        
        // Priority 1: Use credentials from Roundcube login (most reliable)
        var loginUsername = sessionStorage.getItem('roundcube_login_username');
        var loginPassword = sessionStorage.getItem('roundcube_login_password');
        
        if (loginUsername && loginPassword) {
            console.log('Calendar plugin: Using captured login credentials for direct authentication');
            openCalendarWithDirectCredentials(loginUsername, loginPassword);
            return;
        }
        
        // Priority 2: Try server-side stored credentials (from authenticate hook)  
        console.log('Calendar plugin: Attempting to use server-side stored credentials');
        rcmail.http_post('plugin.calendar_get_credentials', {}, rcmail.set_busy(true, 'loading'));
    }
    
    // Function to prompt for password and create SSO
    function promptAndCreateSSO(username) {
        var password = prompt('Please enter your password to access the calendar app (this will be remembered for this session):');
        
        if (password) {
            // Store password in sessionStorage for this session
            sessionStorage.setItem('roundcube_calendar_password', password);
            console.log('Password stored for this session');
            
            // Create SSO token and open calendar
            createSSOTokenAndOpenCalendar(username, password);
        } else {
            // User cancelled password prompt, open calendar normally
            window.open(calendarUrl, '_blank');
        }
    }
    
    // Simple encryption function using base64 and simple obfuscation
    function encryptPassword(password) {
        // Simple encryption: reverse string + base64 + add salt
        var salt = 'caldev2024';
        var reversed = password.split('').reverse().join('');
        var salted = reversed + salt;
        return btoa(salted);
    }
    
    // Function to open calendar with direct credentials
    function openCalendarWithDirectCredentials(username, password) {
        console.log('🎯 Opening calendar with direct credentials for user:', username);
        
        // Encrypt the password before passing in URL
        var encryptedPassword = encryptPassword(password);
        console.log('🔒 Password encrypted for URL transmission');
        
        // Pass credentials with encrypted password in URL parameters to /login route
        var calendarUrlWithCreds = calendarUrl + '/login?username=' + encodeURIComponent(username) + '&encrypted_password=' + encodeURIComponent(encryptedPassword);
        console.log('🎯 Opening calendar with encrypted credentials URL:', calendarUrlWithCreds);
        
        // Try to open the URL in a new tab
        try {
            var newWindow = window.open(calendarUrlWithCreds, '_blank', 'noopener,noreferrer');
            if (newWindow) {
                console.log('🎯 Successfully opened new window');
                newWindow.focus();
            } else {
                console.error('🎯 Failed to open new window - popup blocked?');
                // Show user-friendly message instead of navigating current tab
                alert('Please allow popups for this site to open the calendar in a new tab, or manually copy this URL:\n\n' + calendarUrlWithCreds);
            }
        } catch (error) {
            console.error('🎯 Error opening window:', error);
            // Show user-friendly message instead of navigating current tab
            alert('Please allow popups for this site to open the calendar in a new tab, or manually copy this URL:\n\n' + calendarUrlWithCreds);
        }
    }
    
    // Make the function globally available
    window.openCalendarWithCredentials = openCalendarWithCredentials;
    
    // Test function for debugging
    window.testOpenCalendar = function() {
        var testUrl = calendarUrl + '/login?username=test&password=test';
        console.log('🧪 Testing window.open with URL:', testUrl);
        var result = window.open(testUrl, '_blank');
        console.log('🧪 window.open result:', result);
        return result;
    };
    
    // Handle calendar button clicks
    var calendarButtons = document.querySelectorAll('.button-calendar, .calendar-button');
    
    console.log('🎯 Found calendar buttons:', calendarButtons.length);
    
    calendarButtons.forEach(function(button) {
        console.log('🎯 Setting up click handler for button:', button);
        button.addEventListener('click', function(e) {
            console.log('🎯 Calendar button clicked!');
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            // Check if this button has a username attribute or extract from onclick
            var username = button.getAttribute('data-username') || '';
            
            // If no data-username, try to extract from onclick attribute
            if (!username) {
                var onclickAttr = button.getAttribute('onclick') || '';
                console.log('🎯 Button onclick:', onclickAttr);
                var match = onclickAttr.match(/openCalendarWithCredentials\('([^']+)'\)/);
                if (match) {
                    username = match[1];
                    console.log('🎯 Extracted username from onclick:', username);
                }
            }
            
            console.log('🎯 Button username:', username);
            if (username) {
                console.log('🎯 Calling openCalendarWithCredentials with username:', username);
                openCalendarWithCredentials(username);
            } else {
                console.log('🎯 No username found, using fallback');
                // Fallback to regular calendar link in new tab only
                window.open(calendarUrl, '_blank', 'noopener,noreferrer');
            }
            
            return false; // Prevent any further event handling
        }, true); // Use capture phase to handle before other handlers
    });
    
    // Ensure button is visible after navigation
    function handleNavigation() {
        setTimeout(ensureCalendarButtonVisible, 100);
        setTimeout(ensureCalendarButtonVisible, 500);
        setTimeout(ensureCalendarButtonVisible, 1000);
    }
    
    // Listen for navigation changes
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                handleNavigation();
                
                // Handle any new calendar buttons
                var newButtons = document.querySelectorAll('.button-calendar, .calendar-button');
                newButtons.forEach(function(button) {
                    if (!button.hasAttribute('data-calendar-handler')) {
                        button.setAttribute('data-calendar-handler', 'true');
                        button.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            e.stopImmediatePropagation();
                            window.open(calendarUrl, '_blank', 'noopener,noreferrer');
                            return false;
                        });
                    }
                });
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Listen for URL changes
    var lastUrl = location.href;
    function checkUrlChange() {
        var url = location.href;
        if (url !== lastUrl) {
            lastUrl = url;
            handleNavigation();
        }
    }
    
    // Check for URL changes periodically
    setInterval(checkUrlChange, 100);
    
    // Also listen for popstate events (back/forward buttons)
    window.addEventListener('popstate', handleNavigation);
    
    // Listen for hash changes
    window.addEventListener('hashchange', handleNavigation);
    
    // Initial check
    ensureCalendarButtonVisible();
    
    // Check again after a delay to ensure it's visible
    setTimeout(ensureCalendarButtonVisible, 2000);
    
    // Check every 2 seconds to ensure the button stays visible
    setInterval(ensureCalendarButtonVisible, 2000);
    
    // Handle credentials response from server
    rcmail.addEventListener('plugin.calendar_credentials_response', function(data) {
        if (data.error) {
            console.error('Calendar plugin: Error getting credentials:', data.error);
            // Fallback to regular calendar
            window.open(calendarUrl, '_blank');
        } else if (data.username && data.password) {
            console.log('Calendar plugin: Got credentials from server, opening calendar');
            openCalendarWithDirectCredentials(data.username, data.password);
        }
    });
    
    // Global click handler as fallback
    document.addEventListener('click', function(e) {
        // Check if clicked element is a calendar button
        if (e.target.classList.contains('button-calendar') || 
            e.target.classList.contains('calendar-button') ||
            e.target.closest('.button-calendar') ||
            e.target.closest('.calendar-button')) {
            
            console.log('🎯 Global click handler: Calendar button clicked!');
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            // Try to get username from various sources
            var username = e.target.getAttribute('data-username') || 
                          e.target.closest('[data-username]')?.getAttribute('data-username') ||
                          '';
            
            // If no data-username, try to extract from onclick attribute
            if (!username) {
                var targetElement = e.target.closest('.button-calendar, .calendar-button') || e.target;
                var onclickAttr = targetElement.getAttribute('onclick') || '';
                console.log('🎯 Global handler onclick:', onclickAttr);
                var match = onclickAttr.match(/openCalendarWithCredentials\('([^']+)'\)/);
                if (match) {
                    username = match[1];
                    console.log('🎯 Global handler extracted username:', username);
                }
            }
            
            // Fallback to known username if still not found
            if (!username) {
                username = 'dheeraj.sharma@mithi.com';
                console.log('🎯 Using fallback username:', username);
            }
            
            console.log('🎯 Global handler using username:', username);
            openCalendarWithCredentials(username);
            
            return false; // Prevent any further event handling
        }
    });
});
