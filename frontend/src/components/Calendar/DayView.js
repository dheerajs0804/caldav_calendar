import React from 'react';
import dayjs from 'dayjs';

const DayView = ({ date, events, calendars, onDeleteEvent = () => {} }) => {
  const hours = Array.from({ length: 24 }, (_, i) => i);

  // Helper function to parse time strings and handle timezone offsets
  const parseEventTime = (timeString) => {
    try {
      const date = new Date(timeString);
      return dayjs(date);
    } catch (error) {
      const cleanTime = timeString.replace(/[+-]\d{2}:\d{2}$/, '');
      return dayjs(cleanTime);
    }
  };

  // Function to find overlapping events at a specific time point
  const findOverlappingEventsAtTime = (event, allEvents, timePoint) => {
    const eventStart = parseEventTime(event.start_time);
    const eventEnd = parseEventTime(event.end_time);
    
    if (!eventStart.isValid() || !eventEnd.isValid()) {
      return [];
    }
    
    // Get all events for the current day first
    const eventsForDay = allEvents.filter(e => {
      const eStart = parseEventTime(e.start_time);
      return eStart.isValid() && eStart.isSame(dayjs(date), 'day');
    });
    
    // Find events that are active at the given time point
    const activeEvents = eventsForDay.filter(otherEvent => {
      const otherStart = parseEventTime(otherEvent.start_time);
      const otherEnd = parseEventTime(otherEvent.end_time);
      
      if (!otherStart.isValid() || !otherEnd.isValid()) {
        return false;
      }
      
      // Event is active if the time point is within its duration
      const isActive = (timePoint.isSame(otherStart) || timePoint.isAfter(otherStart)) && timePoint.isBefore(otherEnd);
      
      return isActive;
    });
    
    return activeEvents;
  };

  const getEventStyle = (event) => {
    const calendar = calendars.find(c => c.id === event.calendar_id);
    return {
      backgroundColor: calendar?.color || '#4285f4',
      borderLeft: `4px solid ${calendar?.color || '#4285f4'}`,
    };
  };

  const isToday = (day) => {
    return dayjs(day).isSame(dayjs(), 'day');
  };

  const isCurrentHour = (hour) => {
    return hour === dayjs().hour();
  };

  return (
    <div className="h-full overflow-auto bg-white">
      {/* Header */}
      <div className="sticky top-0 bg-white border-b border-gray-200 z-10 p-4">
        <h2 className="text-xl font-semibold text-gray-900">
          {dayjs(date).format('dddd, MMMM D, YYYY')}
        </h2>
      </div>

      {/* Time grid */}
      <div className="relative" style={{ height: '1536px' }}> {/* 24 hours * 64px per hour */}
        {/* Time labels and grid lines */}
        {hours.map((hour) => (
          <div
            key={hour}
            className={`absolute left-2 w-16 border-b border-gray-100 flex items-start justify-end pr-2 pt-1 ${
              isToday(date) && isCurrentHour(hour) ? 'bg-blue-50' : ''
            }`}
            style={{ 
              top: `${hour * 64}px`,
              height: '64px'
            }}
          >
            <span className="text-xs text-gray-500 font-medium">
              {hour.toString().padStart(2, '0')}:00
            </span>
          </div>
        ))}
        
        {/* Events - positioned absolutely with proper horizontal sharing */}
        {(() => {
          // First, find all events for the day
          const eventsForDay = events.filter(event => dayjs(event.start_time).isSame(dayjs(date), 'day'));
          

          
          // Create a single global overlapping group for all events that overlap with each other
          const globalOverlappingGroup = new Set();
          
          // For each event, find all events that overlap with it and add them to the global group
          eventsForDay.forEach(event => {
            const eventStart = parseEventTime(event.start_time);
            const eventEnd = parseEventTime(event.end_time);
            
            if (!eventStart.isValid() || !eventEnd.isValid()) return;
            
            // Find all overlapping events
            const overlappingEvents = eventsForDay.filter(otherEvent => {
              if (otherEvent.id === event.id) return false;
              
              const otherStart = parseEventTime(otherEvent.start_time);
              const otherEnd = parseEventTime(otherEvent.end_time);
              
              if (!otherStart.isValid() || !otherEnd.isValid()) return false;
              
              // Check if events overlap at any point
              const hasOverlap = eventStart.isBefore(otherEnd) && eventEnd.isAfter(otherStart);
              return hasOverlap;
            });
            
            // If this event has any overlapping events, add it and all overlapping events to the global group
            if (overlappingEvents.length > 0) {
              globalOverlappingGroup.add(event);
              overlappingEvents.forEach(overlappingEvent => {
                globalOverlappingGroup.add(overlappingEvent);
              });
            }
          });
          
          // Convert Set to Array and sort by start time
          const sortedGlobalGroup = Array.from(globalOverlappingGroup).sort((a, b) => {
            const aStart = parseEventTime(a.start_time);
            const bStart = parseEventTime(b.start_time);
            return aStart.isBefore(bStart) ? -1 : 1;
          });
          
          // Now render events with their group information
          return eventsForDay.map((event) => {
            const eventStart = parseEventTime(event.start_time);
            const eventEnd = parseEventTime(event.end_time);
            
            // Calculate base positioning
            const startMinutes = eventStart.hour() * 60 + eventStart.minute();
            const gridPixelsPerMinute = 64 / 60;
            const topPositionPixels = startMinutes * gridPixelsPerMinute;
            
            // Position events relative to the time grid container (no header offset needed)
            // The time grid container starts at the top of the scrollable area
            const adjustedTopPosition = topPositionPixels;
            

            
            // Calculate duration in minutes
            const durationMinutes = eventEnd.diff(eventStart, 'minute');
            const heightPositionPixels = durationMinutes * gridPixelsPerMinute;
            
            // Determine if this event is part of the global overlapping group
            const isInOverlappingGroup = globalOverlappingGroup.has(event);
            
            let eventIndex, totalOverlapping;
            
            if (isInOverlappingGroup) {
              // This event is part of the global overlapping group
              eventIndex = sortedGlobalGroup.findIndex(e => e.id === event.id);
              totalOverlapping = sortedGlobalGroup.length;
            } else {
              // This event is standalone
              eventIndex = 0;
              totalOverlapping = 1;
            }
            
            // Calculate width and left position for overlapping events
            let eventWidth, leftOffset;
            
            if (totalOverlapping > 1) {
              // Share horizontal space side-by-side using percentages
              const availableWidthPercent = 100; // 100% of available space
              const singleEventWidthPercent = availableWidthPercent / totalOverlapping;
              const leftPositionPercent = (eventIndex * singleEventWidthPercent);
              
              eventWidth = `${singleEventWidthPercent}%`;
              leftOffset = `${leftPositionPercent}%`;
            } else {
              // Single event - use full width
              eventWidth = '100%';
              leftOffset = '0%';
            }
            
                            return (
                  <div
                    key={event.id}
                    className="absolute p-2 text-sm text-white rounded overflow-hidden shadow-sm event-container day-event-item"
                    style={{
                      ...getEventStyle(event),
                      top: `${adjustedTopPosition}px`,
                      height: `${Math.max(heightPositionPixels, 20)}px`,
                      width: eventWidth,
                      left: `calc(80px + ${leftOffset})`, // Offset from time labels
                      zIndex: 10,
                      minHeight: '20px',
                      position: 'absolute'
                    }}
                    title={`${event.title} - ${eventStart.format('HH:mm')} - ${eventEnd.format('HH:mm')}`}
                  >
                    <div className="event-header">
                      <div className="font-medium mb-1">{event.title}</div>
                      <button
                        className="delete-event-btn-day"
                        onClick={(e) => {
                          e.stopPropagation();
                          onDeleteEvent(event);
                        }}
                        title="Delete event"
                      >
                        ×
                      </button>
                    </div>
                    <div className="text-xs opacity-90">
                      {eventStart.format('HH:mm')} - {eventEnd.format('HH:mm')}
                    </div>
                  </div>
                );
          });
        })()}
      </div>

      {/* All-day events */}
      <div className="border-b border-gray-200 bg-gray-50 p-4">
        <h3 className="text-sm font-medium text-gray-700 mb-2">All Day</h3>
        <div className="space-y-1">
          {events
            .filter(event => event.all_day && dayjs(event.start_time).isSame(dayjs(date), 'day'))
            .map((event) => (
              <div
                key={event.id}
                className="p-2 text-sm text-white rounded overflow-hidden"
                style={getEventStyle(event)}
              >
                {event.title}
              </div>
            ))}
        </div>
      </div>
    </div>
  );
};

export default DayView;