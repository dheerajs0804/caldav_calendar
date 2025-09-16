import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';
import * as dayjs from 'dayjs';
import { CalendarEvent, Calendar } from '../../interfaces/calendar-event.interface';
import { ColorRegistryService } from '../../services/color-registry.service';


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

  getMonthStart(): Date {
    return this.date.startOf('month').toDate();
  }

  getMonthEnd(): dayjs.Dayjs {
    return this.date.endOf('month');
  }

  getStartDate(): dayjs.Dayjs {
    return dayjs(this.getMonthStart()).startOf('week');
  }

  getEndDate(): dayjs.Dayjs {
    return this.getMonthEnd().endOf('week');
  }

  getDays(): dayjs.Dayjs[] {
    const days: dayjs.Dayjs[] = [];
    const startDate = this.getStartDate();
    const endDate = this.getEndDate();
    let current = startDate;
    
    while (current.isBefore(endDate) || current.isSame(endDate, 'day')) {
      days.push(current);
      current = current.add(1, 'day');
    }
    
    return days;
  }

  getEventsForDate(date: dayjs.Dayjs): CalendarEvent[] {
    return this.events.filter(event => {
      const eventDate = dayjs(event.start_time);
      return eventDate.isSame(date, 'day');
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
    return date.isSame(this.getMonthStart(), 'month');
  }

  trackByDay(index: number, day: dayjs.Dayjs): string {
    return day.format('YYYY-MM-DD');
  }

  trackByEventId(index: number, event: CalendarEvent): string {
    return event.id;
  }
}
