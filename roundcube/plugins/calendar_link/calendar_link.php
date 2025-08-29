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
    }
    
    function startup($args)
    {
        $rcmail = rcmail::get_instance();
        
        // Get configuration values
        $calendar_url = $rcmail->config->get('calendar_app_url', 'http://localhost:4200');
        $calendar_text = $rcmail->config->get('calendar_link_text', '📅 Calendar App');
        
        // Always add the calendar button, regardless of framing
        $this->add_button([
            'command'    => 'calendar',
            'class'      => 'button-calendar',
            'classsel'   => 'button-calendar',
            'innerclass' => 'button-inner',
            'label'      => $calendar_text,
            'type'       => 'link',
            'target'     => '_blank',
            'href'       => $calendar_url
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
            
            // Create a completely clean calendar button without any icons
            $args['content'] .= '<li class="calendar-link"><a href="' . $calendar_url . '" target="_blank" class="calendar-button" onclick="window.open(\'' . $calendar_url . '\', \'_blank\'); return false;" style="background: none; background-image: none;">' . $calendar_text . '</a></li>';
        }
        return $args;
    }
}
