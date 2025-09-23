export interface RecurrenceRule {
  frequency: 'never' | 'daily' | 'weekly' | 'monthly' | 'annually' | 'ondates';
  interval?: number; // Every X days/weeks/months/years
  count?: number; // Number of occurrences
  until?: string; // End date
  byDay?: string[]; // Days of week (MO, TU, WE, etc.)
  byMonth?: number[]; // Months (1-12)
  byMonthDay?: number[]; // Days of month (1-31)
  bySetPos?: number; // Position in month (1st, 2nd, etc.)
  specificDates?: string[]; // Specific dates for 'ondates'
}

export interface CalendarEvent {
  id: string;
  uid?: string;
  title: string;
  description?: string;
  location?: string;
  start_time: string;
  end_time: string;
  all_day: boolean;
  calendar_id: number;
  calendar_name?: string;
  calendar_color?: string;
  calendar_url?: string; // Add calendar URL for proper deletion
  color?: string;
  availability?: 'free' | 'busy' | 'tentative';
  status?: 'confirmed' | 'tentative' | 'cancelled';
  recurrence?: RecurrenceRule;
  exdate?: string | string[]; // Exception dates for single occurrence deletions
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
  attendees?: {
    email: string;
    name: string;
    response: string;
    role: string;
  }[];
  created_at?: string;
  updated_at?: string;
  // Properties for expanded recurring events
  isRecurringInstance?: boolean;
  originalEventId?: string;
  occurrenceIndex?: number;
}

export interface Calendar {
  id: number;
  name: string;
  color: string;
  url?: string;
  userId: number;
  isActive: boolean;
  enabled?: boolean;
  description?: string;
  syncToken?: string;
  createdAt: string;
  updatedAt: string;
}
