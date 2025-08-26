<?php

namespace CalDev\Calendar\API;

use CalDev\Calendar\Models\Calendar;
use CalDev\Calendar\Models\Event;
use CalDev\Calendar\CalDAV\CalDAVClient;
use CalDev\Calendar\CalDAV\CalDAVException;

class CalendarController
{
    private Calendar $calendarModel;
    private Event $eventModel;
    
    public function __construct(Calendar $calendarModel, Event $eventModel)
    {
        $this->calendarModel = $calendarModel;
        $this->eventModel = $eventModel;
    }
    
    /**
     * Get all calendars for a user
     */
    public function index(int $userId): array
    {
        try {
            $calendars = $this->calendarModel->findByUserId($userId);
            
            return [
                'success' => true,
                'data' => $calendars
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch calendars: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create a new calendar
     */
    public function store(array $data, int $userId): array
    {
        try {
            // Validate required fields
            if (empty($data['name']) || empty($data['url'])) {
                return [
                    'success' => false,
                    'error' => 'Name and URL are required'
                ];
            }
            
            // Check if calendar already exists
            $existing = $this->calendarModel->findByUrl($data['url'], $userId);
            if ($existing) {
                return [
                    'success' => false,
                    'error' => 'Calendar already exists'
                ];
            }
            
            // Test CalDAV connection
            try {
                $caldavClient = new CalDAVClient($data['url']);
                $discoveredCalendars = $caldavClient->discoverCalendars();
                
                if (empty($discoveredCalendars)) {
                    return [
                        'success' => false,
                        'error' => 'No calendars found at the specified URL'
                    ];
                }
            } catch (CalDAVException $e) {
                return [
                    'success' => false,
                    'error' => 'Failed to connect to CalDAV server: ' . $e->getMessage()
                ];
            }
            
            // Create calendar
            $calendarId = $this->calendarModel->create([
                'name' => $data['name'],
                'color' => $data['color'] ?? '#4285f4',
                'url' => $data['url'],
                'read_only' => $data['read_only'] ?? false,
                'user_id' => $userId
            ]);
            
            $calendar = $this->calendarModel->findById($calendarId);
            
            return [
                'success' => true,
                'data' => $calendar,
                'message' => 'Calendar created successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to create calendar: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get a specific calendar
     */
    public function show(int $id, int $userId): array
    {
        try {
            $calendar = $this->calendarModel->findById($id);
            
            if (!$calendar) {
                return [
                    'success' => false,
                    'error' => 'Calendar not found'
                ];
            }
            
            // Check if user owns this calendar
            if ($calendar['user_id'] != $userId) {
                return [
                    'success' => false,
                    'error' => 'Unauthorized access'
                ];
            }
            
            return [
                'success' => true,
                'data' => $calendar
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch calendar: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Update a calendar
     */
    public function update(int $id, array $data, int $userId): array
    {
        try {
            $calendar = $this->calendarModel->findById($id);
            
            if (!$calendar) {
                return [
                    'success' => false,
                    'error' => 'Calendar not found'
                ];
            }
            
            // Check if user owns this calendar
            if ($calendar['user_id'] != $userId) {
                return [
                    'success' => false,
                    'error' => 'Unauthorized access'
                ];
            }
            
            // Update calendar
            $success = $this->calendarModel->update($id, $data);
            
            if ($success) {
                $updatedCalendar = $this->calendarModel->findById($id);
                return [
                    'success' => true,
                    'data' => $updatedCalendar,
                    'message' => 'Calendar updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to update calendar'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to update calendar: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete a calendar
     */
    public function destroy(int $id, int $userId): array
    {
        try {
            $calendar = $this->calendarModel->findById($id);
            
            if (!$calendar) {
                return [
                    'success' => false,
                    'error' => 'Calendar not found'
                ];
            }
            
            // Check if user owns this calendar
            if ($calendar['user_id'] != $userId) {
                return [
                    'success' => false,
                    'error' => 'Unauthorized access'
                ];
            }
            
            // Delete calendar (this will cascade delete events)
            $success = $this->calendarModel->delete($id);
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Calendar deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to delete calendar'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to delete calendar: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Sync calendar with CalDAV server
     */
    public function sync(int $id, int $userId): array
    {
        try {
            $calendar = $this->calendarModel->findById($id);
            
            if (!$calendar) {
                return [
                    'success' => false,
                    'error' => 'Calendar not found'
                ];
            }
            
            // Check if user owns this calendar
            if ($calendar['user_id'] != $userId) {
                return [
                    'success' => false,
                    'error' => 'Unauthorized access'
                ];
            }
            
            // Initialize CalDAV client
            $caldavClient = new CalDAVClient($calendar['url']);
            
            // Get current sync token
            $currentSyncToken = $calendar['sync_token'];
            
            if ($currentSyncToken) {
                // Incremental sync
                $changes = $caldavClient->syncCalendar($calendar['url'], $currentSyncToken);
            } else {
                // Full sync - get events for the next year
                $startDate = date('Y-m-d\TH:i:s\Z');
                $endDate = date('Y-m-d\TH:i:s\Z', strtotime('+1 year'));
                $changes = $caldavClient->getEvents($calendar['url'], $startDate, $endDate);
            }
            
            // Get new sync token
            $newSyncToken = $caldavClient->getSyncToken($calendar['url']);
            
            // Update local sync token
            if ($newSyncToken) {
                $this->calendarModel->updateSyncToken($id, $newSyncToken);
            }
            
            // Process changes and update local events
            $processedEvents = $this->processSyncChanges($changes, $id);
            
            return [
                'success' => true,
                'data' => [
                    'processed_events' => $processedEvents,
                    'new_sync_token' => $newSyncToken
                ],
                'message' => 'Calendar synced successfully'
            ];
        } catch (CalDAVException $e) {
            return [
                'success' => false,
                'error' => 'CalDAV sync failed: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to sync calendar: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Process sync changes from CalDAV server
     */
    private function processSyncChanges(array $changes, int $calendarId): array
    {
        $processed = ['created' => 0, 'updated' => 0, 'deleted' => 0];
        
        foreach ($changes as $change) {
            $existingEvent = $this->eventModel->findByUid($change['uid'], $calendarId);
            
            if ($existingEvent) {
                // Update existing event
                $this->eventModel->update($existingEvent['id'], [
                    'title' => $change['title'],
                    'description' => $change['description'],
                    'start_time' => $change['start'],
                    'end_time' => $change['end'],
                    'all_day' => $change['all_day'],
                    'location' => $change['location'],
                    'recurrence_rule' => $change['recurrence_rule'],
                    'etag' => $change['etag']
                ]);
                $processed['updated']++;
            } else {
                // Create new event
                $this->eventModel->create([
                    'calendar_id' => $calendarId,
                    'title' => $change['title'],
                    'description' => $change['description'],
                    'start_time' => $change['start'],
                    'end_time' => $change['end'],
                    'all_day' => $change['all_day'],
                    'location' => $change['location'],
                    'recurrence_rule' => $change['recurrence_rule'],
                    'etag' => $change['etag'],
                    'uid' => $change['uid']
                ]);
                $processed['created']++;
            }
        }
        
        return $processed;
    }
}
