import { Pipe, PipeTransform } from '@angular/core';
import * as dayjs from 'dayjs';
import { CalendarEvent } from '../interfaces/calendar-event.interface';


@Pipe({
  name: 'sortByStartTime',
  standalone: true
})
export class SortByStartTimePipe implements PipeTransform {
  transform(events: CalendarEvent[]): CalendarEvent[] {
    if (!events) return [];
    
    return events.sort((a, b) => {
      const aStart = dayjs(a.start_time);
      const bStart = dayjs(b.start_time);
      return aStart.isBefore(bStart) ? -1 : aStart.isAfter(bStart) ? 1 : 0;
    });
  }
}
