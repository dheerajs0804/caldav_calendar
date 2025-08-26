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
    
    // Handle calendar button clicks
    var calendarButtons = document.querySelectorAll('.button-calendar, .calendar-button');
    
    calendarButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            window.open('http://localhost:3000', '_blank');
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
                            window.open('http://localhost:3000', '_blank');
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
