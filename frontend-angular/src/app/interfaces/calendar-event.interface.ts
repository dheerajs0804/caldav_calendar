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
