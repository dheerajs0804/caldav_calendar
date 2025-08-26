import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import * as dayjs from 'dayjs';

interface Calendar {
  id: number;
  name: string;
  color: string;
  url?: string;
  userId: number;
  isActive: boolean;
  syncToken?: string;
  createdAt: string;
  updatedAt: string;
}

interface Event {
  id: string;
  uid?: string;
  title: string;
  description?: string;
  location?: string;
  start_time: string;
  end_time: string;
  all_day: boolean;
  calendar_id: number;
  reminder?: {
    enabled: boolean;
    type: string;
    time: number;
    unit: string;
    relativeTo: string;
  };
  valarm?: {
    trigger: string;
    action: string;
    description: string;
  };
}

@Component({
  selector: 'app-day-view',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './day-view.component.html',
  styleUrls: ['./day-view.component.scss']
})
export class DayViewComponent {
  @Input() date!: Date;
  @Input() events: Event[] = [];
  @Input() calendars: Calendar[] = [];
  @Output() deleteEvent = new EventEmitter<Event>();

  hours = Array.from({ length: 24 }, (_, i) => i);

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
  findOverlappingEventsAtTime(event: Event, allEvents: Event[], timePoint: dayjs.Dayjs): Event[] {
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

  getEventStyle(event: Event): any {
    const calendar = this.calendars.find(c => c.id === event.calendar_id);
    return {
      backgroundColor: calendar?.color || '#4285f4',
      borderLeft: `4px solid ${calendar?.color || '#4285f4'}`,
    };
  }

  isToday(day: Date): boolean {
    return dayjs(day).isSame(dayjs(), 'day');
  }

  isCurrentHour(hour: number): boolean {
    return hour === dayjs().hour();
  }

  getEventsForDay(): Event[] {
    return this.events.filter(event => dayjs(event.start_time).isSame(dayjs(this.date), 'day'));
  }

  getAllDayEvents(): Event[] {
    return this.events.filter(event => event.all_day && dayjs(event.start_time).isSame(dayjs(this.date), 'day'));
  }

  getOverlappingGroups(): { events: Event[], globalGroup: Set<Event>, sortedGroup: Event[] } {
    const eventsForDay = this.getEventsForDay();
    
    // Create a single global overlapping group for all events that overlap with each other
    const globalOverlappingGroup = new Set<Event>();
    
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



  onDeleteEvent(event: Event): void {
    this.deleteEvent.emit(event);
  }

  formatTime(event: Event): string {
    const eventStart = this.parseEventTime(event.start_time);
    const eventEnd = this.parseEventTime(event.end_time);
    return `${eventStart.format('HH:mm')} - ${eventEnd.format('HH:mm')}`;
  }

  getEventTitle(event: Event): string {
    const eventStart = this.parseEventTime(event.start_time);
    const eventEnd = this.parseEventTime(event.end_time);
    return `${event.title} - ${eventStart.format('HH:mm')} - ${eventEnd.format('HH:mm')}`;
  }

  trackByHour(index: number, hour: number): number {
    return hour;
  }

  trackByEventId(index: number, event: Event): string {
    return event.id;
  }

  // Full overlapping event positioning logic (same as React version)
  getEventPosition(event: Event, globalGroup: Set<Event>, sortedGroup: Event[]): any {
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
