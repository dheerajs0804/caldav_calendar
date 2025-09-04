<?php
namespace mithi\caldav\client; 
/**
 * CalDAV Client
 */

require_once (dirname(__FILE__).'/../../vendor/autoload.php');
require_once (dirname(__FILE__).'/../../vendor/mithi/caldav/client/MithiCalDavClient.php');
use Sabre\DAV\Property\ResourceType;
class share_calendar_caldav_client extends MithiCalDavClient 
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

    public function listCalendars()
    {
        $result = $this->prop_find($this->calendarHomeSet, array('{DAV:}displayname', '{DAV:}resourcetype'), 1);
        if(!is_array($result)) {
            $result = array();
        }
        foreach ($result as $collection => $properties) {
            if(array_key_exists('{DAV:}resourcetype', $properties)) {
                $sabresourcetype = new ResourceType($properties['{DAV:}resourcetype']);
                $resourcetype = $sabresourcetype->getValue()[0]->getValue();
                // Return collections which are of type calendar and which are not subscribed
                if(in_array('{urn:ietf:params:xml:ns:caldav}calendar', $resourcetype) &&
                        !in_array('{http://calendarserver.org/ns/}shared', $resourcetype)) {
                    $displayname = $properties['{DAV:}displayname'];
                    if(empty($displayname)) {
                        $displayname = basename($collection);
                    }
                    $calendars[$collection] = $displayname;
                }
            }
        }
        return $calendars;
    }

    private function composeShareDetailsForSharee($calendarDisplayName, $sharee, $access) {
        $xml = "    <CS:set>"."\n";
        $xml .= "       <D:href>mailto:".$sharee."</D:href>"."\n";
        $xml .= "       <CS:summary>".$calendarDisplayName."</CS:summary>"."\n";
        $xml .= "       ".$access."\n";
        $xml .= "   </CS:set>"."\n";
        return $xml;
    }

    public function shareCalendar($calendar, $calendarDisplayName, $shareWith, $canWrite)
    {
        $url = $this->calendarHomeSetAbsoluteURI.$calendar.'/';
        $access = "<CS:read />";
        if($canWrite) {
            $access = "<CS:read-write />";
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= "<CS:share xmlns:D=\"DAV:\" xmlns:CS=\"http://calendarserver.org/ns/\">"."\n";
        foreach($shareWith as $sharee) {
           $shareeDetails .= $this->composeShareDetailsForSharee($calendarDisplayName, $sharee, $access);
        }
        $xml .= $shareeDetails;
        $xml .= "</CS:share>";
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

    function unShareCalendar($calendar, $unShareWith)
    {
        $url = $this->calendarHomeSetAbsoluteURI.$calendar.'/';
        $xml = "<CS:share xmlns:D=\"DAV:\" xmlns:CS=\"http://calendarserver.org/ns/\">"."\n";
        $xml .= "<CS:remove>"."\n";
        $xml .= "<D:href>mailto:".$unShareWith."</D:href>"."\n";
        $xml .= "</CS:remove>"."\n";
        $xml .= "</CS:share>";
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
    
    private function parseSharee($userInviteDetails) {
        $user = array();
        $user['common-name'] = $userInviteDetails->{'common-name'};
        $user['access'] = 'read';
        if($userInviteDetails->access->{'read-write'}) {
            $user['access'] = 'read-write';
        }
        if($userInviteDetails->{'invite-noresponse'}) {
            $user['status'] = 'invite-noresponse';
        } else if($userInviteDetails->{'invite-accepted'}) {
            $user['status'] = 'invite-accepted';
        } else if($userInviteDetails->{'invite-declined'}) {
            $user['status'] = 'invite-declined';
        } else if($userInviteDetails->{'invite-invalid'}) {
            $user['status'] = 'invite-invalid';
        } else {
            $user['status'] = 'unknown';
        }
        
        return $user;
    }
    
    private function parseInvite($invite) {
        $users = array();
        foreach($invite->user as $userInviteDetails) {
            $user = $this->parseSharee($userInviteDetails);
            $users[] = $user;
        }
        return $users;
    }
    
    private function parseSharedCalendar($calendar)
    {
        $sharedCalendar = array();
        foreach($calendar->propstat as $propstat) {
            $status = $propstat->status;
            list($httpVersion, $statusCode, $message) = explode(' ', (string)$status[0],3);
            if($statusCode == 200) {
                // Get displayname and href from propstat
                if($propstat->prop->resourcetype->calendar && $propstat->prop->resourcetype->{'shared-owner'}) {
                    $sharedCalendar['href'] = $calendar->href;
                    $sharedCalendar['displayname'] = basename($calendar->href);
                    if($propstat->prop->displayname) {
                        $sharedCalendar['displayname'] = $propstat->prop->displayname;
                    }
                }
                if($propstat->prop->invite) {
                    $users = $this->parseInvite($propstat->prop->invite);
                    $sharedCalendar['users'] = $users;
                }
            }
        }
        return $sharedCalendar;
    }
    
    private function parseSharedCalendars($calendars)
    {
        $sharedCalendars = array();
        try {
            $calendarsXML = new \SimpleXMLElement($calendars);
            foreach($calendarsXML->response as $calendar) {
                $sharedCalendar = $this->parseSharedCalendar($calendar);
                // Concider calendar is shared iff it has href, invite abd sharee details
                if(!empty($sharedCalendar) && $sharedCalendar['href'] && $sharedCalendar['users'] && is_array($sharedCalendar['users']) && !empty($sharedCalendar['users'])) {
                    $sharedCalendars[] = $sharedCalendar;
                }
            }
        } catch(Exception $exception) {
            $sharedCalendars = array();
        }
        return $sharedCalendars;
    }

    public function listSharedCalendars()
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
        $sharedCalendars = array();
        if($result[1]['http_code'] == 207) {
            $sharedCalendars = $this->parseSharedCalendars($result[0]);
        }
        return $sharedCalendars;
    }
}
?>
