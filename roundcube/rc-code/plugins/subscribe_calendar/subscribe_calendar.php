<?php

/**
* subscribe_calendar
*
* Subscribe calendar
*
* @version 1.0
* @author 
* @url http://roundcube.net/plugins/subscribe_calendar
*/

class subscribe_calendar extends rcube_plugin
{
    // subscribe_calendar will be activated in settings page
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
        if(!class_exists("subscribe_calendar_caldav_client")) {
            require_once(dirname(__FILE__).'/subscribe_calendar_caldav_client.php');
        }
        $this->caldavclient = new mithi\caldav\client\subscribe_calendar_caldav_client($this->caldavserver, $this->calendaruser, $this->calendaruserpassword); 
        $this->add_texts('localization/', true);
        $this->add_hook('settings_actions', array($this, 'settings_actions'));
        $this->register_action('plugin.subscribe_calendar', array($this, 'init_html'));
        $this->register_action('plugin.subscribe_calendar.subscribe', array($this, 'subscribe'));
        if (strpos ( $this->rc->action, 'plugin.subscribe_calendar' ) === 0) {
            $this->include_script('subscribe_calendar.js');
        }
    }

    function settings_actions($args) 
    {
        $args ['actions'] [] = array(
            'action' => 'plugin.subscribe_calendar',
            'label' => 'subscribe_calendar.subscribe_calendar',
            'title' => 'subscribe_calendar.subscribe_calendar'
        );

        return $args;
    }
    
    function init_html() 
    {
        $this->register_handler('plugin.subscribe_calendar_table', array($this, 'generate_subscribe_calendar_table_html'));
        $this->register_handler('plugin.subscribed_calendar_table', array($this, 'generate_subscribed_calendars_table_html'));
        $this->rc->output->set_pagetitle($this->gettext('subscribe_calendar'));
        $this->rc->output->send('subscribe_calendar.subscribe_calendar');
    }

    function invite_notifications_date_sort($a, $b) {
        $t1 = strtotime($a['dtstamp']);
        $t2 = strtotime($b['dtstamp']);
        if ($t1 == $t2) {
            return 0;
        }
        return ($t1 < $t2) ? 1 : -1;
    }

    function sort_subsribed_calendars($a, $b)
    {
        return strcmp($a['invite']['organizer'], $b['invite']['organizer']);
    }

    function generate_subscribed_calendars_table_html($attrib) {
        $subscribedcalendars = $this->list_subscribed_calendars();
        usort($subscribedcalendars, array($this, "sort_subsribed_calendars"));

        $out = '<h3>'.$this->gettext('subsribedcalendars').'</h3>';
        $subscribed_calendars_table = new html_table(array(
            'id' => 'subscribed-calendars-table',
            'class' => 'records-table',
            'cellspacing' => '0',
            'cols' => 3
        ));

        $subscribed_calendars_table->add_header(array('width' => '120px'), $this->gettext('sharedby'));
        $subscribed_calendars_table->add_header(array('width' => '120px'), $this->gettext('calendar'));
        $subscribed_calendars_table->add_header(array('width' => '270px'), $this->gettext('calendarurl'));

        foreach($subscribedcalendars as $calendar) {
            $class = ($class == 'odd' ? 'even' : 'odd');
            $subscribed_calendars_table->set_row_attribs(array(
                'class' => $class
            ));
            $subscribed_calendars_table->add(array(), $calendar['invite']['organizer']);
            $subscribed_calendars_table->add(array(), $calendar['displayname']);
            $subscribed_calendars_table->add(array(), "<div>".urldecode($this->caldavserver.$calendar['href'])."</div>");
        }
        if(empty($subscribedcalendars)) {
            $subscribed_calendars_table->add(array('colspan' => '3'), rcube_utils::rep_specialchars_output($this->gettext('nosubscribedfetch')));
            $subscribed_calendars_table->set_row_attribs(array('class' => 'odd'));
            $subscribed_calendars_table->add_row();
        }
        $out .= "<div id=\"subscribed-calendars\">".$subscribed_calendars_table->show()."</div>";
        return $out;
    }

    function generate_subscribe_calendar_table_html($attrib)
    {
        $calendarinvitenotifications = $this->get_calendar_invite_notifications();
        usort($calendarinvitenotifications, array($this, "invite_notifications_date_sort"));

        $out = '<h3>'.$this->gettext('subsribeinvites').'</h3>';
        $subscribe_calendar_table = new html_table(array(
            'id' => 'subscribe_calendar_table',
            'class' => 'records-table',
            'cellspacing' => '0',
            'cols' => 5
        ));

        $subscribe_calendar_table->add_header(array('width' => '60px'), $this->gettext('invitedate'));
        $subscribe_calendar_table->add_header(array('width' => '150px'), $this->gettext('invitee'));
        $subscribe_calendar_table->add_header(array('width' => '100px'), $this->gettext('summary'));
        $subscribe_calendar_table->add_header(array('width' => '25px'), $this->gettext('accept'));
        $subscribe_calendar_table->add_header(array('width' => '25px'), $this->gettext('decline'));
        
        foreach($calendarinvitenotifications as $invitenotification) {
            $class = ($class == 'odd' ? 'even' : 'odd');
            $subscribe_calendar_table->set_row_attribs(array(
                'class' => $class
            ));
            
            $accept_button = html::img(array(
                'src' => $attrib['enableicon'],
                'alt' => $this->gettext('accept'),
                'border' => 0
            ) );
            
            $decline_button = html::img(array(
                'src' => $attrib['disableicon'],
                'alt' => $this->gettext('decline'),
                'border' => 0
            ) );

            $datetime = strtotime($invitenotification['dtstamp']);
            $dt = new DateTime("@$datetime");
            $displaydatetime = $dt->format('d-m-Y H:i');
            $subscribe_calendar_table->add(array(), $displaydatetime);
            $subscribe_calendar_table->add(array(), $invitenotification['organizer']);
            $subscribe_calendar_table->add(array(), $invitenotification['summary']);

            $functionparams = '';
            $functionparams .= ", '".$invitenotification['uid']."'";
            $functionparams .= ", '".$invitenotification['organizer']."'";
            $functionparams .= ", '".$invitenotification['hosturl']."'";
            $functionparams .= ", '".$invitenotification['summary']."'";
            
            $acceptfunction = "row_del(";
            $acceptfunction .= "'accept'";
            $acceptfunction .= $functionparams;
            $acceptfunction .= ");";
            
            $declinefunction = "row_del(";
            $declinefunction .= "'decline'";
            $declinefunction .= $functionparams;
            $declinefunction .= ");";
            
            $subscribe_calendar_table->add(array(
                'id' => 'td_' . $id,
                'onclick' => $acceptfunction
            ), $accept_button);
            
            $subscribe_calendar_table->add(array(
                'id' => 'td_' . $id,
                'onclick' => $declinefunction
            ), $decline_button);
        }
        
        if(empty($invitenotification)) {
            $subscribe_calendar_table->add(array('colspan' => '5'), rcube_utils::rep_specialchars_output($this->gettext('noinvitefetch')));
            $subscribe_calendar_table->set_row_attribs(array('class' => 'odd'));
            $subscribe_calendar_table->add_row();
        }

        $out .= "<div id=\"subscribe-calendars\">" . $subscribe_calendar_table->show () . "</div>";
        $this->rc->output->add_gui_object ( 'subscribe_calendar', 'subscribe-calendar' );
        return $out;
    }

    public function list_subscribed_calendars()
    {
        $subscribedcalendars = $this->caldavclient->listSubscribedCalendars();
        return $subscribedcalendars;
    }
    
    public function get_calendar_invite_notifications()
    {
        $calendarinvitenotifications = $this->caldavclient->getCalendarInviteNotifications();
        return $calendarinvitenotifications;
    }

    function reply_accept_calendar_invite($reply, $uid, $organizer, $hosturl, $summary)
    {
        $result = $this->caldavclient->replyCalendarInvite($reply, $uid, $hosturl, $summary);
        switch($result[1]['http_code']) {
            case 200 :
                $message = $this->gettext('subscribed');
                $message .= ' '.$summary.' of '.$organizer;
                $this->rc->output->command('display_message', $message, 'confirmation');
                $result = $this->caldavclient->deleteCalendarNotification($uid);
                break;
            case 401 :
                $this->rc->output->command('display_message', 'Error: '.$this->gettext('unathorized'), 'error');
                break;
            default :
                $this->rc->output->command('display_message', 'Error: '.$this->gettext('internalerror'), 'error');
                break;
        }
    }

    function reply_decline_calendar_invite($reply, $uid, $organizer, $hosturl, $summary)
    {
        $result = $this->caldavclient->replyCalendarInvite($reply, $uid, $hosturl, $summary);
        switch($result[1]['http_code']) {
            case 204 :
                $result = $this->caldavclient->deleteCalendarNotification($uid);
                $message = $this->gettext('declined');
                $message .= ' '.$summary.' of '.$organizer;
                $this->rc->output->command('display_message', $message, 'confirmation');
                break;
            case 401 :
                $this->rc->output->command('display_message', 'Error: '.$this->gettext('unathorized'), 'error');
                break;
            default :
                $this->rc->output->command('display_message', 'Error: '.$this->gettext('internalerror'), 'error');
                break;
        }
    }

    function subscribe() 
    {
        $reply = rcube_utils::get_input_value('_reply', rcube_utils::INPUT_GET );
        $uid = rcube_utils::get_input_value('_uid', rcube_utils::INPUT_GET);
        $organizer = rcube_utils::get_input_value('_organizer', rcube_utils::INPUT_GET);
        $hosturl = rcube_utils::get_input_value('_hosturl', rcube_utils::INPUT_GET);
        $summary = rcube_utils::get_input_value('_summary', rcube_utils::INPUT_GET);
        if($reply == 'accept') {
            $this->reply_accept_calendar_invite($reply, $uid, $organizer, $hosturl, $summary);
        } else {
            $this->reply_decline_calendar_invite($reply, $uid, $organizer, $hosturl, $summary);
        }
        $this->init_html();
    }
}
?>
