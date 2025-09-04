<?php

/**
* add_calendar
*
* Add calendar
*
* @version 1.0
* @author 
* @url http://roundcube.net/plugins/add_calendar
*/

class add_calendar extends rcube_plugin
{
    // add_calendar will be activated in settings page
    public $task = 'settings';
	private $rc;
    private $caldavserver;
    private $calendaruser;
    private $calendaruserpassword;
    private $caldavclient;

    function init()
    {
        $this->rc = rcube::get_instance();

        $this->load_config('config.inc.php.dist');
        $this->load_config();
        
        $this->caldavserver = $this->rc->config->get ('caldav_server_url');
        $this->calendaruser = $this->rc->user->data['username'];
        $this->calendaruserpassword = $this->rc->decrypt($_SESSION['password']);

        if(!class_exists("add_calendar_caldav_client")) {
            require_once(dirname(__FILE__).'/add_calendar_caldav_client.php');
        }
        $this->caldavclient = new mithi\caldav\client\add_calendar_caldav_client($this->caldavserver, $this->calendaruser, $this->calendaruserpassword);
        $this->add_texts('localization/', true);
        $this->add_hook('settings_actions', array($this, 'settings_actions'));
        $this->register_action ('plugin.add_calendar', array($this, 'init_html'));
        $this->register_action ( 'plugin.add_calendar.save', array($this, 'save'));
        $this->register_action ( 'plugin.add_calendar.delete', array($this, 'delete'));
        if (strpos ( $this->rc->action, 'plugin.add_calendar' ) === 0) {
            $this->include_script('add_calendar.js');
        }
    }

    function settings_actions($args) 
    {
        $args ['actions'] [] = array(
            'action' => 'plugin.add_calendar',
            'label' => 'add_calendar.add_calendar',
            'title' => 'add_calendar.add_calendar'
        );

        return $args;
    }
    
    function init_html() 
    {
        $this->register_handler('plugin.add_calendar_form', array($this, 'generate_add_calendar_html'));
        $this->register_handler ('plugin.add_calendar_table', array($this, 'generate_user_calendars_html'));
        $this->rc->output->set_pagetitle ($this->gettext('add_calendar'));
        $this->rc->output->send('add_calendar.add_calendar');
    }

    function generate_user_calendars_html($attrib)
    {
        $usercalendars = $this->getCalendarList();
        $out = "<h3>".$this->gettext('usercalendars').'</h3>';
        $user_calendars = new html_table(array(
            'id' => 'user_calendars',
            'class' => 'table table-striped records-table',
            'cellspacing' => '0',
            'cols' => 3
        ));

        $user_calendars->add_header(array('width' => '100px'), $this->gettext('calendar'));
        $user_calendars->add_header(array('width' => '300px'), $this->gettext('calendarurl'));
        $user_calendars->add_header(array('width' => '25px'), $this->gettext('delete'));

        foreach($usercalendars as $url => $displayname) {
            $class = ($class == 'odd' ? 'even' : 'odd');
            $tdid = 'td_'.basename($url);
            $user_calendars->set_row_attribs(array(
                'id' => $tdid,
                'class' => $class
            ));
            $user_calendars->add(array(), $displayname);
            $user_calendars->add(array(), "<div>".urldecode($this->caldavserver.$url)."</div>");

            $delete_button = html::img(array(
                'src' => $attrib['disableicon'],
                'alt' => $this->gettext('delete'),
                'border' => 0
            ));

            $functionparams = '';
            $functionparams .= "'".$tdid."'";
            $functionparams .= ", '".$url."'";
            $functionparams .= ", '".$displayname."'";
            
            $deletefunction = "row_del(";
            $deletefunction .= $functionparams;
            $deletefunction .= ");";
            
            $user_calendars->add(array(
                'onclick' => $deletefunction
            ), $delete_button);
        }
        
        if(empty($usercalendars)) {
            $user_calendars->add(array('colspan' => '3'), rcube_utils::rep_specialchars_output($this->gettext('nofetch')));
            $user_calendars->set_row_attribs(array('class' => 'odd'));
            $user_calendars->add_row();
        }

        $out .= "<div id=\"user_calendars_container\">" . $user_calendars->show () . "</div>";

        return $out;
    }

    function generate_add_calendar_html()
    {
        $out = '<table' . $attrib_str . ">";

        $field_id = 'calendarname';
        $input_calendarname = new html_inputfield(array(
            'name' => '_calendarname',
            'id' => $field_id,
            'placeholder' => 'Enter calendar name',
            'maxlength' => 320,
            'size' => 40,
            'autofocus' => 1
        ));
        $out .= sprintf ( "<tr><td class=\"title\"><b><label for=\"%s\">%s </label></b></td><br><td>%s</td></tr>", $field_id, rcube_utils::rep_specialchars_output($this->gettext('calendar' )), $input_calendarname->show());
        $out .= "</table>";
        $this->rc->output->add_gui_object('add_calendar_form', 'add_calendar-form');
        return $out;
    }

    function getCalendarList()
    {
        $callendars = $this->caldavclient->listCalendars();
        if(!is_array($callendars)) {
            $callendars = array();
        }
        
        return $callendars;
    }

    function createCalendar($calendarName)
    {
        $result = $this->caldavclient->mkCalendar($calendarName);
        return $result;
    }

    function deleteCalendar($calendarName)
    {
        $result = $this->caldavclient->deleteCalendar($calendarName);
        return $result;
    }
    
    function delete()
    {
        $displayname = rcube_utils::get_input_value ('_displayname', rcube_utils::INPUT_GET);
        $url = rcube_utils::get_input_value('_url', rcube_utils::INPUT_GET);
        $result = $this->deleteCalendar(basename($url));
        switch($result[1]['http_code']) {
            case 204 :
                $deletemessage = $this->gettext('deleted').' '.$displayname;
                $this->rc->output->command('display_message', $deletemessage, 'confirmation' );
                break;
            case 401 :
                $this->rc->output->command('display_message', 'Error: '.$this->gettext('unathorized'), 'error');
                break;
            default :
                $this->rc->output->command('display_message', 'Error: '.$this->gettext('internalerror'), 'error');
                break;
        }
        
        $this->init_html();
    }
    
    function save()
    {
        $id = rcube_utils::get_input_value ( '_id', rcube_utils::INPUT_POST );
        $calendarname = rcube_utils::get_input_value('_calendarname', rcube_utils::INPUT_POST);
        $result = $this->createCalendar($calendarname);
        switch($result[1]['http_code']) {
            case 201 :
                $savemessage = $this->gettext('created').' '.$calendarname;
                $this->rc->output->command('display_message', $savemessage, 'confirmation' );
                break;
            case 401 :
                $this->rc->output->command('display_message', 'Error: '.$this->gettext('unathorized'), 'error');
                break;
            case 403 :
                $this->rc->output->command('display_message', 'Error: '.$calendarname.' '.$this->gettext('exist'), 'error');
                break;
            default :
                $this->rc->output->command('display_message', 'Error: '.$this->gettext('internalerror'), 'error');
                break;
        }
        
        $this->init_html();
    }    
}
?>
