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
        
        // Always add the calendar button, regardless of framing
        $this->add_button([
            'command'    => 'calendar',
            'class'      => 'button-calendar',
            'classsel'   => 'button-calendar',
            'innerclass' => 'button-inner',
            'label'      => 'Calendar',
            'type'       => 'link',
            'target'     => '_blank',
            'href'       => 'http://localhost:3000'
        ], 'taskbar');
        
        // Add stylesheet for the button
        $this->include_stylesheet($this->local_skin_path() . '/calendar_link.css');
    }
    
    function add_script($args)
    {
        $rcmail = rcmail::get_instance();
        
        if (!$rcmail->output->framed) {
            // Add JavaScript to handle the calendar button click
            $this->include_script('calendar_link.js');
        }
    }
    
    function add_calendar_link($args)
    {
        if (isset($args['content'])) {
            // Create a completely clean calendar button without any icons
            $args['content'] .= '<li class="calendar-link"><a href="http://localhost:3000" target="_blank" class="calendar-button" onclick="window.open(\'http://localhost:3000\', \'_blank\'); return false;" style="background: none; background-image: none;">Calendar</a></li>';
        }
        return $args;
    }
}
