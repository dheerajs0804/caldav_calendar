<?php

namespace CalDev\Calendar\CalDAV;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Sabre\VObject\Reader;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;

class CalDAVClient
{
    private Client $httpClient;
    private string $baseUrl;
    private array $credentials;
    private array $options;
    
    public function __construct(string $baseUrl, array $credentials = [], array $options = [])
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->credentials = $credentials;
        $this->options = array_merge([
            'timeout' => 30,
            'verify' => true,
            'headers' => [
                'User-Agent' => 'CalDev Calendar/1.0',
                'Content-Type' => 'text/calendar; charset=utf-8'
            ]
        ], $options);
        
        $this->httpClient = new Client($this->options);
    }
    
    /**
     * Discover available calendars on the server
     */
    public function discoverCalendars(): array
    {
        try {
            $response = $this->httpClient->request('PROPFIND', $this->baseUrl, [
                'headers' => [
                    'Depth' => '1',
                    'Content-Type' => 'application/xml; charset=utf-8'
                ],
                'body' => $this->buildCalendarQuery()
            ]);
            
            return $this->parseCalendarDiscovery($response->getBody()->getContents());
        } catch (RequestException $e) {
            throw new CalDAVException('Failed to discover calendars: ' . $e->getMessage());
        }
    }
    
    /**
     * Get calendar events for a specific time range
     */
    public function getEvents(string $calendarUrl, string $startDate, string $endDate): array
    {
        try {
            $response = $this->httpClient->request('REPORT', $calendarUrl, [
                'headers' => [
                    'Content-Type' => 'application/xml; charset=utf-8',
                    'Depth' => '1'
                ],
                'body' => $this->buildEventQuery($startDate, $endDate)
            ]);
            
            return $this->parseEventResponse($response->getBody()->getContents());
        } catch (RequestException $e) {
            throw new CalDAVException('Failed to get events: ' . $e->getMessage());
        }
    }
    
    /**
     * Create or update an event
     */
    public function putEvent(string $calendarUrl, string $eventUid, VCalendar $calendar): bool
    {
        try {
            $eventUrl = $calendarUrl . '/' . $eventUid . '.ics';
            
            $response = $this->httpClient->request('PUT', $eventUrl, [
                'body' => $calendar->serialize(),
                'headers' => [
                    'If-None-Match' => '*'
                ]
            ]);
            
            return $response->getStatusCode() === 201 || $response->getStatusCode() === 204;
        } catch (RequestException $e) {
            throw new CalDAVException('Failed to put event: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete an event
     */
    public function deleteEvent(string $eventUrl): bool
    {
        try {
            $response = $this->httpClient->request('DELETE', $eventUrl);
            return $response->getStatusCode() === 204;
        } catch (RequestException $e) {
            throw new CalDAVException('Failed to delete event: ' . $e->getMessage());
        }
    }
    
    /**
     * Get sync token for incremental sync
     */
    public function getSyncToken(string $calendarUrl): ?string
    {
        try {
            $response = $this->httpClient->request('PROPFIND', $calendarUrl, [
                'headers' => [
                    'Depth' => '0',
                    'Content-Type' => 'application/xml; charset=utf-8'
                ],
                'body' => $this->buildSyncTokenQuery()
            ]);
            
            return $this->parseSyncToken($response->getBody()->getContents());
        } catch (RequestException $e) {
            throw new CalDAVException('Failed to get sync token: ' . $e->getMessage());
        }
    }
    
    /**
     * Incremental sync using sync token
     */
    public function syncCalendar(string $calendarUrl, string $syncToken): array
    {
        try {
            $response = $this->httpClient->request('REPORT', $calendarUrl, [
                'headers' => [
                    'Content-Type' => 'application/xml; charset=utf-8',
                    'Depth' => '1'
                ],
                'body' => $this->buildSyncQuery($syncToken)
            ]);
            
            return $this->parseSyncResponse($response->getBody()->getContents());
        } catch (RequestException $e) {
            throw new CalDAVException('Failed to sync calendar: ' . $e->getMessage());
        }
    }
    
    private function buildCalendarQuery(): string
    {
        return '<?xml version="1.0" encoding="utf-8" ?>
            <D:propfind xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
                <D:prop>
                    <D:resourcetype/>
                    <D:displayname/>
                    <C:calendar-color/>
                    <C:supported-calendar-component-set/>
                </D:prop>
            </D:propfind>';
    }
    
    private function buildEventQuery(string $startDate, string $endDate): string
    {
        return '<?xml version="1.0" encoding="utf-8" ?>
            <C:calendar-query xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
                <D:prop>
                    <D:getetag/>
                    <C:calendar-data/>
                </D:prop>
                <C:filter>
                    <C:comp-filter name="VCALENDAR">
                        <C:comp-filter name="VEVENT">
                            <C:time-range start="' . $startDate . '" end="' . $endDate . '"/>
                        </C:comp-filter>
                    </C:comp-filter>
                </C:filter>
            </C:calendar-query>';
    }
    
    private function buildSyncTokenQuery(): string
    {
        return '<?xml version="1.0" encoding="utf-8" ?>
            <D:propfind xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
                <D:prop>
                    <C:sync-token/>
                </D:prop>
            </D:propfind>';
    }
    
    private function buildSyncQuery(string $syncToken): string
    {
        return '<?xml version="1.0" encoding="utf-8" ?>
            <C:sync-collection xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
                <D:prop>
                    <D:getetag/>
                    <C:calendar-data/>
                </D:prop>
                <C:sync-token>' . htmlspecialchars($syncToken) . '</C:sync-token>
            </C:sync-collection>';
    }
    
    private function parseCalendarDiscovery(string $xml): array
    {
        $calendars = [];
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        
        $responses = $dom->getElementsByTagName('D:response');
        
        foreach ($responses as $response) {
            $href = $response->getElementsByTagName('D:href')->item(0)?->textContent;
            $resourcetype = $response->getElementsByTagName('D:resourcetype')->item(0);
            
            if ($href && $resourcetype && $resourcetype->getElementsByTagName('C:calendar')->length > 0) {
                $displayName = $response->getElementsByTagName('D:displayname')->item(0)?->textContent ?? basename($href);
                $color = $response->getElementsByTagName('C:calendar-color')->item(0)?->textContent ?? '#4285f4';
                
                $calendars[] = [
                    'url' => $href,
                    'name' => $displayName,
                    'color' => $color
                ];
            }
        }
        
        return $calendars;
    }
    
    private function parseEventResponse(string $xml): array
    {
        $events = [];
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        
        $responses = $dom->getElementsByTagName('D:response');
        
        foreach ($responses as $response) {
            $href = $response->getElementsByTagName('D:href')->item(0)?->textContent;
            $etag = $response->getElementsByTagName('D:getetag')->item(0)?->textContent;
            $calendarData = $response->getElementsByTagName('C:calendar-data')->item(0)?->textContent;
            
            if ($href && $etag && $calendarData) {
                try {
                    $vcalendar = Reader::read($calendarData);
                    $vevent = $vcalendar->VEVENT;
                    
                    if ($vevent) {
                        $events[] = [
                            'url' => $href,
                            'etag' => trim($etag, '"'),
                            'uid' => $vevent->UID->getValue(),
                            'title' => $vevent->SUMMARY->getValue() ?? '',
                            'description' => $vevent->DESCRIPTION->getValue() ?? '',
                            'start' => $vevent->DTSTART->getValue(),
                            'end' => $vevent->DTEND->getValue(),
                            'all_day' => !$vevent->DTSTART->hasTime(),
                            'location' => $vevent->LOCATION->getValue() ?? '',
                            'recurrence_rule' => $vevent->RRULE->getValue() ?? null
                        ];
                    }
                } catch (\Exception $e) {
                    // Skip malformed events
                    continue;
                }
            }
        }
        
        return $events;
    }
    
    private function parseSyncToken(string $xml): ?string
    {
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        
        $syncToken = $dom->getElementsByTagName('C:sync-token')->item(0);
        return $syncToken ? $syncToken->textContent : null;
    }
    
    private function parseSyncResponse(string $xml): array
    {
        // Similar to parseEventResponse but for sync operations
        return $this->parseEventResponse($xml);
    }
}

class CalDAVException extends \Exception {}
