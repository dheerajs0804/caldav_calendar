import { Pipe, PipeTransform } from '@angular/core';
import * as dayjs from 'dayjs';

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

@Pipe({
  name: 'sortByStartTime',
  standalone: true
})
export class SortByStartTimePipe implements PipeTransform {
  transform(events: Event[]): Event[] {
    if (!events) return [];
    
    return events.sort((a, b) => {
      const aStart = dayjs(a.start_time);
      const bStart = dayjs(b.start_time);
      return aStart.isBefore(bStart) ? -1 : aStart.isAfter(bStart) ? 1 : 0;
    });
  }
}
