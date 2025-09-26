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
    
    console.log('🔄 Expanding recurring event:', event.title);
    console.log('🕐 Original event start_time:', event.start_time);
    console.log('🕐 Original event end_time:', event.end_time);
    console.log('🕐 Parsed originalStart:', originalStart.toISOString());
    console.log('🕐 Parsed originalEnd:', originalEnd.toISOString());
    
    // Calculate duration of the original event
    const duration = originalEnd.getTime() - originalStart.getTime();
    
    let currentDate = new Date(originalStart);
    let occurrenceCount = 0;
    const maxOccurrences = event.recurrence.count || 1000; // Default to 1000 if no count specified
    const untilDate = event.recurrence.until ? new Date(event.recurrence.until) : null;
    
    console.log('🕐 Initial currentDate:', currentDate.toISOString());
    console.log('🕐 Duration in ms:', duration);
    
    // Parse EXDATE exceptions
    const exceptionDates = this.parseExceptionDates(event.exdate);
    console.log('🗑️ EXDATE exceptions for', event.title, ':', exceptionDates);
    console.log('🗑️ EXDATE raw data for', event.title, ':', event.exdate);
    console.log('🗑️ EXDATE processing for event UID:', event.uid);
    
    // Generate occurrences
    while (occurrenceCount < maxOccurrences && currentDate <= endDate) {
      // Skip if this occurrence is before our start date range
      if (currentDate >= startDate) {
        // Check if this occurrence is in the EXDATE list
        const isException = this.isDateInExceptions(currentDate, exceptionDates);
        
        if (!isException) {
          console.log(`🔄 Creating expanded event for occurrence ${occurrenceCount}`);
          console.log(`🕐 currentDate being passed:`, currentDate.toISOString());
          const expandedEvent = this.createExpandedEvent(event, currentDate, duration, occurrenceCount);
          expandedEvents.push(expandedEvent);
        } else {
          console.log('🗑️ Skipping occurrence due to EXDATE:', currentDate.toISOString().split('T')[0]);
        }
        
        // Debug: Log every occurrence check
        console.log(`🗑️ Occurrence ${occurrenceCount} for ${event.title}: ${currentDate.toISOString().split('T')[0]} - ${isException ? 'EXCLUDED' : 'INCLUDED'}`);
      }
      
      // Move to next occurrence
      const previousDate = new Date(currentDate);
      currentDate = this.getNextOccurrence(currentDate, event.recurrence!);
      console.log(`🕐 Moved from ${previousDate.toISOString()} to ${currentDate.toISOString()}`);
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
    
    // Preserve the original time from the recurring event
    const originalStart = new Date(originalEvent.start_time);
    const originalEnd = new Date(originalEvent.end_time);
    
    // Create new dates with the occurrence date but original time
    const occurrenceStart = new Date(startDate);
    occurrenceStart.setHours(originalStart.getHours(), originalStart.getMinutes(), originalStart.getSeconds(), originalStart.getMilliseconds());
    
    const occurrenceEnd = new Date(startDate);
    occurrenceEnd.setHours(originalEnd.getHours(), originalEnd.getMinutes(), originalEnd.getSeconds(), originalEnd.getMilliseconds());
    
    // Check if this occurrence has individual modifications
    const occurrenceKey = occurrenceStart.toISOString().replace(/[-:]/g, '').replace(/\.\d{3}Z$/, 'Z');
    const individualModification = originalEvent.individualOccurrences?.[occurrenceKey];
    
    console.log('🕐 Creating expanded event for occurrence', occurrenceIndex);
    console.log('🕐 Original start time:', originalEvent.start_time);
    console.log('🕐 Original end time:', originalEvent.end_time);
    console.log('🕐 Occurrence date:', startDate.toISOString().split('T')[0]);
    console.log('🕐 Calculated occurrence start:', occurrenceStart.toISOString());
    console.log('🕐 Calculated occurrence end:', occurrenceEnd.toISOString());
    console.log('🔍 Checking individual modification for:', occurrenceKey);
    console.log('🔍 Available individual modifications:', Object.keys(originalEvent.individualOccurrences || {}));
    console.log('🔍 Individual modification data:', individualModification);
    
    if (individualModification) {
      // Use individual modification data if available
      console.log('✅ Using individual modification for occurrence', occurrenceIndex);
      return {
        ...originalEvent,
        id: `${originalEvent.id}_${occurrenceIndex}`,
        uid: `${originalEvent.uid}_${occurrenceIndex}`,
        title: individualModification.title,
        description: individualModification.description,
        location: individualModification.location,
        start_time: individualModification.start_time,
        end_time: individualModification.end_time,
        all_day: individualModification.all_day,
        availability: individualModification.availability,
        status: individualModification.status,
        calendar_id: individualModification.calendar_id,
        attendees: individualModification.attendees,
        reminder: individualModification.reminder,
        // Mark this as an expanded instance with individual modifications
        isRecurringInstance: true,
        originalEventId: originalEvent.id,
        occurrenceIndex: occurrenceIndex,
        hasIndividualModifications: true
      };
    } else {
      // Use original event data
      console.log('🕐 Using original event data for occurrence', occurrenceIndex);
      return {
        ...originalEvent,
        id: `${originalEvent.id}_${occurrenceIndex}`,
        uid: `${originalEvent.uid}_${occurrenceIndex}`,
        start_time: occurrenceStart.toISOString(),
        end_time: occurrenceEnd.toISOString(),
        // Mark this as an expanded instance
        isRecurringInstance: true,
        originalEventId: originalEvent.id,
        occurrenceIndex: occurrenceIndex
      };
    }
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

  /**
   * Parse EXDATE exceptions from event data
   */
  private parseExceptionDates(exdate: any): Date[] {
    if (!exdate) return [];
    
    const exceptions: Date[] = [];
    
    if (Array.isArray(exdate)) {
      exdate.forEach(dateStr => {
        const date = this.parseExdateString(dateStr);
        if (date) {
          exceptions.push(date);
        }
      });
    } else if (typeof exdate === 'string') {
      const date = this.parseExdateString(exdate);
      if (date) {
        exceptions.push(date);
      }
    }
    
    console.log('🗑️ Parsed EXDATE exceptions:', exceptions.map(d => d.toISOString().split('T')[0]));
    return exceptions;
  }

  /**
   * Parse EXDATE string (can be UTC format like "20250920T111800Z" or ISO format)
   */
  private parseExdateString(dateStr: string): Date | null {
    try {
      // Handle UTC format: 20250920T111800Z
      if (/^\d{8}T\d{6}Z$/.test(dateStr)) {
        const year = parseInt(dateStr.substring(0, 4));
        const month = parseInt(dateStr.substring(4, 6)) - 1; // Month is 0-indexed
        const day = parseInt(dateStr.substring(6, 8));
        const hour = parseInt(dateStr.substring(9, 11));
        const minute = parseInt(dateStr.substring(11, 13));
        const second = parseInt(dateStr.substring(13, 15));
        
        const date = new Date(Date.UTC(year, month, day, hour, minute, second));
        console.log(`🗑️ Parsed UTC EXDATE: ${dateStr} -> ${date.toISOString()}`);
        return date;
      }
      
      // Handle ISO format: 2025-09-20T11:18:00+02:00
      const date = new Date(dateStr);
      if (!isNaN(date.getTime())) {
        console.log(`🗑️ Parsed ISO EXDATE: ${dateStr} -> ${date.toISOString()}`);
        return date;
      }
      
      console.warn(`🗑️ Could not parse EXDATE: ${dateStr}`);
      return null;
    } catch (error) {
      console.error(`🗑️ Error parsing EXDATE ${dateStr}:`, error);
      return null;
    }
  }

  /**
   * Check if a date is in the EXDATE exceptions list
   */
  private isDateInExceptions(date: Date, exceptions: Date[]): boolean {
    return exceptions.some(exceptionDate => {
      // Compare dates by day (ignore time and timezone)
      const dateStr = date.toISOString().split('T')[0]; // YYYY-MM-DD
      const exceptionStr = exceptionDate.toISOString().split('T')[0]; // YYYY-MM-DD
      
      const isMatch = dateStr === exceptionStr;
      if (isMatch) {
        console.log(`🗑️ Found EXDATE match: ${dateStr} matches ${exceptionStr}`);
      }
      
      return isMatch;
    });
  }
}
