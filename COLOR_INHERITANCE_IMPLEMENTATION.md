# Color Inheritance Feature Implementation

## Overview
This document describes the implementation of the color inheritance feature, where events automatically inherit the color of their parent calendar when created.

## Changes Made

### 1. Database Schema Updates
**File:** `backend/database/migrations.sql`
- Added `color VARCHAR(7) DEFAULT '#4285f4'` field to the `events` table
- This allows events to store their own color value

### 2. Backend Model Updates
**File:** `backend/src/Models/Event.php`
- Updated `create()` method to include color field in SQL INSERT
- Updated `update()` method to include color field in SQL UPDATE
- Both methods now accept and store the color parameter

### 3. Event Creation Logic
**File:** `backend/index.php`
- Modified `createEvent()` function to automatically inherit calendar color
- Added logic to retrieve calendar color from discovered calendars
- Events now include the inherited color when created

### 4. Frontend Updates

#### Angular Frontend
**Files Updated:**
- `frontend-angular/src/app/components/day-view/day-view.component.ts`
- `frontend-angular/src/app/components/week-view/week-view.component.ts`
- `frontend-angular/src/app/components/month-view/month-view.component.ts`
- `frontend-angular/src/app/components/calendar.component.html`

**Changes:**
- Updated `getEventStyle()` methods to use `event.color` instead of looking up calendar color
- Updated agenda view to use event color for calendar name display
- CalendarEvent interface already had color field

#### React Frontend
**File:** `frontend/src/types/calendar.ts`
- Added `color?: string` field to the Event interface

## How It Works

### Event Creation Process
1. User creates an event and selects a calendar
2. Backend retrieves the calendar's color from discovered calendars
3. Event is created with the inherited calendar color
4. Frontend displays the event using its own color

### Color Inheritance Logic
```php
// Get calendar color for the event
$calendarColor = '#4285f4'; // Default color
$calendarId = $input['calendar_id'] ?? 1;

// Try to get calendar color from discovered calendars
try {
    $caldavClient = getCalDAVClient();
    if ($caldavClient) {
        $calendars = $caldavClient->discoverCalendars();
        if ($calendars && is_array($calendars) && isset($calendars[$calendarId - 1])) {
            $calendarColor = $calendars[$calendarId - 1]['color'] ?? '#4285f4';
        }
    }
} catch (Exception $e) {
    error_log("Could not get calendar color: " . $e->getMessage());
}

// Create event with inherited color
$event = [
    // ... other fields ...
    'color' => $calendarColor,
    // ... other fields ...
];
```

### Frontend Display Logic
```typescript
// Angular components
getEventStyle(event: CalendarEvent): any {
    return {
        backgroundColor: event.color || '#4285f4',
        borderLeft: `4px solid ${event.color || '#4285f4'}`,
    };
}
```

## Benefits

1. **Consistency**: Events automatically match their calendar's color scheme
2. **Performance**: Frontend no longer needs to look up calendar colors for each event
3. **Flexibility**: Events can still have their own colors if needed (for future features)
4. **User Experience**: Visual consistency between calendars and their events

## Testing

A comprehensive test file has been created at `test/test_color_inheritance.php` that verifies:
- Event model accepts color field
- Calendar color retrieval logic works correctly
- Event creation inherits calendar colors
- Frontend styling uses event colors
- Multiple events with different colors work properly

## Migration Notes

- Existing events will use the default color `#4285f4` until they are updated
- New events will automatically inherit their calendar's color
- No breaking changes to existing functionality
- Backward compatible with existing event data

## Future Enhancements

- Allow users to override event colors individually
- Add color picker for calendar creation/editing
- Support for color themes and customization
- Color-based event filtering and grouping
