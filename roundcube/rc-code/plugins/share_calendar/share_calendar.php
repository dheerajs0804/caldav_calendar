<?php

/**
* share_calendar
*
* Share calendar
*
* @version 1.0
* @author 
* @url http://roundcube.net/plugins/share_calendar
*/

class share_calendar extends rcube_plugin
{
    // share_calendar will be activated in settings page
    public $task = 'settings';
    private $rc;
    private $caldavserver;
    private $calendaruser;
    private $calendaruserpassword;
    private $caldavclient;
    private $calendarlist;

    function init()
    {
        $this->rc = rcube::get_instance();

        $this->load_config('config.inc.php.dist');
        $this->load_config();
        
        $this->caldavserver = $this->rc->config->get ('caldav_server_url');
        $this->calendaruser = $this->rc->user->data['username'];
        $this->calendaruserpassword = $this->rc->decrypt($_SESSION['password']);

        if(!class_exists("share_calendar_caldav_client")) {
            require_once(dirname(__FILE__).'/share_calendar_caldav_client.php');
        }
        $this->caldavclient = new mithi\caldav\client\share_calendar_caldav_client($this->caldavserver, $this->calendaruser, $this->calendaruserpassword);

        $this->calendarlist = $this->getCalendarList();
        $this->add_texts('localization/', true);
        $this->add_hook('settings_actions', array($this, 'settings_actions'));
        $this->register_action ('plugin.share_calendar', array($this, 'init_html'));
        $this->register_action ( 'plugin.share_calendar.share', array($this, 'share'));
        $this->register_action ( 'plugin.share_calendar.unshare', array($this, 'un_share'));
        if (strpos ( $this->rc->action, 'plugin.share_calendar' ) === 0) {
            $this->include_script('share_calendar.js');
        }
    }

    function settings_actions($args) 
    {
        $args ['actions'] [] = array(
            'action' => 'plugin.share_calendar',
            'label' => 'share_calendar.share_calendar',
            'title' => 'share_calendar.share_calendar'
        );

        return $args;
    }
    
    function init_html() 
    {
        $this->register_handler('plugin.share_calendar_form', array(
            $this, 
            'generate_share_calendar_form'
        ));
        $this->register_handler('plugin.shared_calendars_table', array(
            $this,
            'generate_shared_calendars_table_html'
        ));
        $this->rc->output->set_pagetitle ($this->gettext('share_calendar'));
        $this->rc->output->send('share_calendar.share_calendar');
    }

    function compare_displayname($a, $b)
    {
        return strcmp($a["displayname"], $b["displayname"]);
    }
    
    function compare_users($a, $b)
    {
        return strcmp($a["common-name"], $b["common-name"]);
    }
    
    function shared_calendar_table($attrib, $calendar) 
    {
        $calendar_display_name = $calendar['displayname'];
        if(empty($calendar_display_name)) {
            $calendar_display_name = basename($calendar['href']);
        }

        $out = "<div><div>";
        $out .= "<h3>".$calendar_display_name."</h3>";
        $table_id = 'shared_calendar_'.basename($calendar['href']);
        $shared_calendar = new html_table(array(
            'id' => $table_id,
            'class' => 'records-table',
            'cellspacing' => '0',
            'cols' => 4
        ));

        $shared_calendar->add_header(array('width' => '150px'), $this->gettext('sharedwith'));
        $shared_calendar->add_header(array('width' => '25px'), $this->gettext('access'));
        $shared_calendar->add_header(array('width' => '40px'), $this->gettext('status'));
        $shared_calendar->add_header(array('width' => '15px'), $this->gettext('unshare'));

        $users = $calendar['users'];
        usort($users, array($this, "compare_users"));
        
        foreach($users as $user) {
            $class = ($class == 'odd' ? 'even' : 'odd');
            $td_id = 'td_'.$user['common-name'];
            $shared_calendar->set_row_attribs(array(
                'id' => $td_id,
                'class' => $class
            ));
            
            $shared_calendar->add(array(), $user['common-name']);
            $shared_calendar->add(array(), $user['access']);
            $shared_calendar->add(array(), $user['status']);
            
            $delete_button = html::img(array(
                'src' => $attrib['disableicon'],
                'alt' => $this->gettext('delete'),
                'border' => 0
            ));
            
            $functionparams = '';
            $functionparams .= "'".$table_id."'";
            $functionparams .= ", '".$td_id."'";
            $functionparams .= ", '".$calendar['href']."'";
            $functionparams .= ", '".$calendar_display_name."'";
            $functionparams .= ", '".$user['common-name']."'";
            
            $deletefunction = "row_del(";
            $deletefunction .= $functionparams;
            $deletefunction .= ");";
            
            $shared_calendar->add(array(
                'onclick' => $deletefunction
            ), $delete_button);
        }
        
        if(empty($calendar['users'])) {
            $shared_calendar->add(array('colspan' => '4'), rcube_utils::rep_specialchars_output($this->gettext('noshare')));
            $shared_calendar->set_row_attribs(array('class' => 'odd'));
            $shared_calendar->add_row();
        }

        $out .= "<div id=\"shared_calendars_container\">" . $shared_calendar->show () . "</div></div></div>";
        return $out;
    }

    function shared_calendars_table($attrib, $usercalendars)
    {
        $out = "";

        foreach($usercalendars as $usercalendar) {
            $out .= $this->shared_calendar_table($attrib, $usercalendar);
        }
        return $out;
    }

    function generate_shared_calendars_table_html($attrib)
    {
        $usercalendars = $this->getSharedCalendarList();
        usort($usercalendars, array($this, "compare_displayname"));

        if(!empty($usercalendars)) {
            $out .= $this->shared_calendars_table($attrib, $usercalendars);
        }
        return $out;
    }

    private function getShareCalendarSelectOptionValues($calendarToShare) {
        $selectOptionValues = array();
        foreach($calendarToShare as $url => $displayName) {
            $selectOptionValues[] = $displayName."MITHI_CAL_VAL_SEPARATOR".basename($url);
        }
        return $selectOptionValues;
    }

    function generate_share_calendar_form($attrib)
    {
        $out .= '<table class="table table-borderless"' . $attrib_str . ">";

        $hidden_id = new html_hiddenfield(array(
            'name' => '_id',
            'value' => $mailget_id
        ));
        $out .= $hidden_id->show();
        
        $field_id = 'calendarname';
        $input_calendarname = new html_select (array(
            'name' => '_calendarname',
            'id' => $field_id
        ));
        $calendarToShare = $this->calendarlist;
        $input_calendarname->add(array_values($calendarToShare), $this->getShareCalendarSelectOptionValues($this->calendarlist));
        $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>", $field_id, rcube_utils::rep_specialchars_output($this->gettext('calendarname')), $input_calendarname->show($calendarname));

        $sharewith = '';
        $field_id = 'sharewith';
        $input_sharewith = new html_textarea(array(
            'name' => '_sharewith',
            'id' => $field_id,
            'cols' => 70,
            'rows' => 1,
            'tabindex' => '1',
            'spellcheck' => 'false',
            'autocomplete' => 'off',
            'aria-autocomplete' => 'list',
            'aria-expanded' => 'false',
            'aria-haspopup' => 'false',
            'style' => 'height: 18px; resize: none;'
        ));
        $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>", $field_id, rcube_utils::rep_specialchars_output($this->gettext('sharewith')), $input_sharewith->show($sharewith));
        $canwrite = '0';
        $field_id = 'canwrite';
        $input_canwriteenabled = new html_checkbox(array(
            'name' => '_canwrite',
            'id' => $field_id,
            'value' => '0'
        ));
        $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>", $field_id, rcube_utils::rep_specialchars_output($this->gettext('canwrite')), $input_canwriteenabled->show($canwrite));
        $hidden_share_calendar = new html_hiddenfield(array(
            'name' => '_share_calendar',
            'value' => ''
        ));
        $out .= $hidden_share_calendar->show();

        $hidden_share_calendar_display_name = new html_hiddenfield(array(
            'name' => '_share_calendar_display_name',
            'value' => ''
        ));
        $out .= $hidden_share_calendar_display_name->show();

        $out .= "</table>";

        $this->rc->output->add_gui_object('share_calendar_form', 'share_calendar-form');

        return $out;
    }

    function getSharedCalendarList()
    {
        $shared_calendars = $this->caldavclient->listSharedCalendars();
        return $shared_calendars;
    }
    
    function getCalendarList()
    {
        $calendars = $this->caldavclient->listCalendars();
        if(!is_array($calendars)) {
            $calendars = array();
        }
        asort($calendars);
        return $calendars;
    }

    function do_share_calendar($calendar_name, $calendar_display_name, $share_with, $can_write)
    {
        $result = $this->caldavclient->shareCalendar($calendar_name, $calendar_display_name, $share_with, $can_write);
        return $result;
    }

    function do_un_share_calendar($calendar_url, $un_share_with)
    {
        $result = $this->caldavclient->unShareCalendar($calendar_url, $un_share_with);
        return $result;
    }
    
    function un_share()
    {
        $calendar_display_name = rcube_utils::get_input_value('_calendar_display_name', rcube_utils::INPUT_GET);
        $calendar_url = rcube_utils::get_input_value('_calendar_url', rcube_utils::INPUT_GET);
        $calendar = basename($calendar_url);
        $un_share_with = rcube_utils::get_input_value('_un_share_with', rcube_utils::INPUT_GET);
        $result = $this->do_un_share_calendar($calendar, $un_share_with);
        switch($result[1]['http_code']) {
            case 200 :
                $sharemessage = $this->gettext('successfullyunshared').' '.$calendar_display_name.' from '.$un_share_with;
                $this->rc->output->command('display_message', $sharemessage, 'confirmation' );
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

    function share()
    {
        $calendar = rcube_utils::get_input_value('_share_calendar', rcube_utils::INPUT_POST);
        $calendar_display_name = rcube_utils::get_input_value('_share_calendar_display_name', rcube_utils::INPUT_POST);
        $share_with = rcube_mime::decode_address_list(rcube_utils::get_input_value('_sharewith', rcube_utils::INPUT_POST, true), null, true, null, true);
        $can_write = rcube_utils::get_input_value('_canwrite', rcube_utils::INPUT_POST);
        if(isset($can_write) && $can_write == 0) {
            $can_write = true;
        } else {
            $can_write = false;
        }
        
        $result = $this->do_share_calendar($calendar, $calendar_display_name, $share_with, $can_write);
        switch($result[1]['http_code']) {
            case 200 :
                $share_message = $this->gettext('successfullyshared').' '.$calendar_display_name.' with '.$share_with;
                $this->rc->output->command('display_message', $share_message, 'confirmation' );
                break;
            case 401 :
                $this->rc->output->command('display_message', 'Error: '.$this->gettext('unathorized'), 'error');
                break;
            case 403 :
                $this->rc->output->command('display_message', 'Error: '.$calendar_display_name.' '.$this->gettext('exist'), 'error');
                break;
            default :
                $this->rc->output->command('display_message', 'Error: '.$this->gettext('internalerror'), 'error');
                break;
        }
        $this->init_html();
    }    
}
?>
