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
import { CalendarEvent, Calendar } from '../interfaces/calendar-event.interface';


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
  calendar_id?: number;
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
  events: CalendarEvent[] = [];
  loading: boolean = true;
  error: string | null = null;
  showAddEventModal: boolean = false;
  showEventDetailModal: boolean = false;
  selectedEvent: CalendarEvent | null = null;
  selectedCalendar: any = null; // Store the selected calendar info
  showSidebar: boolean = true; // Show sidebar by default
  searchTerm: string = ''; // For filtering calendars in sidebar
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
    
    // Always fetch calendars and show calendar view directly after login
    this.fetchCalendars();
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

  toggleSidebar(): void {
    this.showSidebar = !this.showSidebar;
  }

  toggleCalendar(calendar: Calendar, event: MouseEvent): void {
    event.stopPropagation();
    calendar.enabled = !calendar.enabled;
    
    // Update the calendar state on the server
    this.updateCalendarState(calendar);
  }

  updateCalendarState(calendar: Calendar): void {
    this.http.put(`http://localhost:8000/calendars/${calendar.id}/toggle`, {
      enabled: calendar.enabled
    }, { withCredentials: true }).subscribe({
      next: (response) => {
        console.log('Calendar state updated:', calendar.name, calendar.enabled);
        // Refresh events after calendar state change
        this.fetchEvents();
      },
      error: (error) => {
        console.error('Failed to update calendar state:', error);
        // Revert the change on error
        calendar.enabled = !calendar.enabled;
      }
    });
  }

  deleteCalendar(calendar: Calendar, event: MouseEvent): void {
    event.stopPropagation();
    
    // Confirm deletion
    if (!confirm(`Are you sure you want to delete the calendar "${calendar.name}"? This action cannot be undone.`)) {
      return;
    }
    
    this.http.delete(`http://localhost:8000/calendars/${calendar.id}`, { 
      withCredentials: true 
    }).subscribe({
      next: (response: any) => {
        if (response.success) {
          console.log('Calendar deleted successfully:', calendar.name);
          
          // Remove calendar from local array
          this.calendars = this.calendars.filter(cal => cal.id !== calendar.id);
          
          // If the deleted calendar was selected, select another one
          if (this.selectedCalendar && this.selectedCalendar.id === calendar.id) {
            this.selectedCalendar = this.calendars.find(cal => cal.enabled) || this.calendars[0] || null;
          }
          
          // Refresh events
          this.fetchEvents();
          
          alert('Calendar deleted successfully!');
        } else {
          console.error('Failed to delete calendar:', response.message);
          alert('Failed to delete calendar: ' + (response.message || 'Unknown error'));
        }
      },
      error: (error) => {
        console.error('Error deleting calendar:', error);
        alert('Error deleting calendar. Please try again.');
      }
    });
  }

  get filteredCalendars(): Calendar[] {
    if (!this.searchTerm.trim()) {
      return this.calendars;
    }
    const term = this.searchTerm.toLowerCase();
    return this.calendars.filter(cal => 
      cal.name.toLowerCase().includes(term) ||
      (cal.description && cal.description.toLowerCase().includes(term))
    );
  }

  get enabledCalendarsCount(): number {
    return this.calendars.filter(cal => cal.enabled).length;
  }

  get hasEnabledCalendars(): boolean {
    return this.calendars.some(cal => cal.enabled);
  }

  openAddCalendarModal(): void {
    // Navigate to calendar selection page for adding new calendars
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
      const response = await this.http.get<any>('http://localhost:8000/calendars/user', { withCredentials: true }).toPromise();
      
      if (response.success && response.data) {
        this.calendars = response.data.calendars.map((cal: any) => ({
          ...cal,
          enabled: cal.enabled ?? true // Default to enabled if not specified
        }));
        console.log('📅 Calendars loaded from backend:', this.calendars);
        console.log('📅 Calendar IDs:', this.calendars.map(cal => ({ name: cal.name, id: cal.id, type: typeof cal.id })));
        
        // Set the first enabled calendar as selected if none is selected
        if (!this.selectedCalendar && this.calendars.length > 0) {
          this.selectedCalendar = this.calendars.find(cal => cal.enabled) || this.calendars[0];
          console.log('📅 Set selected calendar:', this.selectedCalendar?.name, 'ID:', this.selectedCalendar?.id);
        }
        
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
      // If we have multiple enabled calendars, fetch events from all of them
      if (this.calendars && this.calendars.length > 0) {
        const allEvents: CalendarEvent[] = [];
        
        for (const calendar of this.calendars) {
          if (calendar.enabled && calendar.url) {
            try {
              const eventsUrl = `http://localhost:8000/events?calendar_url=${encodeURIComponent(calendar.url)}`;
      const response = await this.http.get<any>(eventsUrl, { withCredentials: true }).toPromise();
      
              if (response.success && response.data) {
                // Add calendar info to each event
                const eventsWithCalendar = response.data.map((event: CalendarEvent) => ({
                  ...event,
                  calendar_name: calendar.name,
                  calendar_color: calendar.color,
                  calendar_id: calendar.id,
                  calendar_url: calendar.url // Store calendar URL for proper deletion
                }));
                allEvents.push(...eventsWithCalendar);
              }
            } catch (error) {
              console.error(`Error fetching events from calendar ${calendar.name}:`, error);
            }
          }
        }
        
        // Remove duplicate events using utility function
        const uniqueEvents = this.removeDuplicateEvents(allEvents);
        
        // Debug: Log all events before and after deduplication
        console.log('🔍 EVENTS DEBUG - Before deduplication:', allEvents);
        console.log('🔍 EVENTS DEBUG - After deduplication:', uniqueEvents);
        console.log('🔍 EVENTS DEBUG - Duplicates found:', allEvents.length - uniqueEvents.length);
        
        this.events = uniqueEvents;
        console.log('Events loaded from all calendars:', uniqueEvents);
        
        // Start reminder checking if events exist
        if (this.events.length > 0) {
          this.startReminderChecking();
        }
      } else if (this.selectedCalendar && this.selectedCalendar.url) {
        // Fallback to single calendar mode
        const eventsUrl = `http://localhost:8000/events?calendar_url=${encodeURIComponent(this.selectedCalendar.url)}`;
        const response = await this.http.get<any>(eventsUrl, { withCredentials: true }).toPromise();
        
        if (response.success) {
          const uniqueEvents = this.removeDuplicateEvents(response.data);
          this.events = uniqueEvents;
          console.log('Events loaded from selected calendar:', uniqueEvents);
          
          if (this.events.length > 0) {
            this.startReminderChecking();
          }
        } else {
          this.error = 'Failed to load events';
        }
      } else {
        console.error('No enabled calendars or selected calendar available');
        this.router.navigate(['/calendar-selection']);
        return;
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
  private shouldShowReminder(event: CalendarEvent, currentTime: number): boolean {
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
  private showReminderNotification(event: CalendarEvent): void {
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
        color: event.color || '#8b5cf6',
        all_day: event.all_day
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
  private removeDuplicateEvents(events: CalendarEvent[]): CalendarEvent[] {
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

  openEventDetailModal(event: CalendarEvent): void {
    this.selectedEvent = event;
    this.showEventDetailModal = true;
  }

  closeEventDetailModal(): void {
    this.showEventDetailModal = false;
    this.selectedEvent = null;
  }

  // Handle calendar selection change in add event modal
  onCalendarSelectionChange(event: any): void {
    const selectedCalendarId = Number(event.target.value);
    const selectedCalendar = this.calendars.find(cal => Number(cal.id) === selectedCalendarId);
    console.log('📅 Calendar selection changed:');
    console.log('  - Raw value:', event.target.value, 'Type:', typeof event.target.value);
    console.log('  - Parsed ID:', selectedCalendarId, 'Type:', typeof selectedCalendarId);
    console.log('  - Found calendar:', selectedCalendar?.name, 'ID:', selectedCalendar?.id, 'Type:', typeof selectedCalendar?.id);
    console.log('📅 Available calendars:', this.calendars.map(cal => ({ 
      name: cal.name, 
      id: cal.id, 
      type: typeof cal.id, 
      enabled: cal.enabled 
    })));
    
    // Test the comparison
    console.log('🔍 Testing calendar ID comparison:');
    this.calendars.forEach(cal => {
      const matches = Number(cal.id) === selectedCalendarId;
      console.log(`  - Calendar "${cal.name}" (ID: ${cal.id}, type: ${typeof cal.id}) === ${selectedCalendarId} (type: ${typeof selectedCalendarId}): ${matches}`);
    });
  }

  // Event creation
  async createEvent(): Promise<void> {
    try {
      console.log('🚀 Starting event creation...');
      console.log('📋 New event data:', this.newEvent);
      console.log('📅 Selected calendar_id from dropdown:', this.newEvent.calendar_id);
      console.log('📅 Available calendars:', this.calendars.map(cal => ({ 
        name: cal.name, 
        id: cal.id, 
        enabled: cal.enabled, 
        url: cal.url 
      })));
      
      // Determine which calendar to use for event creation
      let targetCalendar = null;
      
      // Priority 1: Use the calendar selected in the "Add Event" modal dropdown
      if (this.newEvent.calendar_id && this.calendars && this.calendars.length > 0) {
        // Convert both to numbers for comparison to handle type mismatches
        const selectedId = Number(this.newEvent.calendar_id);
        targetCalendar = this.calendars.find(cal => Number(cal.id) === selectedId);
        console.log('✅ Priority 1 - Using calendar from dropdown:', targetCalendar?.name, 'ID:', targetCalendar?.id);
        console.log('  - Selected ID:', selectedId, 'Type:', typeof selectedId);
        console.log('  - Calendar IDs:', this.calendars.map(cal => ({ name: cal.name, id: cal.id, type: typeof cal.id })));
      } else {
        console.log('❌ Priority 1 - No calendar_id in newEvent or no calendars available');
        console.log('  - calendar_id:', this.newEvent.calendar_id, 'Type:', typeof this.newEvent.calendar_id);
        console.log('  - calendars length:', this.calendars?.length);
      }
      
      // Priority 2: If no calendar selected in dropdown, use the currently selected calendar
      if (!targetCalendar && this.selectedCalendar) {
        targetCalendar = this.selectedCalendar;
        console.log('✅ Priority 2 - Using currently selected calendar:', targetCalendar?.name, 'ID:', targetCalendar?.id);
      } else if (!targetCalendar) {
        console.log('❌ Priority 2 - No currently selected calendar');
      }
      
      // Priority 3: Fallback to the first enabled calendar
      if (!targetCalendar && this.calendars && this.calendars.length > 0) {
        targetCalendar = this.calendars.find(cal => cal.enabled);
        console.log('✅ Priority 3 - Using first enabled calendar:', targetCalendar?.name, 'ID:', targetCalendar?.id);
      } else if (!targetCalendar) {
        console.log('❌ Priority 3 - No enabled calendars found');
      }
      
      if (!targetCalendar || !targetCalendar.url) {
        console.error('❌ No calendar available for event creation');
        alert('No calendar available for event creation. Please select a calendar.');
        return;
      }
      
      console.log('🎯 Final target calendar:', targetCalendar.name, 'URL:', targetCalendar.url);

      const eventData = {
        title: this.newEvent.summary, // Map summary to title for backend
        description: this.newEvent.description,
        location: this.newEvent.location,
        start_time: this.newEvent.all_day 
          ? dayjs(this.newEvent.start_date).format('YYYY-MM-DD') + 'T00:00:00'
          : dayjs(`${this.newEvent.start_date}T${this.newEvent.start_time}`).format('YYYY-MM-DDTHH:mm:ss'),
        end_time: this.newEvent.all_day
          ? dayjs(this.newEvent.end_date).format('YYYY-MM-DD') + 'T23:59:59'
          : dayjs(`${this.newEvent.end_date}T${this.newEvent.end_time}`).format('YYYY-MM-DDTHH:mm:ss'),
        all_day: this.newEvent.all_day,
        attendees: this.newEvent.attendees,
        reminder: this.newEvent.reminder,
        calendar_url: targetCalendar.url  // Pass the target calendar URL
      };

      console.log('Creating event in calendar:', targetCalendar.name, 'with URL:', targetCalendar.url);
      console.log('Event data being sent:', eventData);
      
      const response = await this.http.post<any>('http://localhost:8000/events', eventData, {
        withCredentials: true
      }).toPromise();
      
      if (response.success) {
        console.log('✅ Event created successfully in calendar:', targetCalendar.name);
        console.log('Event response:', response.data);
        this.closeAddEventModal();
        await this.fetchEvents(); // Refresh events
      } else {
        console.error('❌ Failed to create event:', response.message);
      }
    } catch (error) {
      console.error('Error creating event:', error);
    }
  }

  // Reset new event form
  private resetNewEvent(): void {
    // Find the first enabled calendar as default, but don't force it
    const defaultCalendar = this.calendars.find(cal => cal.enabled);
    
    this.newEvent = {
      summary: '',
      location: '',
      description: '',
      start_date: '',
      start_time: '',
      end_date: '',
      end_time: '',
      all_day: false,
      calendar_id: defaultCalendar ? defaultCalendar.id : undefined, // Set default but allow user to change
      reminder: {
        enabled: false,
        type: 'message',
        time: 15,
        unit: 'minutes',
        relativeTo: 'start'
      },
      attendees: []
    };
    
    console.log('🔄 Reset new event form. Default calendar:', defaultCalendar?.name, 'ID:', defaultCalendar?.id, 'Type:', typeof defaultCalendar?.id);
    console.log('🔄 Set calendar_id to:', this.newEvent.calendar_id, 'Type:', typeof this.newEvent.calendar_id);
  }

  // Delete event handler
  async onDeleteEvent(event: CalendarEvent): Promise<void> {
    try {
      console.log('🗑️ Delete event requested:', event);
      console.log('🗑️ Event ID:', event.id);
      console.log('🗑️ Event UID:', event.uid);
      console.log('🗑️ Event Title:', event.title);
      console.log('🗑️ Event Calendar:', event.calendar_name, 'URL:', event.calendar_url);
      
      // Use the event's calendar URL instead of selectedCalendar
      if (!event.calendar_url) {
        console.error('❌ No calendar URL found for event');
        alert('Cannot delete event: No calendar information available.');
        return;
      }

      // Use UID for deletion (the actual CalDAV identifier) instead of generated ID
      const eventIdentifier = event.uid || event.id;
      console.log('🗑️ Using identifier for deletion:', eventIdentifier, '(UID:', event.uid, 'ID:', event.id, ')');
      
      // Build the delete URL with the event's calendar URL
      const deleteUrl = `http://localhost:8000/events/${eventIdentifier}?calendar_url=${encodeURIComponent(event.calendar_url)}`;
      
      console.log('🗑️ Deleting event from URL:', deleteUrl);
      console.log('🗑️ Event calendar URL:', event.calendar_url);
      
      const response = await this.http.delete<any>(deleteUrl, { withCredentials: true }).toPromise();
      
      console.log('🗑️ Delete response:', response);
      
      if (response.success) {
        // Remove the event from the local events array using the same identifier
        this.events = this.events.filter(e => (e.uid || e.id) !== eventIdentifier);
        console.log('✅ Event deleted successfully from local array');
        
        // Close the modal
        this.closeEventDetailModal();
        
        // Show success message
        alert('Event deleted successfully!');
      } else {
        console.error('❌ Delete failed:', response.message);
        alert('Failed to delete event: ' + (response.message || 'Unknown error'));
      }
    } catch (error) {
      console.error('❌ Error deleting event:', error);
      alert('Error deleting event. Please try again.');
    }
  }

  // Get events for a specific day
  getEventsForDay(date: Date): CalendarEvent[] {
    return this.events.filter(event => {
      const eventDate = dayjs(event.start_time);
      const targetDate = dayjs(date);
      return eventDate.isSame(targetDate, 'day');
    });
  }

  // Get events for a specific time slot
  getEventsForTimeSlot(date: Date, hour: number): CalendarEvent[] {
    return this.events.filter(event => {
      const eventDate = dayjs(event.start_time);
      const targetDate = dayjs(date);
      return eventDate.isSame(targetDate, 'day') && eventDate.hour() === hour;
    });
  }

  // Format time for display
  formatTime(time: string, isAllDay?: boolean): string {
    if (isAllDay) {
      return 'All Day';
    }
    return dayjs(time).format('h:mm A');
  }
}
