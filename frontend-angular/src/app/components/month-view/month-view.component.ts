import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';
import * as dayjs from 'dayjs';
import { CalendarEvent, Calendar } from '../../interfaces/calendar-event.interface';


@Component({
  selector: 'app-month-view',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './month-view.component.html',
  styleUrls: ['./month-view.component.scss']
})
export class MonthViewComponent {
  @Input() date!: Date;
  @Input() events: CalendarEvent[] = [];
  @Input() calendars: Calendar[] = [];

  weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

  getMonthStart(): Date {
    return dayjs(this.date).startOf('month').toDate();
  }

  getMonthEnd(): dayjs.Dayjs {
    return dayjs(this.date).endOf('month');
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
    const calendar = this.calendars.find(c => c.id === event.calendar_id);
    return {
      backgroundColor: calendar?.color || '#4285f4',
    };
  }

  isToday(date: dayjs.Dayjs): boolean {
    return date.isSame(dayjs(), 'day');
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
