# Color Inheritance Fix

## Problem Identified
The color inheritance feature was not working because:

1. **Calendar colors were not being stored persistently** - When calendars were discovered, they were assigned a hardcoded default color `#4285f4` instead of using actual colors.

2. **No color storage system** - The `calendar_states.json` only stored enabled/disabled state, not colors.

3. **Event creation couldn't access custom colors** - Events tried to get colors from discovered calendars, but those always had the default color.

## Solution Implemented

### 1. Calendar Color Storage System
**File:** `backend/index.php`

- **Created `calendar_colors.json`** - New file to store calendar colors by URL
- **Modified `getUserCalendars()`** - Now loads stored colors and uses them instead of default
- **Updated `createCalendar()`** - Now stores the selected color when creating calendars

### 2. Color Priority System
The system now uses this priority order for calendar colors:
1. **Stored color** (from `calendar_colors.json`) - Highest priority
2. **CalDAV color** (from server) - Medium priority  
3. **Default color** (`#4285f4`) - Fallback

### 3. Event Color Inheritance
**File:** `backend/index.php`

- **Enhanced `createEvent()`** - Now properly retrieves stored calendar colors
- **Added logging** - Better debugging for color inheritance process
- **Improved error handling** - Graceful fallback to default color

## Code Changes

### Calendar Discovery with Stored Colors
```php
// Load calendar colors from file
$calendarColorsFile = 'data/calendar_colors.json';
$calendarColors = [];

if (file_exists($calendarColorsFile)) {
    $calendarColors = json_decode(file_get_contents($calendarColorsFile), true) ?? [];
}

// Get stored color for this calendar URL, or use CalDAV color, or default
$storedColor = $calendarColors[$calendarUrl] ?? null;
$caldavColor = $calendar['color'] ?? null;
$finalColor = $storedColor ?? $caldavColor ?? '#4285f4';
```

### Calendar Creation with Color Storage
```php
// Store the calendar color for future reference
$calendarColorsFile = 'data/calendar_colors.json';
$calendarColors = [];

if (file_exists($calendarColorsFile)) {
    $calendarColors = json_decode(file_get_contents($calendarColorsFile), true) ?? [];
}

// Store color by calendar URL
$calendarUrl = $result['data']['url'];
$calendarColors[$calendarUrl] = $color;

// Save the updated colors
file_put_contents($calendarColorsFile, json_encode($calendarColors, JSON_PRETTY_PRINT));
```

### Event Creation with Color Inheritance
```php
// Load stored calendar colors
$calendarColorsFile = 'data/calendar_colors.json';
$calendarColors = [];

if (file_exists($calendarColorsFile)) {
    $calendarColors = json_decode(file_get_contents($calendarColorsFile), true) ?? [];
}

// Get color: stored color first, then CalDAV color, then default
$storedColor = $calendarColors[$calendarUrl] ?? null;
$caldavColor = $calendars[$calendarId - 1]['color'] ?? null;
$calendarColor = $storedColor ?? $caldavColor ?? '#4285f4';
```

## How It Works Now

1. **Calendar Creation**: When you create a calendar with a specific color (like red #f50000), it's stored in `calendar_colors.json`

2. **Calendar Discovery**: When calendars are loaded, the system checks for stored colors first, then falls back to CalDAV colors or default

3. **Event Creation**: When you create an event in a calendar, it automatically inherits the stored color of that calendar

4. **Frontend Display**: Events are displayed with their inherited colors

## Testing

A comprehensive test has been created at `test/test_color_inheritance_fix.php` that verifies:
- Calendar color storage works correctly
- Calendar discovery uses stored colors
- Event creation inherits calendar colors
- Frontend displays events with correct colors

## Expected Result

Now when you:
1. Create a calendar with red color (#f50000)
2. Create an event in that calendar
3. The event should display in red instead of blue

The fix ensures that calendar colors are properly stored and events inherit them correctly.
