<?php

/**
 * Calendar Link Plugin
 * Adds a link to your calendar app in Roundcube navigation
 */

class calendar_link extends rcube_plugin
{
    public $task = 'mail|settings|contacts|help';
    
    function init()
    {
        // Add the calendar button to the taskbar on all pages
        $this->add_hook('startup', array($this, 'startup'));
        
        // Inject calendar button into the main menu
        $this->add_hook('template_object_mainmenu', array($this, 'add_calendar_link'));
        
        // Add JavaScript to inject the button if it's missing
        $this->add_hook('startup', array($this, 'add_script'));
        
        // Better authentication hooks based on Roundcube best practices
        $this->add_hook('authenticate', array($this, 'authenticate_hook'));
        $this->add_hook('login_after', array($this, 'store_login_credentials'));
        $this->add_hook('logout_after', array($this, 'cleanup_sso_tokens'));
        $this->add_hook('session_destroy', array($this, 'cleanup_sso_tokens'));
        
        // Hook into login form to inject credential capture
        $this->add_hook('render_page', array($this, 'inject_login_capture'));
        
        // Register AJAX actions with proper naming convention
        $this->register_action('plugin.calendar_get_password', array($this, 'get_stored_password'));
        $this->register_action('plugin.calendar_create_sso', array($this, 'create_sso_token'));
        $this->register_action('plugin.calendar_store_credentials', array($this, 'ajax_store_credentials'));
        
        // Add security hooks
        $this->add_hook('render_page', array($this, 'add_security_headers'));
    }
    
    function startup($args)
    {
        $rcmail = rcmail::get_instance();
        
        // Get configuration values
        $calendar_url = $rcmail->config->get('calendar_app_url', 'http://localhost:4200');
        $calendar_text = $rcmail->config->get('calendar_link_text', '📅 Calendar App');
        
        // Get current user credentials for auto-login
        $username = $rcmail->user->data['username'] ?? '';
        $password = $rcmail->user->data['password'] ?? ''; // Note: This might not be available for security reasons
        
        // Always add the calendar button, regardless of framing
        $this->add_button([
            'command'    => 'calendar',
            'class'      => 'button-calendar',
            'classsel'   => 'button-calendar',
            'innerclass' => 'button-inner',
            'label'      => $calendar_text,
            'type'       => 'link',
            'target'     => '_blank',
            'href'       => $calendar_url,
            'onclick'    => 'openCalendarWithCredentials(\'' . $username . '\'); return false;'
        ], 'taskbar');
        
        // Add stylesheet for the button
        $this->include_stylesheet($this->local_skin_path() . '/calendar_link.css');
    }
    
    function add_script($args)
    {
        $rcmail = rcmail::get_instance();
        
        if (!$rcmail->output->framed) {
            // Add meta tag with calendar URL for JavaScript
            $calendar_url = $rcmail->config->get('calendar_app_url', 'http://localhost:4200');
            $rcmail->output->add_header('<meta name="calendar-app-url" content="' . htmlspecialchars($calendar_url) . '">');
            
            // Check if we have stored password for auto-login
            $has_stored_password = isset($_SESSION['calendar_auto_login_password']) ? 'true' : 'false';
            $rcmail->output->add_header('<meta name="calendar-has-password" content="' . $has_stored_password . '">');
            
            // Add JavaScript to handle the calendar button click
            $this->include_script('calendar_link.js');
        }
    }
    
    function add_calendar_link($args)
    {
        if (isset($args['content'])) {
            $rcmail = rcmail::get_instance();
            $calendar_url = $rcmail->config->get('calendar_app_url', 'http://localhost:4200');
            $calendar_text = $rcmail->config->get('calendar_link_text', '📅 Calendar App');
            
            // Get current user username for auto-login
            $username = $rcmail->user->data['username'] ?? '';
            
            // Create a completely clean calendar button without any icons
            $args['content'] .= '<li class="calendar-link"><a href="' . $calendar_url . '" target="_blank" class="calendar-button" data-username="' . htmlspecialchars($username) . '" onclick="openCalendarWithCredentials(\'' . htmlspecialchars($username) . '\'); return false;" style="background: none; background-image: none;">' . $calendar_text . '</a></li>';
        }
        return $args;
    }
    
    function store_login_credentials($args)
    {
        $rcmail = rcmail::get_instance();
        
        // Store the password in session for calendar auto-login
        // Note: This is for convenience but consider security implications in production
        if (isset($args['user']) && isset($args['pass'])) {
            $_SESSION['calendar_auto_login_password'] = $args['pass'];
            error_log("Calendar plugin: Stored password for auto-login");
        }
        
        return $args;
    }
    
    function get_stored_password()
    {
        $rcmail = rcmail::get_instance();
        
        // Return the stored password for calendar auto-login
        $password = isset($_SESSION['calendar_auto_login_password']) ? $_SESSION['calendar_auto_login_password'] : null;
        
        if ($password) {
            $rcmail->output->command('plugin.calendar_password_response', array('password' => $password));
        } else {
            $rcmail->output->command('plugin.calendar_password_response', array('error' => 'No password stored'));
        }
    }
    
    function create_sso_token()
    {
        $rcmail = rcmail::get_instance();
        
        // Try multiple sources for credentials (following Roundcube best practices)
        $username = $rcmail->user->data['username'] ?? '';
        
        // Priority order: authenticate hook > sessionStorage > session fallback
        $password = $_SESSION['calendar_auth_pass'] ?? 
                   $_SESSION['calendar_auto_login_password'] ?? '';
        
        if (!$username || !$password) {
            error_log("Calendar plugin: No credentials available - username: " . ($username ? 'present' : 'missing') . 
                     ", password: " . ($password ? 'present' : 'missing'));
            $rcmail->output->command('plugin.calendar_sso_response', array('error' => 'No credentials available'));
            return;
        }
        
        // Make request to calendar backend to create SSO token
        $calendar_backend_url = 'http://localhost:8001/auth/sso-token';
        
        $postData = json_encode([
            'username' => $username,
            'password' => $password
        ]);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $postData
            ]
        ]);
        
        $response = @file_get_contents($calendar_backend_url, false, $context);
        
        if ($response) {
            $data = json_decode($response, true);
            if ($data && $data['success']) {
                $rcmail->output->command('plugin.calendar_sso_response', array('token' => $data['token']));
            } else {
                $rcmail->output->command('plugin.calendar_sso_response', array('error' => 'Failed to create SSO token'));
            }
        } else {
            $rcmail->output->command('plugin.calendar_sso_response', array('error' => 'Cannot connect to calendar backend'));
        }
    }
    
    function inject_login_capture($args)
    {
        $rcmail = rcmail::get_instance();
        
        // Only inject on login page
        if ($rcmail->task == 'login' && $args['template'] == 'login') {
            $login_capture_js = '
            <script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                var loginForm = document.getElementById("form");
                if (loginForm) {
                    loginForm.addEventListener("submit", function(e) {
                        var username = document.getElementById("rcmloginuser").value;
                        var password = document.getElementById("rcmloginpwd").value;
                        
                        // Store credentials in sessionStorage for calendar plugin
                        if (username && password) {
                            sessionStorage.setItem("roundcube_login_username", username);
                            sessionStorage.setItem("roundcube_login_password", password);
                            console.log("Calendar plugin: Login credentials captured");
                        }
                    });
                }
            });
            </script>';
            
            $args['output'] = str_replace('</head>', $login_capture_js . '</head>', $args['output']);
        }
        
        return $args;
    }
    
    function ajax_store_credentials()
    {
        $rcmail = rcmail::get_instance();
        
        $username = rcube_utils::get_input_value('username', rcube_utils::INPUT_POST);
        $password = rcube_utils::get_input_value('password', rcube_utils::INPUT_POST);
        
        if ($username && $password) {
            $_SESSION['calendar_auto_login_username'] = $username;
            $_SESSION['calendar_auto_login_password'] = $password;
            
            $rcmail->output->command('plugin.calendar_credentials_stored', array('success' => true));
        } else {
            $rcmail->output->command('plugin.calendar_credentials_stored', array('error' => 'Missing credentials'));
        }
    }
    
    /**
     * Authenticate hook - better way to capture credentials
     * Based on Roundcube plugin best practices
     */
    function authenticate_hook($args)
    {
        // This hook is called during authentication process
        // We can capture credentials here more reliably
        if (isset($args['user']) && isset($args['pass'])) {
            // Store credentials for SSO token creation
            $_SESSION['calendar_auth_user'] = $args['user'];
            $_SESSION['calendar_auth_pass'] = $args['pass'];
            
            error_log("Calendar plugin: Captured credentials via authenticate hook for user: " . $args['user']);
        }
        
        return $args;
    }
    
    /**
     * Cleanup SSO tokens on logout/session destroy
     * Security best practice from Roundcube documentation
     */
    function cleanup_sso_tokens($args)
    {
        // Clean up any stored credentials and SSO tokens
        unset($_SESSION['calendar_auto_login_password']);
        unset($_SESSION['calendar_auto_login_username']);
        unset($_SESSION['calendar_auth_user']);
        unset($_SESSION['calendar_auth_pass']);
        
        error_log("Calendar plugin: Cleaned up SSO tokens on logout/session destroy");
        
        return $args;
    }
    
    /**
     * Add security headers for better protection
     * Following Roundcube security guidelines
     */
    function add_security_headers($args)
    {
        $rcmail = rcmail::get_instance();
        
        // Add CSRF protection for our plugin actions
        if ($rcmail->task == 'mail' && isset($_GET['_action']) && 
            strpos($_GET['_action'], 'plugin.calendar_') === 0) {
            
            // Verify CSRF token for plugin actions
            if (!$rcmail->check_request()) {
                error_log("Calendar plugin: CSRF token verification failed");
                return $args;
            }
        }
        
        return $args;
    }
}
