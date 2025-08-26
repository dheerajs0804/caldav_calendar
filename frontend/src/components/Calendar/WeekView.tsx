import React from 'react';
import dayjs from 'dayjs';
import { Calendar, Event } from '../../types/calendar';

interface WeekViewProps {
  date: Date;
  events: Event[];
  calendars: Calendar[];
  onDeleteEvent?: (event: Event) => void;
}

const WeekView: React.FC<WeekViewProps> = ({ date, events, calendars, onDeleteEvent = () => {} }) => {
  const weekStart = dayjs(date).startOf('week');
  const weekDays = Array.from({ length: 7 }, (_, i) => weekStart.add(i, 'day'));
  const hours = Array.from({ length: 24 }, (_, i) => i);
  

  
  // Helper function to parse time strings and handle timezone offsets (same as DayView)
  const parseEventTime = (timeString: string) => {
    try {
      const date = new Date(timeString);
      return dayjs(date);
    } catch (error) {
      const cleanTime = timeString.replace(/[+-]\d{2}:\d{2}$/, '');
      return dayjs(cleanTime);
    }
  };

  // Function to find overlapping events (same logic as DayView)
  const findOverlappingEvents = (event: Event, allEvents: Event[], day: dayjs.Dayjs) => {
    const eventStart = parseEventTime(event.start_time);
    const eventEnd = parseEventTime(event.end_time);
    
    if (!eventStart.isValid() || !eventEnd.isValid()) {
      return [];
    }
    
    const overlappingEvents = allEvents.filter(otherEvent => {
      if (otherEvent.id === event.id) return false;
      
      const otherStart = parseEventTime(otherEvent.start_time);
      const otherEnd = parseEventTime(otherEvent.end_time);
      
      if (!otherStart.isValid() || !otherEnd.isValid()) {
        return false;
      }
      
      // Check if events are on the same day
      if (!otherStart.isSame(day, 'day')) return false;
      
      // Events overlap if they share any time in common
      const hasOverlap = eventStart.isBefore(otherEnd) && eventEnd.isAfter(otherStart);
      
      return hasOverlap;
    });
    
    return overlappingEvents;
  };

  // Function to calculate event position (same logic as DayView)
  const getEventPosition = (event: Event, day: dayjs.Dayjs, allEvents: Event[]) => {
    const eventStart = parseEventTime(event.start_time);
    const eventEnd = parseEventTime(event.end_time);
    
    if (!eventStart.isValid() || !eventEnd.isValid()) {
      return null;
    }

    // Constants for positioning (same as DayView)
    const HOUR_HEIGHT = 64; // pixels per hour
    const MINUTE_HEIGHT = HOUR_HEIGHT / 60; // pixels per minute

    // Calculate position from start of day (00:00)
    const startMinutes = eventStart.hour() * 60 + eventStart.minute();
    const endMinutes = eventEnd.hour() * 60 + eventEnd.minute();

    const topPixels = startMinutes * MINUTE_HEIGHT;
    const heightPixels = (endMinutes - startMinutes) * MINUTE_HEIGHT;

    // Get all events for the current day
    const eventsForDay = allEvents.filter(e => {
      const eStart = parseEventTime(e.start_time);
      return eStart.isValid() && eStart.isSame(day, 'day');
    });

    // Find all overlapping events (same logic as DayView)
    const globalOverlappingGroup = new Set();
    
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

    // Sort overlapping events by start time
    const sortedOverlappingEvents = Array.from(globalOverlappingGroup).sort((a, b) => {
      const aStart = parseEventTime(a.start_time);
      const bStart = parseEventTime(b.start_time);
      return aStart.isBefore(bStart) ? -1 : 1;
    });

    // Calculate width and left position
    let eventWidth, leftOffset;
    
    if (globalOverlappingGroup.has(event)) {
      // This event is in an overlapping group
      const totalOverlapping = sortedOverlappingEvents.length;
      const eventIndex = sortedOverlappingEvents.findIndex(e => e.id === event.id);
      
      const availableWidthPercent = 100;
      const singleEventWidthPercent = availableWidthPercent / totalOverlapping;
      const leftPositionPercent = eventIndex * singleEventWidthPercent;
      
      eventWidth = `${singleEventWidthPercent}%`;
      leftOffset = `${leftPositionPercent}%`;
    } else {
      // Single event - use full width with small margins
      eventWidth = 'calc(100% - 4px)';
      leftOffset = '2px';
    }

    return {
      top: topPixels,
      height: Math.max(heightPixels, 20), // Minimum height for visibility
      width: eventWidth,
      left: leftOffset,
      startMinutes,
      endMinutes,
      eventStart,
      eventEnd
    };
  };

  const getEventStyle = (event: Event) => {
    const calendar = calendars.find(c => c.id === event.calendar_id);
    return {
      backgroundColor: calendar?.color || '#4285f4',
      borderLeft: `4px solid ${calendar?.color || '#4285f4'}`,
    };
  };

  const isToday = (day: dayjs.Dayjs) => {
    return day.isSame(dayjs(), 'day');
  };

  const isCurrentHour = (hour: number) => {
    return hour === dayjs().hour();
  };

  return (
    <div className="h-full overflow-auto bg-white">
      {/* Header with day names */}
      <div className="sticky top-0 bg-white border-b border-gray-200 z-10">
        <div className="grid grid-cols-8 gap-px bg-gray-200">
          {/* Time column header */}
          <div className="bg-white p-2 min-w-[80px]"></div>
          
          {/* Day headers */}
          {weekDays.map((day) => (
            <div
              key={day.format('YYYY-MM-DD')}
              className={`bg-white p-3 text-center ${
                isToday(day) ? 'bg-yellow-50' : ''
              }`}
            >
              <div className="text-sm font-medium text-gray-900">
                {day.format('ddd')}
              </div>
              <div className={`text-lg font-semibold ${
                isToday(day) ? 'text-yellow-600' : 'text-gray-700'
              }`}>
                {day.format('MMM-DD')}
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Time grid */}
      <div className="grid grid-cols-8 gap-px bg-gray-200">
        {/* Time labels */}
        <div className="bg-white">
          {hours.map((hour) => (
            <div
              key={hour}
              className={`h-16 border-b border-gray-100 flex items-start justify-end pr-2 pt-1 ${
                isCurrentHour(hour) ? 'bg-blue-50' : ''
              }`}
            >
              <span className="text-xs text-gray-500 font-medium">
                {hour.toString().padStart(2, '0')}:00
              </span>
            </div>
          ))}
        </div>

        {/* Day columns */}
        {weekDays.map((day) => (
          <div key={day.format('YYYY-MM-DD')} className="bg-white relative" style={{ position: 'relative' }}>
            {/* Time slots */}
            {hours.map((hour) => (
              <div
                key={`${day.format('YYYY-MM-DD')}-${hour}`}
                className={`h-16 border-b border-gray-100 relative ${
                  isToday(day) && isCurrentHour(hour) ? 'bg-yellow-50' : ''
                }`}
              />
            ))}
            
            {/* Events positioned absolutely over the time grid */}
            {(() => {
              const eventsForDay = events.filter(event => {
                const eventStart = parseEventTime(event.start_time);
                return eventStart.isValid() && eventStart.isSame(day, 'day');
              });

              return eventsForDay.map((event) => {
                const position = getEventPosition(event, day, events);
                
                if (!position) return null;
                
                const eventStart = parseEventTime(event.start_time);
                const eventEnd = parseEventTime(event.end_time);
                
                return (
                  <div
                    key={event.id}
                    className="absolute p-2 text-sm text-white rounded overflow-hidden shadow-sm week-event-item"
                    style={{
                      ...getEventStyle(event),
                      top: `${position.top}px`,
                      height: `${position.height}px`,
                      width: position.width,
                      left: position.left,
                      zIndex: 10,
                      minHeight: '20px',
                      position: 'absolute'
                    }}
                    title={`${event.title} - ${eventStart.format('HH:mm')} - ${eventEnd.format('HH:mm')}`}
                  >
                    <div className="event-header">
                      <div className="font-medium mb-1">{event.title}</div>
                      <button
                        className="delete-event-btn"
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
        ))}
      </div>

      {/* All-day events row */}
      <div className="border-b border-gray-200 bg-gray-50">
        <div className="grid grid-cols-8 gap-px bg-gray-200">
          <div className="bg-gray-50 p-2 min-w-[80px]">
            <span className="text-sm font-medium text-gray-700">all-day</span>
          </div>
          
          {weekDays.map((day) => {
            const allDayEvents = events.filter(event => {
              if (!event.all_day) return false;
              const eventDate = dayjs(event.start_time);
              return eventDate.isSame(day, 'day');
            });

            return (
              <div
                key={day.format('YYYY-MM-DD')}
                className={`bg-gray-50 p-1 min-h-[40px] ${
                  isToday(day) ? 'bg-yellow-50' : ''
                }`}
              >
                {allDayEvents.map((event, index) => (
                  <div
                    key={event.id}
                    className="mb-1 p-1 text-xs text-white rounded overflow-hidden"
                    style={getEventStyle(event)}
                  >
                    <div className="truncate">
                      {event.title}
                    </div>
                  </div>
                ))}
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
};

export default WeekView;
