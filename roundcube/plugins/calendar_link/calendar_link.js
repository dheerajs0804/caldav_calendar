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
    
    // Try to get URL from data attribute or configuration
    var configElement = document.querySelector('meta[name="calendar-app-url"]');
    if (configElement) {
        calendarUrl = configElement.getAttribute('content');
    }
    
    // Function to open calendar with auto-login using SSO
    function openCalendarWithCredentials(username) {
        console.log('Calendar plugin: Starting credential resolution for user:', username);
        
        // Priority 1: Use credentials from Roundcube login (most reliable)
        var loginUsername = sessionStorage.getItem('roundcube_login_username');
        var loginPassword = sessionStorage.getItem('roundcube_login_password');
        
        if (loginUsername && loginPassword) {
            console.log('Calendar plugin: Using captured login credentials for seamless SSO');
            createSSOTokenAndOpenCalendar(loginUsername, loginPassword);
            return;
        }
        
        // Priority 2: Try server-side stored credentials (from authenticate hook)  
        console.log('Calendar plugin: Attempting to use server-side stored credentials');
        rcmail.http_post('plugin.calendar_create_sso', {}, rcmail.set_busy(true, 'loading'));
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
    
    // Function to create SSO token and open calendar
    function createSSOTokenAndOpenCalendar(username, password) {
        // Make request to calendar backend to create SSO token
        fetch('http://localhost:8001/auth/sso-token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                username: username,
                password: password
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('🎯 SSO Token Response:', data);
            if (data.success && data.token) {
                // Open calendar with SSO token
                var calendarUrlWithSSO = calendarUrl + '?sso_token=' + encodeURIComponent(data.token);
                console.log('🎯 Opening calendar with SSO URL:', calendarUrlWithSSO);
                window.open(calendarUrlWithSSO, '_blank');
            } else {
                console.error('❌ Failed to create SSO token:', data.message);
                alert('Failed to create SSO token: ' + (data.message || 'Unknown error'));
                // Fallback to regular calendar
                window.open(calendarUrl, '_blank');
            }
        })
        .catch(error => {
            console.error('Error creating SSO token:', error);
            alert('Error connecting to calendar backend');
            // Fallback to regular calendar
            window.open(calendarUrl, '_blank');
        });
    }
    
    // Make the function globally available
    window.openCalendarWithCredentials = openCalendarWithCredentials;
    
    // Handle calendar button clicks
    var calendarButtons = document.querySelectorAll('.button-calendar, .calendar-button');
    
    calendarButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Check if this button has a username attribute
            var username = button.getAttribute('data-username') || '';
            if (username) {
                openCalendarWithCredentials(username);
            } else {
                // Fallback to regular calendar link
                window.open(calendarUrl, '_blank');
            }
        });
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
                            window.open(calendarUrl, '_blank');
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
});
