import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import * as dayjs from 'dayjs';
import { CalendarEvent, Calendar } from '../../interfaces/calendar-event.interface';
import { ColorRegistryService } from '../../services/color-registry.service';


@Component({
  selector: 'app-day-view',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './day-view.component.html',
  styleUrls: ['./day-view.component.scss']
})
export class DayViewComponent {
  @Input() date!: Date;
  @Input() events: CalendarEvent[] = [];
  @Input() calendars: Calendar[] = [];
  @Output() deleteEvent = new EventEmitter<CalendarEvent>();
  @Output() eventClick = new EventEmitter<CalendarEvent>();

  hours = Array.from({ length: 24 }, (_, i) => i);

  constructor(private colorRegistry: ColorRegistryService) {}

  // Helper function to parse time strings and handle timezone offsets
  parseEventTime(timeString: string): dayjs.Dayjs {
    try {
      const date = new Date(timeString);
      return dayjs(date);
    } catch (error) {
      const cleanTime = timeString.replace(/[+-]\d{2}:\d{2}$/, '');
      return dayjs(cleanTime);
    }
  }

  // Function to find overlapping events at a specific time point
  findOverlappingEventsAtTime(event: CalendarEvent, allEvents: CalendarEvent[], timePoint: dayjs.Dayjs): CalendarEvent[] {
    const eventStart = this.parseEventTime(event.start_time);
    const eventEnd = this.parseEventTime(event.end_time);
    
    if (!eventStart.isValid() || !eventEnd.isValid()) {
      return [];
    }
    
    // Get all events for the current day first
    const eventsForDay = allEvents.filter(e => {
      const eStart = this.parseEventTime(e.start_time);
      return eStart.isValid() && eStart.isSame(dayjs(this.date), 'day');
    });
    
    // Find events that are active at the given time point
    const activeEvents = eventsForDay.filter(otherEvent => {
      const otherStart = this.parseEventTime(otherEvent.start_time);
      const otherEnd = this.parseEventTime(otherEvent.end_time);
      
      if (!otherStart.isValid() || !otherEnd.isValid()) {
        return false;
      }
      
      // Event is active if the time point is within its duration
      const isActive = (timePoint.isSame(otherStart) || timePoint.isAfter(otherStart)) && timePoint.isBefore(otherEnd);
      
      return isActive;
    });
    
    return activeEvents;
  }

  getEventStyle(event: CalendarEvent): any {
    // 🎨 Thunderbird-style: Get color from local registry
    const thunderbirdColor = this.colorRegistry.getCalendarColor(event.calendar_name || '');
    
    // Use Thunderbird-style color as primary, with fallbacks
    const eventColor = thunderbirdColor || event.color || event.calendar_color || '#4285f4';
    
    // Debug logging
    console.log('🎨 Thunderbird Day Event Style Debug:', {
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
    
    return `event-container day-event-item cursor-pointer ${colorClass}`;
  }

  isToday(day: Date): boolean {
    return dayjs(day).isSame(dayjs(), 'day');
  }

  getEventClass(event: CalendarEvent): string {
    const calendarName = event.calendar_name?.toLowerCase().replace(/[^a-z0-9]/g, '') || 'default';
    return `event-${calendarName}`;
  }

  isCurrentHour(hour: number): boolean {
    return hour === dayjs().hour();
  }

  getEventsForDay(): CalendarEvent[] {
    return this.events.filter(event => dayjs(event.start_time).isSame(dayjs(this.date), 'day'));
  }

  getAllDayEvents(): CalendarEvent[] {
    return this.events.filter(event => event.all_day && dayjs(event.start_time).isSame(dayjs(this.date), 'day'));
  }

  getOverlappingGroups(): { events: CalendarEvent[], globalGroup: Set<CalendarEvent>, sortedGroup: CalendarEvent[] } {
    const eventsForDay = this.getEventsForDay();
    
    // Create a single global overlapping group for all events that overlap with each other
    const globalOverlappingGroup = new Set<CalendarEvent>();
    
    // For each event, find all events that overlap with it and add them to the global group
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
    
    // Convert Set to Array and sort by start time
    const sortedGlobalGroup = Array.from(globalOverlappingGroup).sort((a, b) => {
      const aStart = this.parseEventTime(a.start_time);
      const bStart = this.parseEventTime(b.start_time);
      return aStart.isBefore(bStart) ? -1 : 1;
    });

    return {
      events: eventsForDay,
      globalGroup: globalOverlappingGroup,
      sortedGroup: sortedGlobalGroup
    };
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

  // Full overlapping event positioning logic (same as React version)
  getEventPosition(event: CalendarEvent, globalGroup: Set<CalendarEvent>, sortedGroup: CalendarEvent[]): any {
    const eventStart = this.parseEventTime(event.start_time);
    const eventEnd = this.parseEventTime(event.end_time);
    
    // Calculate base positioning
    const startMinutes = eventStart.hour() * 60 + eventStart.minute();
    const gridPixelsPerMinute = 64 / 60;
    const topPositionPixels = startMinutes * gridPixelsPerMinute;
    
    // Position events relative to the time grid container (no header offset needed)
    const adjustedTopPosition = topPositionPixels;
    
    // Calculate duration in minutes
    const durationMinutes = eventEnd.diff(eventStart, 'minute');
    const heightPositionPixels = durationMinutes * gridPixelsPerMinute;
    
    // Determine if this event is part of the global overlapping group
    const isInOverlappingGroup = globalGroup.has(event);
    
    let eventIndex, totalOverlapping;
    
    if (isInOverlappingGroup) {
      // This event is part of the global overlapping group
      eventIndex = sortedGroup.findIndex(e => e.id === event.id);
      totalOverlapping = sortedGroup.length;
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
      
      eventWidth = singleEventWidthPercent;
      leftOffset = `calc(80px + ${leftPositionPercent}%)`;
    } else {
      // Single event - use full width
      eventWidth = 100;
      leftOffset = 'calc(80px + 0%)';
    }
    
    return {
      top: adjustedTopPosition,
      height: Math.max(heightPositionPixels, 20),
      width: eventWidth,
      left: leftOffset,
      zIndex: 10,
      minHeight: 20
    };
  }
}
