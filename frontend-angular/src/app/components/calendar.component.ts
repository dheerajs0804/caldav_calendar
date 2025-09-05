import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { Observable, interval, Subscription } from 'rxjs';
import * as dayjs from 'dayjs';
import { DayViewComponent } from './day-view/day-view.component';
import { WeekViewComponent } from './week-view/week-view.component';
import { MonthViewComponent } from './month-view/month-view.component';
import { EventDetailModalComponent } from './event-detail-modal/event-detail-modal.component';
import { ReminderNotificationComponent, ReminderNotification, ReminderEvent } from './reminder-notification.component';
import { SortByStartTimePipe } from '../pipes/sort-by-start-time.pipe';
import { EmailService } from '../services/email.service';
import { AuthService } from '../services/auth.service';
import { Router } from '@angular/router';

interface Calendar {
  id: number;
  name: string;
  color: string;
  url?: string;
  userId: number;
  isActive: boolean;
  syncToken?: string;
  createdAt: string;
  updatedAt: string;
}

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
}

interface View {
  type: string;
  label: string;
  icon: string;
}

interface NewEvent {
  summary: string;
  location: string;
  description: string;
  start_date: string;
  start_time: string;
  end_date: string;
  end_time: string;
  all_day: boolean;
  reminder: {
    enabled: boolean;
    type: string;
    time: number;
    unit: string;
    relativeTo: string;
  };
  attendees: {
    email: string;
    name: string;
    response: string;
    role: string;
  }[];
}

@Component({
  selector: 'app-calendar',
  standalone: true,
  imports: [CommonModule, FormsModule, DayViewComponent, WeekViewComponent, MonthViewComponent, EventDetailModalComponent, ReminderNotificationComponent, SortByStartTimePipe],
  templateUrl: './calendar.component.html',
  styleUrls: ['./calendar.component.scss']
})
export class CalendarComponent implements OnInit, OnDestroy {
  views: View[] = [
    { type: 'day', label: 'Day', icon: '📅' },
    { type: 'week', label: 'Week', icon: '📅' },
    { type: 'month', label: 'Month', icon: '📅' },
    { type: 'agenda', label: 'Agenda', icon: '📋' }
  ];

  currentView: View = this.views[1]; // Start with week view
  currentDate: Date = new Date();
  calendars: Calendar[] = [];
  events: Event[] = [];
  loading: boolean = true;
  error: string | null = null;
  showAddEventModal: boolean = false;
  showEventDetailModal: boolean = false;
  selectedEvent: Event | null = null;
  selectedCalendar: any = null; // Store the selected calendar info
  newEvent: NewEvent = {
    summary: '',
    location: '',
    description: '',
    start_date: '',
    start_time: '',
    end_date: '',
    end_time: '',
    all_day: false,
    reminder: {
      enabled: false,
      type: 'message',
      time: 15,
      unit: 'minutes',
      relativeTo: 'start'
    },
    attendees: []
  };

  public notifiedEvents: Set<string> = new Set();
  private reminderInterval?: Subscription;
  
  // New reminder notification properties
  activeReminders: ReminderNotification[] = [];
  showReminderWindow = false;

  constructor(
    private http: HttpClient, 
    private emailService: EmailService,
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    console.log('Calendar component is loading...');
    
    // Check if user has selected a calendar
    const selectedCalendar = localStorage.getItem('selectedCalendar');
    if (!selectedCalendar) {
      // No calendar selected, redirect to calendar selection
      this.router.navigate(['/calendar-selection']);
      return;
    }
    
    // Parse the selected calendar
    try {
      this.selectedCalendar = JSON.parse(selectedCalendar);
      console.log('Selected calendar:', this.selectedCalendar);
    } catch (error) {
      console.error('Error parsing selected calendar:', error);
      this.router.navigate(['/calendar-selection']);
      return;
    }
    
    this.fetchCalendars();
    this.fetchEvents();
  }

  ngOnDestroy(): void {
    if (this.reminderInterval) {
      this.reminderInterval.unsubscribe();
    }
  }

  logout(): void {
    this.authService.logout();
    this.router.navigate(['/login']);
  }

  changeCalendar(): void {
    // Clear the selected calendar and redirect to selection
    localStorage.removeItem('selectedCalendar');
    this.router.navigate(['/calendar-selection']);
  }

  getWeekDateRange(): string {
    const weekStart = dayjs(this.currentDate).startOf('week');
    const weekEnd = weekStart.add(6, 'day');
    return `${weekStart.format('MMM D')} - ${weekEnd.format('MMM D, YYYY')}`;
  }

  // Fetch calendars from backend
  async fetchCalendars(): Promise<void> {
    try {
      this.loading = true;
      const response = await this.http.get<any>('http://localhost:8000/calendars', { withCredentials: true }).toPromise();
      
      if (response.success) {
        this.calendars = response.data;
        console.log('Calendars loaded:', response.data);
        await this.fetchEvents();
      } else {
        this.error = 'Failed to load calendars';
      }
    } catch (error) {
      console.error('Error fetching calendars:', error);
      this.error = 'Error connecting to backend';
    } finally {
      this.loading = false;
    }
  }

  // Fetch events when calendars change
  async fetchEvents(): Promise<void> {
    try {
      // Check if we have a selected calendar
      if (!this.selectedCalendar || !this.selectedCalendar.href) {
        console.error('No calendar selected, redirecting to calendar selection');
        this.router.navigate(['/calendar-selection']);
        return;
      }
      
      // Build the URL with the selected calendar
      const eventsUrl = `http://localhost:8000/events?calendar_url=${encodeURIComponent(this.selectedCalendar.href)}`;
      
      const response = await this.http.get<any>(eventsUrl, { withCredentials: true }).toPromise();
      
      if (response.success) {
        // Remove duplicate events using utility function
        const uniqueEvents = this.removeDuplicateEvents(response.data);
        
        // Debug: Log all events before and after deduplication
        console.log('🔍 EVENTS DEBUG - Before deduplication:', response.data);
        console.log('🔍 EVENTS DEBUG - After deduplication:', uniqueEvents);
        console.log('🔍 EVENTS DEBUG - Duplicates found:', response.data.length - uniqueEvents.length);
        
        this.events = uniqueEvents;
        console.log('Events loaded:', uniqueEvents);
        
        // Start reminder checking if events exist
        if (this.events.length > 0) {
          this.startReminderChecking();
        }
      } else {
        this.error = 'Failed to load events';
      }
    } catch (error) {
      console.error('Error loading events:', error);
      this.error = 'Error loading events';
    }
  }

  // Start checking for reminders every minute
  private startReminderChecking(): void {
    if (this.reminderInterval) {
      this.reminderInterval.unsubscribe();
    }

    this.reminderInterval = interval(60000).subscribe(() => {
      this.checkForReminders();
    });

    // Check immediately
    this.checkForReminders();
  }

  // Check for events that need reminders
  private checkForReminders(): void {
    const now = new Date();
    const currentTime = now.getTime();

    this.events.forEach(event => {
      if (this.shouldShowReminder(event, currentTime)) {
        this.showReminderNotification(event);
      }
    });
  }

  // Determine if a reminder should be shown for an event
  private shouldShowReminder(event: Event, currentTime: number): boolean {
    // Skip if already notified
    if (this.notifiedEvents.has(event.id)) {
      return false;
    }

    const eventStart = new Date(event.start_time).getTime();
    const timeUntilEvent = eventStart - currentTime;

    // Show reminder 15 minutes before event
    const reminderTime = 15 * 60 * 1000; // 15 minutes in milliseconds

    return timeUntilEvent > 0 && timeUntilEvent <= reminderTime;
  }

  // Show reminder notification
  private showReminderNotification(event: Event): void {
    console.log('Checking event:', event.title, '- Has reminder:', !!event.reminder, 'Has VALARM:', !!event.valarm);
    
    // Mark as notified
    this.notifiedEvents.add(event.id);

    // Create reminder notification
    const reminder: ReminderNotification = {
      id: `reminder-${event.id}-${Date.now()}`,
      event: {
        id: event.id,
        title: event.title,
        description: event.description || '',
        start_time: event.start_time,
        end_time: event.end_time,
        location: event.location,
        color: event.color || '#8b5cf6'
      },
      timestamp: new Date(),
      snoozed: false
    };

    console.log('Created reminder notification:', reminder);
    console.log('Total active reminders:', this.activeReminders.length + 1);

    // Add to active reminders
    this.activeReminders = [...this.activeReminders, reminder];
    this.showReminderWindow = true;

    console.log('Show reminder window:', this.showReminderWindow);
    console.log('Active reminders array:', this.activeReminders);
  }

  // Handle reminder dismissal
  onReminderDismissed(reminderId: string): void {
    console.log('Reminder dismissed:', reminderId);
    // Remove the reminder from active reminders
    this.activeReminders = this.activeReminders.filter(r => r.id !== reminderId);
    if (this.activeReminders.length === 0) {
      this.showReminderWindow = false;
    }
  }

  // Handle reminder snooze
  onReminderSnoozed(data: {id: string, minutes: number}): void {
    console.log('Reminder snoozed:', data);
    // Find the reminder and update its snooze time
    const reminder = this.activeReminders.find(r => r.id === data.id);
    if (reminder) {
      reminder.snoozed = true;
      reminder.snoozeUntil = new Date(Date.now() + data.minutes * 60 * 1000);
      // Remove from active reminders for now
      this.activeReminders = this.activeReminders.filter(r => r.id !== data.id);
      if (this.activeReminders.length === 0) {
        this.showReminderWindow = false;
      }
    }
  }

  // Handle reminder details
  onReminderDetails(reminder: ReminderNotification): void {
    console.log('Reminder details requested:', reminder);
    // Open the event detail modal for the reminder's event
    const event = this.events.find(e => e.id === reminder.event.id);
    if (event) {
      this.openEventDetailModal(event);
    }
  }

  // Handle reminder window close
  onReminderWindowClosed(): void {
    console.log('Reminder window closed');
    this.showReminderWindow = false;
  }

  // Remove duplicate events
  private removeDuplicateEvents(events: Event[]): Event[] {
    const seen = new Set();
    return events.filter(event => {
      const key = event.id || event.uid || event.title + event.start_time;
      if (seen.has(key)) {
        return false;
      }
      seen.add(key);
      return true;
    });
  }

  // Navigation methods
  previous(): void {
    if (this.currentView.type === 'day') {
      this.currentDate = dayjs(this.currentDate).subtract(1, 'day').toDate();
    } else if (this.currentView.type === 'week') {
      this.currentDate = dayjs(this.currentDate).subtract(1, 'week').toDate();
    } else if (this.currentView.type === 'month') {
      this.currentDate = dayjs(this.currentDate).subtract(1, 'month').toDate();
    }
  }

  next(): void {
    if (this.currentView.type === 'day') {
      this.currentDate = dayjs(this.currentDate).add(1, 'day').toDate();
    } else if (this.currentView.type === 'week') {
      this.currentDate = dayjs(this.currentDate).add(1, 'week').toDate();
    } else if (this.currentView.type === 'month') {
      this.currentDate = dayjs(this.currentDate).add(1, 'month').toDate();
    }
  }

  today(): void {
    this.currentDate = new Date();
  }

  changeView(view: View): void {
    this.currentView = view;
  }

  // Modal methods
  openAddEventModal(): void {
    this.showAddEventModal = true;
  }

  closeAddEventModal(): void {
    this.showAddEventModal = false;
    this.resetNewEvent();
  }

  openEventDetailModal(event: Event): void {
    this.selectedEvent = event;
    this.showEventDetailModal = true;
  }

  closeEventDetailModal(): void {
    this.showEventDetailModal = false;
    this.selectedEvent = null;
  }

  // Event creation
  async createEvent(): Promise<void> {
    try {
      const eventData = {
        title: this.newEvent.summary, // Map summary to title for backend
        description: this.newEvent.description,
        location: this.newEvent.location,
        start_time: this.newEvent.all_day 
          ? dayjs(this.newEvent.start_date).format('YYYY-MM-DDTHH:mm:ss')
          : dayjs(`${this.newEvent.start_date}T${this.newEvent.start_time}`).format('YYYY-MM-DDTHH:mm:ss'),
        end_time: this.newEvent.all_day
          ? dayjs(this.newEvent.end_date).format('YYYY-MM-DDTHH:mm:ss')
          : dayjs(`${this.newEvent.end_date}T${this.newEvent.end_time}`).format('YYYY-MM-DDTHH:mm:ss'),
        all_day: this.newEvent.all_day,
        attendees: this.newEvent.attendees,
        reminder: this.newEvent.reminder
      };

      const response = await this.http.post<any>('http://localhost:8000/events', eventData, {
        withCredentials: true
      }).toPromise();
      
      if (response.success) {
        console.log('Event created successfully:', response.data);
        this.closeAddEventModal();
        await this.fetchEvents(); // Refresh events
      } else {
        console.error('Failed to create event:', response.message);
      }
    } catch (error) {
      console.error('Error creating event:', error);
    }
  }

  // Reset new event form
  private resetNewEvent(): void {
    this.newEvent = {
      summary: '',
      location: '',
      description: '',
      start_date: '',
      start_time: '',
      end_date: '',
      end_time: '',
      all_day: false,
      reminder: {
        enabled: false,
        type: 'message',
        time: 15,
        unit: 'minutes',
        relativeTo: 'start'
      },
      attendees: []
    };
  }

  // Get events for a specific day
  getEventsForDay(date: Date): Event[] {
    return this.events.filter(event => {
      const eventDate = dayjs(event.start_time);
      const targetDate = dayjs(date);
      return eventDate.isSame(targetDate, 'day');
    });
  }

  // Get events for a specific time slot
  getEventsForTimeSlot(date: Date, hour: number): Event[] {
    return this.events.filter(event => {
      const eventDate = dayjs(event.start_time);
      const targetDate = dayjs(date);
      return eventDate.isSame(targetDate, 'day') && eventDate.hour() === hour;
    });
  }

  // Format time for display
  formatTime(time: string): string {
    return dayjs(time).format('h:mm A');
  }
}
