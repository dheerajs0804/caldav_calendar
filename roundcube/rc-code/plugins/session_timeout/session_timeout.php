<?php

/**
 * Session Timeout Termination Plugin for Roundcube
 * This plugin will terminate the user session after the session_lifetime period has exceeded.
 *
 * @version 1.0
 * @author Shahid
 * @license GNU GPL v3
 */

class session_timeout extends rcube_plugin
{
    public $task = '?(?!login|logout).*'; // Applies to all tasks except login/logout

    function init()
    {
        // Add hook to the 'startup' event which is triggered on each page load
        $this->add_hook('startup', array($this, 'startup'));
    }

    function startup($args)
    {
        $rcmail = rcmail::get_instance();

        // Get the session lifetime value from config (in minutes)
        $session_lifetime = $rcmail->config->get('session_lifetime', 15);  // Default to 15 minutes if not set
        $session_lifetime_seconds = $session_lifetime * 60; // Convert to seconds


        $current_time = time();

        // Check the last activity time in session
        if (isset($_SESSION['last_activity'])) {
            $last_activity = $_SESSION['last_activity'];

            // Log the last activity timestamp

            // Check if the session lifetime has been exceeded
            $time_difference = $current_time - $last_activity;

            if ($time_difference > $session_lifetime_seconds) {
                // Log the timeout event

                // Destroy the session
                session_destroy();
                session_unset();

                // Redirect the user to the logout page
                $rcmail->output->redirect(array('_task' => 'logout'));
            } else {
                // Log that the session is still active within the lifetime
                error_log("Session is still active. Last activity was within the session lifetime.");
            }
        } else {
            error_log("No 'last_activity' timestamp found in session.");
        }


        // Update the session last activity timestamp
        $_SESSION['last_activity'] = $current_time;

        // Log the updated session activity time
        return $args;
    }
}
?>
