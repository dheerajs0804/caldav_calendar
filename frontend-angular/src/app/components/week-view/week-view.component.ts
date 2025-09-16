import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import * as dayjs from 'dayjs';
import { CalendarEvent, Calendar } from '../../interfaces/calendar-event.interface';
import { ColorRegistryService } from '../../services/color-registry.service';


@Component({
  selector: 'app-week-view',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './week-view.component.html',
  styleUrls: ['./week-view.component.scss']
})
export class WeekViewComponent {
  @Input() date!: dayjs.Dayjs;
  @Input() events: CalendarEvent[] = [];
  @Input() calendars: Calendar[] = [];
  @Output() deleteEvent = new EventEmitter<CalendarEvent>();
  @Output() eventClick = new EventEmitter<CalendarEvent>();

  hours = Array.from({ length: 24 }, (_, i) => i);

  constructor(private colorRegistry: ColorRegistryService) {}

  // Helper function to parse time strings and handle timezone offsets (same as DayView)
  parseEventTime(timeString: string): dayjs.Dayjs {
    try {
      const date = new Date(timeString);
      return dayjs(date);
    } catch (error) {
      const cleanTime = timeString.replace(/[+-]\d{2}:\d{2}$/, '');
      return dayjs(cleanTime);
    }
  }

  // Function to find overlapping events (same logic as DayView)
  findOverlappingEvents(event: CalendarEvent, allEvents: CalendarEvent[], day: dayjs.Dayjs): CalendarEvent[] {
    const eventStart = this.parseEventTime(event.start_time);
    const eventEnd = this.parseEventTime(event.end_time);
    
    if (!eventStart.isValid() || !eventEnd.isValid()) {
      return [];
    }
    
    const overlappingEvents = allEvents.filter(otherEvent => {
      if (otherEvent.id === event.id) return false;
      
      const otherStart = this.parseEventTime(otherEvent.start_time);
      const otherEnd = this.parseEventTime(otherEvent.end_time);
      
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
  }

  // Function to calculate event position (simple version that was working)
  getEventPosition(event: CalendarEvent, day: dayjs.Dayjs, allEvents: CalendarEvent[]): any {
    console.log(`getEventPosition called for: ${event.title}`);
    const eventStart = this.parseEventTime(event.start_time);
    const eventEnd = this.parseEventTime(event.end_time);
    
    if (!eventStart.isValid() || !eventEnd.isValid()) {
      console.log(`Invalid time for event: ${event.title}`);
      return null;
    }

    // Constants for positioning
    const HOUR_HEIGHT = 64; // pixels per hour
    const MINUTE_HEIGHT = HOUR_HEIGHT / 60; // pixels per minute

    // Calculate position from start of day (00:00)
    const startMinutes = eventStart.hour() * 60 + eventStart.minute();
    const endMinutes = eventEnd.hour() * 60 + eventEnd.minute();

    const topPixels = startMinutes * MINUTE_HEIGHT;
    const heightPixels = (endMinutes - startMinutes) * MINUTE_HEIGHT;

    // Get all events for the current day
    const eventsForDay = allEvents.filter(e => {
      const eStart = this.parseEventTime(e.start_time);
      return eStart.isValid() && eStart.isSame(day, 'day');
    });

    // Find all overlapping events (simple version)
    const globalOverlappingGroup = new Set<CalendarEvent>();
    
    eventsForDay.forEach(event => {
      const eventStart = this.parseEventTime(event.start_time);
      const eventEnd = this.parseEventTime(event.end_time);
      
      if (!eventStart.isValid() || !eventEnd.isValid()) return;
      
      // Find all overlapping events
      const overlappingEvents = eventsForDay.filter(otherEvent => {
        if (otherEvent.id === event.id) return false;
        
        const otherStart = this.parseEventTime(otherEvent.start_time);
        const otherEnd = this.parseEventTime(otherEvent.end_time);
        
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
      const aStart = this.parseEventTime(a.start_time);
      const bStart = this.parseEventTime(b.start_time);
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
      
      eventWidth = singleEventWidthPercent; // Return as number for [style.width.%]
      leftOffset = `${leftPositionPercent}%`; // Keep as string for left positioning
    } else {
      // Single event - use full width with small margins
      eventWidth = 100; // Return as number for [style.width.%]
      leftOffset = '2px';
    }

    // Debug logging
    console.log(`WeekView Event: ${event.title}, Day: ${day.format('YYYY-MM-DD')}, Top: ${topPixels}, Height: ${heightPixels}, Width: ${eventWidth}, Left: ${leftOffset}`);

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
  }

  getEventTailwindClasses(event: CalendarEvent): string {
    // 🎨 Thunderbird-style: Get color from local registry
    const thunderbirdColor = this.colorRegistry.getCalendarColor(event.calendar_name || '');
    const eventColor = thunderbirdColor || event.color || event.calendar_color || '#4285f4';
    
    // Convert hex color to Tailwind color class
    let colorClass = 'bg-blue-500'; // Default blue
    
    if (eventColor === '#ff0000') {
      colorClass = 'bg-red-500';
    } else if (eventColor === '#00ff00') {
      colorClass = 'bg-green-500';
    } else if (eventColor === '#0000ff') {
      colorClass = 'bg-blue-600';
    } else if (eventColor === '#ffa500') {
      colorClass = 'bg-orange-500';
    } else if (eventColor === '#800080') {
      colorClass = 'bg-purple-500';
    } else if (eventColor === '#ffff00') {
      colorClass = 'bg-yellow-500';
    } else if (eventColor === '#ffc0cb') {
      colorClass = 'bg-pink-500';
    } else if (eventColor === '#00ffff') {
      colorClass = 'bg-cyan-500';
    } else {
      // For custom colors, use arbitrary value with proper syntax
      colorClass = `bg-[${eventColor}]`;
    }
    
    console.log('🎨 Tailwind color class:', colorClass);
    
    return `week-event-item cursor-pointer ${colorClass}`;
  }

  getEventStyle(event: CalendarEvent): any {
    // 🎨 Thunderbird-style: Get color from local registry
    const thunderbirdColor = this.colorRegistry.getCalendarColor(event.calendar_name || '');
    
    // Use Thunderbird-style color as primary, with fallbacks
    const eventColor = thunderbirdColor || event.color || event.calendar_color || '#4285f4';
    
    // Debug logging
    console.log('🎨 Thunderbird Week Event Style Debug:', {
      eventTitle: event.title,
      calendarName: event.calendar_name,
      thunderbirdColor: thunderbirdColor,
      eventColor: event.color,
      calendarColor: event.calendar_color,
      finalColor: eventColor,
      event: event
    });
    
    // Return styles with background color as fallback for custom colors
    const styles = {
      'background-color': eventColor,
      'border-left': `4px solid ${eventColor}`,
      'color': 'white'
    };
    
    console.log('🎨 Thunderbird Applied styles:', styles);
    console.log('🎨 Event color for Tailwind:', eventColor);
    
    return styles;
  }

  isToday(day: dayjs.Dayjs): boolean {
    return day.isSame(dayjs(), 'day');
  }

  isCurrentHour(hour: number): boolean {
    return hour === dayjs().hour();
  }

  getWeekDays(): dayjs.Dayjs[] {
    const weekStart = this.date.startOf('week');
    return Array.from({ length: 7 }, (_, i) => weekStart.add(i, 'day'));
  }

  getEventsForDay(day: dayjs.Dayjs): CalendarEvent[] {
    const eventsForDay = this.events.filter(event => {
      const eventStart = this.parseEventTime(event.start_time);
      return eventStart.isValid() && eventStart.isSame(day, 'day');
    });
    console.log(`Events for day ${day.format('YYYY-MM-DD')}:`, eventsForDay.length);
    return eventsForDay;
  }



  getAllDayEventsForDay(day: dayjs.Dayjs): CalendarEvent[] {
    return this.events.filter(event => {
      if (!event.all_day) return false;
      const eventDate = this.parseEventTime(event.start_time);
      return eventDate.isSame(day, 'day');
    });
  }

  onDeleteEvent(event: CalendarEvent): void {
    this.deleteEvent.emit(event);
  }

  onEventClick(event: CalendarEvent): void {
    this.eventClick.emit(event);
  }

  formatTime(event: CalendarEvent): string {
    if (event.all_day) {
      return 'All Day';
    }
    const eventStart = this.parseEventTime(event.start_time);
    const eventEnd = this.parseEventTime(event.end_time);
    return `${eventStart.format('HH:mm')} - ${eventEnd.format('HH:mm')}`;
  }

  getEventTitle(event: CalendarEvent): string {
    const eventStart = this.parseEventTime(event.start_time);
    const eventEnd = this.parseEventTime(event.end_time);
    return `${event.title} - ${eventStart.format('HH:mm')} - ${eventEnd.format('HH:mm')}`;
  }

  trackByHour(index: number, hour: number): number {
    return hour;
  }

  trackByEventId(index: number, event: CalendarEvent): string {
    return event.id;
  }

  trackByDay(index: number, day: dayjs.Dayjs): string {
    return day.format('YYYY-MM-DD');
  }
}