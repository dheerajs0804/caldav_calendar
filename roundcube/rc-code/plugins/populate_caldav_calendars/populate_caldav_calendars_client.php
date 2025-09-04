<?php

namespace mithi\caldav\client;
/**
 * CalDAV Client
 */

require_once (dirname(__FILE__).'/../../vendor/autoload.php');
require_once (dirname(__FILE__).'/../../vendor/mithi/caldav/client/MithiCalDavClient.php');

use Sabre\DAV\Property\ResourceType;

class populate_caldav_calendars_client extends MithiCalDavClient {
    private $calendarHomeSet;
    private $calendarHomeSetAbsoluteURI;
    private $calendarProperties;

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
        $this->initCalendarPropertyList();
    }

    private function initCalendarPropertyList() {
        $this->calendarProperties = array(
            "DAV:" => array("displayname", "resourcetype"),
            "http://apple.com/ns/ical/" => array("calendar-color")
        );
    }

    private function composePropfindPayload() {
       $propfindPayload = "";
       $propfindPayloadNamespaceList = "";
       $propfindPayloadPropList = "";
       $namespaceList = array_keys($this->calendarProperties);
       $namespaceAbbrMap = array();
       $abbr = "A";

       foreach ($namespaceList as $namespace) {
           $namespaceAbbrMap[$abbr++] = $namespace;
       }

       foreach ($namespaceAbbrMap as $abbr => $namespace) {
           $propfindPayloadNamespaceList .= "xmlns:" . "$abbr=" . "\"$namespace\" ";
       }

       $propfindPayloadNamespaceList = trim($propfindPayloadNamespaceList);

       $propfindPayload .= "<?xml version=\"1.0\" encoding=\"utf-8\"?>" . "\n";
       $propfindPayload .= "<A:propfind " . $propfindPayloadNamespaceList . ">" ."\n";
       $propfindPayload .= "<A:prop>" . "\n";

       foreach ($namespaceAbbrMap as $abbr => $namespace) {
           $napespaceProperties = $this->calendarProperties[$namespace];
           foreach ($napespaceProperties as $property) {
               $propfindPayloadPropList .= "<" . "$abbr:" . "$property" . "/>" . "\n";
           }
       }

       $propfindPayload .= "$propfindPayloadPropList";
       $propfindPayload .= "</A:prop>" . "\n";
       $propfindPayload .= "</A:propfind>" . "\n";
       
       return $$propfindPayload;
    }


    private function parseCalendarColor($calendarXML) {
        $calendarColor = array();

        foreach($calendarXML->propstat as $propstat) {
            $status = $propstat->status;
            list($httpVersion, $statusCode, $message) = explode(' ', (string)$status[0],3);
            if($statusCode == 200) {
                if($propstat->prop->{'calendar-color'}) {
                    $calendarColor['code'] = strval($propstat->prop->{'calendar-color'});
                }

		if($propstat->prop->{'calendar-color'}[0] && $propstat->prop->{'calendar-color'}[0]->attributes()) {
                    foreach($propstat->prop->{'calendar-color'}[0]->attributes() as $key=>$value) {
                        if($key == 'symbolic-color') {
                            $calendarColor['name'] = strval($value);
                        }
                   }
                }
            }
        }

        return $calendarColor;
    }

    private function parseCalendarName($calendarXML) {
        $calendarName = "";

        foreach($calendarXML->propstat as $propstat) {
            $status = $propstat->status;
            list($httpVersion, $statusCode, $message) = explode(' ', (string)$status[0],3);
            if($statusCode == 200) {
                if($propstat->prop->displayname) {
                    $calendarName = strval($propstat->prop->displayname);
                }
            }
        }

        return $calendarName;
    }

    private function parseCalendarHref($calendarXML) {
        $calendarHref = array();
        // Get href from propstat
        if($calendarXML->href) {
            $calendarHref['href'] = strval($calendarXML->href);
            $calendarHref['absolute_href'] = $this->getCalendarBaseURI().strval($calendarXML->href);
        }
        return $calendarHref;
    }

    // FIXME Nedd to find better approach to parse calendar properties generic way
    private function parseCalendarProperties($calendarXML) {
        $calendar = array();
        $calendar['location'] = $this->parseCalendarHref($calendarXML);
        $calendar['name'] = $this->parseCalendarName($calendarXML);
        $calendar['color'] = $this->parseCalendarColor($calendarXML);
        return $calendar;
    }

    private function parseCalendar($calendarResponseXML)
    {
        $calendar = array();
        foreach($calendarResponseXML->propstat as $propstat) {
            $status = $propstat->status;
            list($httpVersion, $statusCode, $message) = explode(' ', (string)$status[0],3);
            if($statusCode == 200) {
                // Get requested proprerties from propstat iff resource type is calendar
                if($propstat->prop->resourcetype->calendar) {
                    $parsedCalendar = $this->parseCalendarProperties($calendarResponseXML);
                    // Concider calendar iff it has href
                    if(!empty($parsedCalendar) && $parsedCalendar['location']) {
                        $calendar = $parsedCalendar;
                    }
                }
            }
        }
        return $calendar;
    }

    private function parseCalendars($calendarsResponseXML)
    {
        $calendars = array();
        try {
            $calendarsXML = new \SimpleXMLElement($calendarsResponseXML);
            foreach($calendarsXML->response as $calendarResponseXML) {
                $calendar = $this->parseCalendar($calendarResponseXML);
                // Concider calendar iff it is not empty
                if(!empty($calendar)) {
                    $calendars[] = $calendar;
                }
            }
        } catch(Exception $exception) {
            $calendars = array();
        }
        return $calendars;
    }


    public function getServerCalendars()
    {
        $url = $this->calendarHomeSetAbsoluteURI;
        $xml = $this->composePropfindPayload();
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
        $calendars = array();
        if($result[1]['http_code'] == 207) {
            $calendars = $this->parseCalendars($result[0]);
        }
        return $calendars;
    }
}

?>
