import { Injectable } from '@angular/core';
import { CalendarEvent, RecurrenceRule } from '../interfaces/calendar-event.interface';

@Injectable({
  providedIn: 'root'
})
export class RecurrenceExpansionService {

  constructor() { }

  /**
   * Expand a recurring event into multiple individual events
   */
  expandRecurringEvent(event: CalendarEvent, startDate: Date, endDate: Date): CalendarEvent[] {
    if (!event.recurrence || event.recurrence.frequency === 'never') {
      return [event];
    }

    const expandedEvents: CalendarEvent[] = [];
    const originalStart = new Date(event.start_time);
    const originalEnd = new Date(event.end_time);
    
    // Calculate duration of the original event
    const duration = originalEnd.getTime() - originalStart.getTime();
    
    let currentDate = new Date(originalStart);
    let occurrenceCount = 0;
    const maxOccurrences = event.recurrence.count || 1000; // Default to 1000 if no count specified
    const untilDate = event.recurrence.until ? new Date(event.recurrence.until) : null;
    
    // Generate occurrences
    while (occurrenceCount < maxOccurrences && currentDate <= endDate) {
      // Skip if this occurrence is before our start date range
      if (currentDate >= startDate) {
        const expandedEvent = this.createExpandedEvent(event, currentDate, duration, occurrenceCount);
        expandedEvents.push(expandedEvent);
      }
      
      // Move to next occurrence
      currentDate = this.getNextOccurrence(currentDate, event.recurrence!);
      occurrenceCount++;
      
      // Stop if we've reached the until date
      if (untilDate && currentDate > untilDate) {
        break;
      }
    }
    
    return expandedEvents;
  }

  /**
   * Get the next occurrence date based on recurrence rule
   */
  private getNextOccurrence(currentDate: Date, recurrence: RecurrenceRule): Date {
    const nextDate = new Date(currentDate);
    const interval = recurrence.interval || 1;
    
    switch (recurrence.frequency) {
      case 'daily':
        nextDate.setDate(nextDate.getDate() + interval);
        break;
        
      case 'weekly':
        nextDate.setDate(nextDate.getDate() + (7 * interval));
        break;
        
      case 'monthly':
        nextDate.setMonth(nextDate.getMonth() + interval);
        break;
        
      case 'annually':
        nextDate.setFullYear(nextDate.getFullYear() + interval);
        break;
        
      default:
        nextDate.setDate(nextDate.getDate() + interval);
    }
    
    return nextDate;
  }

  /**
   * Create an expanded event instance
   */
  private createExpandedEvent(originalEvent: CalendarEvent, startDate: Date, duration: number, occurrenceIndex: number): CalendarEvent {
    const endDate = new Date(startDate.getTime() + duration);
    
    return {
      ...originalEvent,
      id: `${originalEvent.id}_${occurrenceIndex}`,
      uid: `${originalEvent.uid}_${occurrenceIndex}`,
      start_time: startDate.toISOString(),
      end_time: endDate.toISOString(),
      // Mark this as an expanded instance
      isRecurringInstance: true,
      originalEventId: originalEvent.id,
      occurrenceIndex: occurrenceIndex
    };
  }

  /**
   * Expand all recurring events in a list
   */
  expandAllRecurringEvents(events: CalendarEvent[], startDate: Date, endDate: Date): CalendarEvent[] {
    const expandedEvents: CalendarEvent[] = [];
    
    for (const event of events) {
      const expanded = this.expandRecurringEvent(event, startDate, endDate);
      expandedEvents.push(...expanded);
    }
    
    return expandedEvents;
  }

  /**
   * Get a human-readable description of the recurrence rule
   */
  getRecurrenceDescription(recurrence: RecurrenceRule): string {
    if (!recurrence || recurrence.frequency === 'never') {
      return 'Does not repeat';
    }

    const frequency = recurrence.frequency;
    const interval = recurrence.interval || 1;
    const count = recurrence.count;
    const until = recurrence.until;

    let description = '';

    // Frequency and interval
    if (interval === 1) {
      description = `Every ${frequency}`;
    } else {
      description = `Every ${interval} ${frequency}`;
    }

    // Add count or until
    if (count) {
      description += `, ${count} times`;
    } else if (until) {
      const untilDate = new Date(until);
      description += `, until ${untilDate.toLocaleDateString()}`;
    }

    return description;
  }
}
