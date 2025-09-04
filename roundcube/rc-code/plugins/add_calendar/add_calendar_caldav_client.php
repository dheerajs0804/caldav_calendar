<?php
namespace mithi\caldav\client; 
/**
 * CalDAV Client
 */

require_once (dirname(__FILE__).'/../../vendor/autoload.php');
require_once (dirname(__FILE__).'/../../vendor/mithi/caldav/client/MithiCalDavClient.php');

use Sabre\DAV\Property\ResourceType;

class add_calendar_caldav_client extends MithiCalDavClient
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

    private function generateGUID() {
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = ""
            .substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12)
            ."";
        return $uuid;
    }

    public function mkCalendar($calendar)
    {
        $calendarUUID = $this->generateGUID();
        $url = $this->calendarHomeSetAbsoluteURI.$calendarUUID.'/';
        $xml = '<?xml version="1.0" encoding="utf-8"?>
        <C:mkcalendar xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
        <D:set>
            <D:prop>
                <D:displayname>'.$calendar.'</D:displayname>
            </D:prop>
        </D:set>
        </C:mkcalendar>';
        $options = array(
            CURLOPT_HTTPHEADER => Array('Content-Type: application/xml; charset="utf-8"'),
            CURLOPT_USERPWD => $this->getCalendarUserId().":".$this->getCalendarUserPassword(),
            CURLOPT_CUSTOMREQUEST => 'MKCALENDAR',
            CURLOPT_POSTFIELDS => $xml,
            // Automatically follow redirects
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_RETURNTRANSFER => 1
        );
        $result = $this->curlRequest($url, $options);
        return $result;
    }

    public function deleteCalendar($calendar)
    {
        $url = $this->calendarHomeSetAbsoluteURI.$calendar.'/';
        $options = array(
            CURLOPT_USERPWD => $this->getCalendarUserId().":".$this->getCalendarUserPassword(),
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => 1
        );
        $result = $this->curlRequest($url, $options);
        return $result;
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
}
?>
