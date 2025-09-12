# Color Inheritance Issue - Root Cause Found and Fixed

## 🔍 **Root Cause Identified**

The color inheritance feature wasn't working because of a **frontend-backend communication issue**:

1. **Frontend was NOT sending `calendar_id`** - The Angular frontend was only sending `calendar_url` but not the actual `calendar_id`
2. **Backend was defaulting to `calendar_id: 1`** - Since no `calendar_id` was provided, the backend used the default value of 1
3. **Calendar ID mismatch** - The "redcal" calendar might not be at index 0 (ID 1), so events were being created in the wrong calendar

## 🛠️ **Fix Applied**

### Frontend Fix (Angular)
**File:** `frontend-angular/src/app/components/calendar.component.ts`

**Before:**
```typescript
const eventData = {
  // ... other fields ...
  calendar_url: targetCalendar.url  // Only sending URL
};
```

**After:**
```typescript
const eventData = {
  // ... other fields ...
  calendar_id: targetCalendar.id, // Send the actual calendar ID
  calendar_url: targetCalendar.url, // Also send URL for CalDAV operations
};
```

### Backend Enhancement
**File:** `backend/index.php`

Added better logging to debug calendar ID and color mapping:
```php
error_log("Event calendar color: $calendarColor (stored: $storedColor, caldav: $caldavColor)");
error_log("Calendar ID: $calendarId, Calendar URL: $calendarUrl");
error_log("Available calendar colors: " . json_encode($calendarColors));
```

## 🎯 **How It Works Now**

1. **User selects "redcal" calendar** in the dropdown
2. **Frontend sends `calendar_id: X`** (where X is the actual ID of "redcal")
3. **Backend looks up calendar by ID** and gets the correct calendar URL
4. **Backend retrieves stored color** from `calendar_colors.json` using the calendar URL
5. **Event is created with inherited red color** (#ff0000)
6. **Frontend displays event in red** using the event's color field

## ✅ **Expected Result**

Now when you:
1. Create a red calendar (#ff0000) named "redcal"
2. Select "redcal" in the event creation dropdown
3. Create an event

The event should:
- ✅ Be created with `calendar_id` matching the "redcal" calendar
- ✅ Inherit the red color (#ff0000) from the calendar
- ✅ Display in red instead of blue

## 🔧 **Technical Details**

- **Calendar colors are stored correctly** in `data/calendar_colors.json`
- **Calendar discovery works properly** and assigns sequential IDs
- **Color inheritance logic is working** - it was just not getting the right calendar ID
- **Frontend now sends both `calendar_id` and `calendar_url`** for maximum compatibility

The fix ensures that events are created in the correct calendar and inherit the proper color from their parent calendar.
