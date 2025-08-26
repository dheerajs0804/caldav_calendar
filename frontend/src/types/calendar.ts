export interface Calendar {
  id: number;
  name: string;
  color: string;
  url: string;
  read_only: boolean;
  sync_token?: string;
  user_id: number;
  created_at: string;
  updated_at: string;
}

export interface Event {
  id: number;
  calendar_id: number;
  uid: string;
  title: string;
  description?: string;
  start_time: string;
  end_time: string;
  all_day: boolean;
  location?: string;
  recurrence_rule?: string;
  etag?: string;
  created_at: string;
  updated_at: string;
}

export interface CalendarView {
  type: 'day' | 'week' | 'month' | 'agenda';
  label: string;
  icon: string;
}

export interface CalendarState {
  calendars: Calendar[];
  events: Event[];
  selectedCalendar: Calendar | null;
  currentView: CalendarView;
  currentDate: Date;
  loading: boolean;
  error: string | null;
}

export interface CreateCalendarData {
  name: string;
  url: string;
  color?: string;
  read_only?: boolean;
}

export interface CreateEventData {
  calendar_id: number;
  title: string;
  description?: string;
  start_time: string;
  end_time: string;
  all_day?: boolean;
  location?: string;
  recurrence_rule?: string;
}

export interface UpdateEventData extends Partial<CreateEventData> {
  id: number;
}

export interface ApiResponse<T = any> {
  success: boolean;
  data?: T;
  error?: string;
  message?: string;
}

export interface SyncResult {
  processed_events: {
    created: number;
    updated: number;
    deleted: number;
  };
  new_sync_token?: string;
}

export interface User {
  id: number;
  username: string;
  email: string;
  timezone: string;
  created_at: string;
  updated_at: string;
}

export interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  loading: boolean;
  error: string | null;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface RegisterData {
  username: string;
  email: string;
  password: string;
  confirm_password: string;
}

export interface RecurrenceRule {
  freq: 'DAILY' | 'WEEKLY' | 'MONTHLY' | 'YEARLY';
  interval?: number;
  count?: number;
  until?: string;
  byday?: string[];
  bymonth?: number[];
  bymonthday?: number[];
}

export interface EventFormData {
  title: string;
  description: string;
  start_date: string;
  start_time: string;
  end_date: string;
  end_time: string;
  all_day: boolean;
  location: string;
  calendar_id: number;
  recurrence: RecurrenceRule | null;
}

export interface CalendarSettings {
  default_view: CalendarView['type'];
  week_start: 'monday' | 'sunday';
  time_format: '12h' | '24h';
  timezone: string;
  working_hours: {
    start: string;
    end: string;
  };
  show_weekends: boolean;
  show_holidays: boolean;
}
