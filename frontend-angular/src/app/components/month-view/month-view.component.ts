import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';
import * as dayjs from 'dayjs';
import * as isoWeek from 'dayjs/plugin/isoWeek';
import { CalendarEvent, Calendar } from '../../interfaces/calendar-event.interface';
import { ColorRegistryService } from '../../services/color-registry.service';

// Configure dayjs to use ISO week (Monday as first day)
dayjs.extend(isoWeek);


@Component({
  selector: 'app-month-view',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './month-view.component.html',
  styleUrls: ['./month-view.component.scss']
})
export class MonthViewComponent {
  @Input() date!: dayjs.Dayjs;
  @Input() events: CalendarEvent[] = [];
  @Input() calendars: Calendar[] = [];

  weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

  constructor(private colorRegistry: ColorRegistryService) {}

  getMonthStart(): dayjs.Dayjs {
    return this.date.startOf('month');
  }

  getMonthEnd(): dayjs.Dayjs {
    return this.date.endOf('month');
  }

  getStartDate(): dayjs.Dayjs {
    // Ensure week starts on Monday (ISO week)
    return dayjs(this.getMonthStart()).startOf('isoWeek');
  }

  getEndDate(): dayjs.Dayjs {
    // Ensure week ends on Sunday (ISO week)
    return this.getMonthEnd().endOf('isoWeek');
  }

  getDays(): dayjs.Dayjs[] {
    const days: dayjs.Dayjs[] = [];
    const startDate = this.getStartDate();
    const endDate = this.getEndDate();
    let current = startDate;
    
    console.log('📅 Month View Date Debug:', {
      currentDate: this.date.format('YYYY-MM-DD'),
      monthStart: this.getMonthStart().format('YYYY-MM-DD'),
      weekStart: startDate.format('YYYY-MM-DD'),
      weekEnd: endDate.format('YYYY-MM-DD'),
      weekStartDay: startDate.format('dddd'),
      weekEndDay: endDate.format('dddd')
    });
    
    while (current.isBefore(endDate) || current.isSame(endDate, 'day')) {
      days.push(current);
      current = current.add(1, 'day');
    }
    
    console.log('📅 Generated days count:', days.length);
    console.log('📅 First day:', days[0]?.format('YYYY-MM-DD dddd'));
    console.log('📅 Last day:', days[days.length - 1]?.format('YYYY-MM-DD dddd'));
    
    return days;
  }

  getEventsForDate(date: dayjs.Dayjs): CalendarEvent[] {
    return this.events.filter(event => {
      const eventStart = dayjs(event.start_time);
      const eventEnd = dayjs(event.end_time);
      
      // Include events that:
      // 1. Start on this day, OR
      // 2. End on this day, OR  
      // 3. Span across this day (start before and end after)
      return eventStart.isSame(date, 'day') || 
             eventEnd.isSame(date, 'day') || 
             (eventStart.isBefore(date, 'day') && eventEnd.isAfter(date, 'day'));
    });
  }

  getEventStyle(event: CalendarEvent): any {
    // 🎨 Thunderbird-style: Get color from local registry
    const thunderbirdColor = this.colorRegistry.getCalendarColor(event.calendar_name || '');
    
    // Use Thunderbird-style color as primary, with fallbacks
    const eventColor = thunderbirdColor || event.color || event.calendar_color || '#4285f4';
    
    // Debug logging
    console.log('🎨 Thunderbird Month Event Style Debug:', {
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
    
    return `month-event text-white rounded px-2 py-1 text-xs cursor-pointer ${colorClass}`;
  }

  isToday(date: dayjs.Dayjs): boolean {
    return date.isSame(dayjs(), 'day');
  }

  getEventClass(event: CalendarEvent): string {
    const calendarName = event.calendar_name?.toLowerCase().replace(/[^a-z0-9]/g, '') || 'default';
    return `event-${calendarName}`;
  }

  isCurrentMonth(date: dayjs.Dayjs): boolean {
    return date.isSame(this.date, 'month');
  }

  trackByDay(index: number, day: dayjs.Dayjs): string {
    return day.format('YYYY-MM-DD');
  }

  trackByEventId(index: number, event: CalendarEvent): string {
    return event.id;
  }
}
