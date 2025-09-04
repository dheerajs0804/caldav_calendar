<?php
namespace mithi\caldav\client;
/**
 * CalDAV Client
 */

require_once (dirname(__FILE__).'/../../vendor/autoload.php');
require_once (dirname(__FILE__).'/../../vendor/mithi/caldav/client/MithiCalDavClient.php');

use Sabre\DAV\Property\ResourceType;

class subscribe_calendar_caldav_client extends MithiCalDavClient
{
    private $calendarHomeSet;
    private $calendarHomeSetAbsoluteURI;

    /**
     *  Default constructor for CalDAV client.
     *
     * @param string $uri Caldav URI to appropriate calendar.
     * @param string $user Username for HTTP Basic Auth.
     * @param string $pass Password for HTTP Basic Auth.
     */
    public function __construct($uri, $user = null, $pass = null)
    {
        parent::__construct($uri, $user, $pass);
        $this->calendarHomeSet = $this->getCalendarHomeSet()[0];
        $this->calendarHomeSetAbsoluteURI = $this->getCalendarBaseURI().$this->calendarHomeSet;
    }

    private function getNotificationsList()
    {
        $calendar = 'notification';
        $url = $this->calendarHomeSetAbsoluteURI.$calendar.'/';
        $xml = '<?xml version="1.0" encoding="utf-8"?>
                <A:propfind xmlns:A="DAV:">
                    <A:prop>
                        <C:notificationtype xmlns:C="http://calendarserver.org/ns/"/>
                    </A:prop>
                </A:propfind>';
        $options = array(
            CURLOPT_HTTPHEADER => Array('Content-Type: application/xml; charset="utf-8"', 'Depth: 1'),
            CURLOPT_USERPWD => $this->getCalendarUserId().":".$this->getCalendarUserPassword(),
            CURLOPT_CUSTOMREQUEST => 'PROPFIND',
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_RETURNTRANSFER => 1
        );
        $result = $this->curlRequest($url, $options);
        return $result;
    }
    
    private function parseNotification($notification)
    {
        $notificationDetail = array();
        $dtstamp = '';
        $uid = '';
        $organizer = '';
        $summary = '';
        $hosturl = '';
        
        $notificationXML = new \SimpleXMLElement($notification);
        
        if(isset($notificationXML->{'invite-notification'}->uid)) {
            $uid = $notificationXML->{'invite-notification'}->uid;
        }
        
        if(isset($notificationXML->dtstamp)) {
            $dtstamp = $notificationXML->dtstamp;
        }

        if(isset($notificationXML->{'invite-notification'}->organizer->{'common-name'})) {
            $organizer = $notificationXML->{'invite-notification'}->organizer->{'common-name'};
        }
                
        if(isset($notificationXML->{'invite-notification'}->summary)) {
            $summary = $summary = $notificationXML->{'invite-notification'}->summary;
        }

        if(isset($notificationXML->{'invite-notification'}->hosturl->href)) {
            $hosturl = $hosturl = $notificationXML->{'invite-notification'}->hosturl->href;
        }
        
        // Check no response is sent to invite
        $inviteNoResponse = false;
        foreach($notificationXML->{'invite-notification'}->children() as $key=>$value) {
            if($key == 'invite-noresponse') {
                $inviteNoResponse = true;
            }
        }

        if($inviteNoResponse) {
            $notificationDetail['uid'] = $uid;
            $notificationDetail['dtstamp'] = $dtstamp;
            $notificationDetail['organizer'] = $organizer;
            $notificationDetail['summary'] = $summary;
            $notificationDetail['hosturl'] = $hosturl;
        }
        return $notificationDetail;
    }
    
    private function parseNotifications($notifications)
    {
        $notificationDetails = array();
        foreach($notifications as $notification) {
            $notificationDetail = $this->parseNotification($notification);
            if(!empty($notificationDetail)) {
                $notificationDetails[] = $notificationDetail;
            }
        }
        return $notificationDetails;
    }
    
    private function getNotification($notification)
    {
        $calendar = "notification";
        $url = $this->calendarHomeSetAbsoluteURI.$calendar.'/'.$notification;
        $options = array(
            CURLOPT_HTTPHEADER => Array('Content-Type: application/xml; charset="utf-8"'),
            CURLOPT_USERPWD => $this->getCalendarUserId().":".$this->getCalendarUserPassword(),
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_RETURNTRANSFER => 1
        );

        $result = $this->curlRequest($url, $options);
        return $result;
    }

    
    private function getNotifications($notificationList)
    {
        $notifications = array();
        foreach($notificationList as $notification) {
            $result = $this->getNotification(basename($notification));
            if(!empty($result) && isset($result[0])) {
                $notifications[] = $result[0];
            }
        }
        return $notifications;
    }
    
    private function isSharedTypeCalendar($notification) {
        $inviteShareType = false;
        if($notification->propstat->prop->notificationtype->{'invite-notification'}[0] &&
          $notification->propstat->prop->notificationtype->{'invite-notification'}[0]->attributes()) {
            // check node invite-notification has attribute shared-type with value calendar
            $inviteNotificationAttr = $notification->propstat->prop->notificationtype->{'invite-notification'}[0]->attributes();
            foreach($inviteNotificationAttr as $attr => $value) {
                if($attr == 'shared-type' && $value == 'calendar') {
                    $inviteShareType = 'true';
                }
            }
        }
        return $inviteShareType;
    }
    
    private function parseNotificationToGetHref($notifications)
    {
        $notificationHref = array();
        try {
            $notificationsXML = new \SimpleXMLElement($notifications);
            foreach($notificationsXML->response as $notification) {
                $status = $notification->propstat->status;
                list($httpVersion, $statusCode, $message) = explode(' ', (string)$status[0],3);
                if($statusCode == 200) {
                    $inviteShareType = $this->isSharedTypeCalendar($notification);
                    if($inviteShareType && isset($notification->href))  
                    {
                        $notificationHref[] = $notification->href;
                    }
                }
            }
        } catch(Exception $exception) {
            $notificationHref = array();
        }
        
        return $notificationHref;
    }
    
    public function getCalendarInviteNotifications()
    {
        $calendarInviteNotifications = array();
        $result = $this->getNotificationsList();
        if($result[1]['http_code'] == 207) {
            $result = $this->parseNotificationToGetHref($result[0]);
            $result = $this->getNotifications($result);
            $calendarInviteNotifications = $this->parseNotifications($result);
        }
        return $calendarInviteNotifications;
    }

    public function deleteCalendarNotification($notification)
    {
        $calendar = "notification";
        $url = $this->calendarHomeSetAbsoluteURI.$calendar.'/'.$notification.'.xml';
        $options = array(
            CURLOPT_HTTPHEADER => Array('Content-Type: application/xml; charset="utf-8"'),
            CURLOPT_USERPWD => $this->getCalendarUserId().":".$this->getCalendarUserPassword(),
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_RETURNTRANSFER => 1
        );
        $result = $this->curlRequest($url, $options);
        return $result;
    }

    public function replyCalendarInvite($reply, $uid, $hosturl, $summary)
    {
        $url = $this->calendarHomeSetAbsoluteURI;
        $inviteReplay = "<CS:invite-accepted />";
        if($reply == 'decline') {
            $inviteReplay = "<CS:invite-declined />";
        }
        $xml = "<CS:invite-reply xmlns:D=\"DAV:\" xmlns:CS=\"http://calendarserver.org/ns/\">\n";
        $xml .= $inviteReplay."\n";
        $xml .= "<CS:hosturl><D:href>".$hosturl."</D:href></CS:hosturl>\n";
        $xml .= "<CS:in-reply-to>".$uid."</CS:in-reply-to>\n";
        $xml .= "<CS:summary>".$summary."</CS:summary>\n";
        $xml .= "</CS:invite-reply>";
        
        $options = array(
            CURLOPT_HTTPHEADER => Array('Content-Type: application/xml; charset="utf-8"'),
            CURLOPT_USERPWD => $this->getCalendarUserId().":".$this->getCalendarUserPassword(),
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_RETURNTRANSFER => 1
        );
        $result = $this->curlRequest($url, $options);
        return $result;
    }

    private function isInviteAccepted($invite) {
        $accepted = false;
        foreach($invite->user as $userInviteDetails) {
            $common_name = strtolower(strval($userInviteDetails->{'common-name'}));
            $emailAddressSetInLowerCase = array_map('strtolower', $this->getEmailAddressSet());
            if(in_array($common_name, $emailAddressSetInLowerCase)) {
                if($userInviteDetails->{'invite-accepted'}) {
                    $accepted = true;
                }
            }
        }
        return $accepted;
    }

    private function parseSubscribedCalendar($calendar)
    {
        $subscribedCalendar = array();
        foreach($calendar->propstat as $propstat) {
            $status = $propstat->status;
            list($httpVersion, $statusCode, $message) = explode(' ', (string)$status[0],3);
            if($statusCode == 200) {
                if($propstat->prop->resourcetype->calendar && $propstat->prop->resourcetype->shared) {
                    $subscribedCalendar['displayname'] = $propstat->prop->displayname;
                    $subscribedCalendar['href'] = $calendar->href;
                }
                // If invite is accepted then get organizer name
                if($propstat->prop->invite && $this->isInviteAccepted($propstat->prop->invite)) {
                    $subscribedCalendar['invite'] = array(
                        'organizer' => $propstat->prop->invite->organizer->{'common-name'}
                    );
                }
            }
        }            
        return $subscribedCalendar;
    }
    
    private function parseSubscribedCalendars($calendars)
    {
        $subscribedCalendars = array();
        try {
            $calendarsXML = new \SimpleXMLElement($calendars);
            foreach($calendarsXML->response as $calendar) {
                $subscribedCalendar = $this->parseSubscribedCalendar($calendar);
                // Concider calendar is subscribed iff it has href, invite abd rganizer details
                if(!empty($subscribedCalendar) && $subscribedCalendar['href'] && $subscribedCalendar['invite'] && $subscribedCalendar['invite']['organizer']) {
                    $subscribedCalendars[] = $subscribedCalendar;
                }
            }
        } catch(Exception $exception) {
            $subscribedCalendars = array();
        }
        return $subscribedCalendars;
    }
    
    public function listSubscribedCalendars()
    {
        $url = $this->calendarHomeSetAbsoluteURI;
        $xml = '<?xml version="1.0" encoding="utf-8"?>
                <A:propfind xmlns:A="DAV:">
                    <A:prop>
                        <A:displayname/>
                        <A:resourcetype/>
                        <C:invite xmlns:C="http://calendarserver.org/ns/"/>
                    </A:prop>
                </A:propfind>';
        $options = array(
            CURLOPT_HTTPHEADER => Array('Content-Type: application/xml; charset="utf-8"', 'Depth: 1'),
            CURLOPT_USERPWD => $this->getCalendarUserId().":".$this->getCalendarUserPassword(),
            CURLOPT_CUSTOMREQUEST => 'PROPFIND',
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_RETURNTRANSFER => 1
        );
        $result = $this->curlRequest($url, $options);
        $subscribed_calendars = array();
        if($result[1]['http_code'] == 207) {
            $subscribed_calendars = $this->parseSubscribedCalendars($result[0]);
        }
        return $subscribed_calendars;
    }
}
?>
