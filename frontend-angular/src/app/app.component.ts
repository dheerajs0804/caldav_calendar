import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { Observable, interval, Subscription } from 'rxjs';
import * as dayjs from 'dayjs';
import { DayViewComponent } from './components/day-view/day-view.component';
import { WeekViewComponent } from './components/week-view/week-view.component';
import { MonthViewComponent } from './components/month-view/month-view.component';
import { EventDetailModalComponent } from './components/event-detail-modal/event-detail-modal.component';
import { SortByStartTimePipe } from './pipes/sort-by-start-time.pipe';
import { EmailService } from './services/email.service';
import { AuthService, User } from './services/auth.service';
import { LoginComponent } from './components/login.component';
import { ActivatedRoute } from '@angular/router';

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
  selector: 'app-root',
  standalone: true,
  imports: [CommonModule, FormsModule, DayViewComponent, WeekViewComponent, MonthViewComponent, EventDetailModalComponent, SortByStartTimePipe, LoginComponent],
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss']
})
export class AppComponent implements OnInit, OnDestroy {
  views: View[] = [
    { type: 'day', label: 'Day', icon: '📅' },
    { type: 'week', label: 'Week', icon: '📅' },
    { type: 'month', label: 'Month', icon: '📅' },
    { type: 'agenda', label: 'Agenda', icon: '📋' }
  ];

  currentView: View = this.views[1]; // Start with week view
  currentUser: User | null = null;
  isAuthenticated = false;
  currentDate: Date = new Date();
  calendars: Calendar[] = [];
  events: Event[] = [];
  loading: boolean = true;
  error: string | null = null;
  showAddEventModal: boolean = false;
  showEventDetailModal: boolean = false;
  selectedEvent: Event | null = null;
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

  constructor(private http: HttpClient, private emailService: EmailService, private authService: AuthService, private route: ActivatedRoute) {}

  ngOnInit(): void {
    console.log('🚀 App component is loading...');
    console.log('🚀 Current URL:', window.location.href);
    console.log('🚀 Query string:', window.location.search);
    
    // Check for auto-login parameters from Roundcube
    this.route.queryParams.subscribe(params => {
      console.log('🔍 URL Parameters detected:', params);
      console.log('🔍 All keys:', Object.keys(params));
      console.log('🔍 SSO Token:', params['sso_token']);
      
      const username = params['username'];
      const password = params['password'];
      const ssoToken = params['sso_token'];
      
      if (ssoToken) {
        console.log('🎯 SSO login attempt from Roundcube with token:', ssoToken);
        this.authService.ssoLogin(ssoToken).subscribe({
          next: (response) => {
            console.log('🎯 SSO login response:', response);
            if (response.success) {
              console.log('✅ SSO login successful');
              // Clear URL parameters after successful login
              window.history.replaceState({}, document.title, window.location.pathname);
              
              // Retry fetching calendars and events after successful SSO login
              console.log('🔄 Retrying data fetch after SSO authentication...');
              this.fetchCalendars();
              this.fetchEvents();
            } else {
              console.error('❌ SSO login failed:', response.message);
            }
          },
          error: (error) => {
            console.error('❌ SSO login error:', error);
          }
        });
      } else if (username && password) {
        console.log('Auto-login attempt from Roundcube for user:', username);
        this.authService.autoLogin(username, password).subscribe({
          next: (response) => {
            if (response.success) {
              console.log('Auto-login successful');
              // Clear URL parameters after successful login
              window.history.replaceState({}, document.title, window.location.pathname);
            } else {
              console.error('Auto-login failed:', response.message);
            }
          },
          error: (error) => {
            console.error('Auto-login error:', error);
          }
        });
      }
    });
    
    // Check authentication status
    this.authService.currentUser.subscribe(user => {
      this.currentUser = user;
      this.isAuthenticated = !!user;
      
      // Always try to fetch calendars and events, regardless of authentication status
      // The backend will handle authentication via SSO tokens or session
      this.fetchCalendars();
      this.fetchEvents();
    });
    
    // Check auth status on startup
    this.authService.checkAuthStatus();
  }

  logout(): void {
    this.authService.logout().subscribe({
      next: (response) => {
        if (response.success) {
          this.currentUser = null;
          this.isAuthenticated = false;
          this.events = [];
          this.calendars = [];
          console.log('Logged out successfully');
        }
      },
      error: (error) => {
        console.error('Logout error:', error);
      }
    });
  }

  ngOnDestroy(): void {
    if (this.reminderInterval) {
      this.reminderInterval.unsubscribe();
    }
  }

  clearNotifiedEvents(): void {
    this.notifiedEvents.clear();
    console.log('Cleared notified events');
    alert('Notified events cleared!');
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
      const response = await this.http.get<any>('http://localhost:8001/calendars', { withCredentials: true }).toPromise();
      
      if (response.success) {
        this.calendars = response.data;
        console.log('Calendars loaded:', response.data);
        await this.fetchEvents();
      } else {
        console.log('Failed to load calendars - might need authentication');
        this.error = 'Failed to load calendars';
      }
    } catch (error: any) {
      console.error('Error fetching calendars:', error);
      if (error.status === 401) {
        console.log('Authentication required - calendar will show login if needed');
      } else {
        this.error = 'Error connecting to backend';
      }
    } finally {
      this.loading = false;
    }
  }

  // Fetch events when calendars change
  async fetchEvents(): Promise<void> {
    try {
              const response = await this.http.get<any>('http://localhost:8001/events', { withCredentials: true }).toPromise();
      
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
      this.checkReminders();
    });
  }

  // Function to manually refresh events from server
  async refreshEvents(): Promise<void> {
    try {
      console.log('Refreshing events from server...');
      // Clear notified events when refreshing to allow reminders to show again
      this.notifiedEvents.clear();
      console.log('Cleared notified events for fresh reminder check');
      await this.fetchEvents();
      alert('Events refreshed from server!');
    } catch (error) {
      console.error('Error refreshing events:', error);
      alert('Failed to refresh events');
    }
  }

  // Function to clear local storage and refresh from server
  async clearLocalStorage(): Promise<void> {
    try {
      console.log('Clearing local storage...');
      
      // Clear the events from state
      this.events = [];
      
      // Make a request to clear local storage on backend
              const response = await this.http.post('http://localhost:8001/events/clear-local', {}, { withCredentials: true }).toPromise();
      
      alert('Local storage cleared! Now refreshing events from server...');
      // Refresh events from server
      await this.refreshEvents();
    } catch (error) {
      console.error('Error clearing local storage:', error);
      alert('Failed to clear local storage');
    }
  }

  // Function to force sync from server (completely fresh)
  async forceSyncFromServer(): Promise<void> {
    try {
      console.log('Force syncing from server...');
      
      // Clear notified events for fresh reminder check
      this.notifiedEvents.clear();
      console.log('Cleared notified events for fresh reminder check');
      
      // Clear local storage first
      await this.clearLocalStorage();
      
      // Then sync with CalDAV to get fresh events
      await this.syncCalDAV();
      
      alert('Force sync completed! Events are now fresh from the server.');
    } catch (error) {
      console.error('Error during force sync:', error);
      alert('Error during force sync');
    }
  }

  // Function to delete an event
  async deleteEvent(eventId: string): Promise<void> {
    try {
      console.log('Deleting event:', eventId);
      
              const response = await this.http.delete(`http://localhost:8001/events/${eventId}`, { withCredentials: true }).toPromise();
      
      // Remove event from local state - match by either ID or UID
      this.events = this.events.filter(event => 
        event.id !== eventId && event.uid !== eventId
      );
      console.log('Event deleted successfully');
      alert('Event deleted successfully!');
    } catch (error) {
      console.error('Error deleting event:', error);
      alert(`Error deleting event: ${error}`);
    }
  }

  // Function to confirm event deletion
  confirmDeleteEvent(event: Event): void {
    const message = `Are you sure you want to delete "${event.title}"?\n\nThis action cannot be undone.`;
    if (window.confirm(message)) {
      // Use UID instead of ID for CalDAV server compatibility
      this.deleteEvent(event.uid || event.id);
    }
  }

  async syncCalDAV(): Promise<void> {
    try {
      console.log('Syncing with CalDAV...');
      
      // First check CalDAV status
              const statusResponse = await this.http.get<any>('http://localhost:8001/caldav/status').toPromise();
      console.log('CalDAV Status:', statusResponse);
      
      // Then try to discover calendars
              const discoverResponse = await this.http.post<any>('http://localhost:8001/caldav/discover', {}, { withCredentials: true }).toPromise();
      console.log('CalDAV Discovery:', discoverResponse);
      
      if (discoverResponse.success) {
        // Update calendars with discovered CalDAV calendars
        if (discoverResponse.data && discoverResponse.data.calendars) {
          const caldavCalendars = discoverResponse.data.calendars.map((cal: any, index: number) => ({
            id: index + 1,
            name: cal.name || `Google Calendar ${index + 1}`,
            color: cal.color || '#4285f4',
            url: cal.url,
            userId: 1,
            isActive: true,
            syncToken: `caldav-sync-${Date.now()}`,
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString()
          }));
          
          this.calendars = caldavCalendars;
          console.log('Updated calendars with CalDAV data:', caldavCalendars);
          
          // Try to fetch events from the first calendar
          if (caldavCalendars.length > 0) {
            const firstCalendar = caldavCalendars[0];
            try {
              const eventsResponse = await this.http.get<any>(`http://localhost:8001/caldav/events/${firstCalendar.id}`).toPromise();
              if (eventsResponse.success) {
                // Remove duplicate events using utility function
                const uniqueEvents = this.removeDuplicateEvents(eventsResponse.data);
                
                // Debug: Log CalDAV events before and after deduplication
                console.log('🔍 CALDAV EVENTS DEBUG - Before deduplication:', eventsResponse.data);
                console.log('🔍 CALDAV EVENTS DEBUG - After deduplication:', uniqueEvents);
                console.log('🔍 CALDAV EVENTS DEBUG - Duplicates found:', eventsResponse.data.length - uniqueEvents.length);
                
                this.events = uniqueEvents;
                console.log('Events loaded from CalDAV:', uniqueEvents);
              }
            } catch (eventsError) {
              console.log('Could not fetch events from CalDAV, using mock data');
            }
          }
        }
      } else {
        console.error('CalDAV discovery failed:', discoverResponse.message);
      }
    } catch (error) {
      console.error('Error syncing with CalDAV:', error);
    }
  }

  async handleAddEvent(): Promise<void> {
    try {
      // Create event object in the format expected by backend
      const eventData = {
        title: this.newEvent.summary,
        description: this.newEvent.description,
        location: this.newEvent.location,
        start_time: this.newEvent.all_day 
          ? `${this.newEvent.start_date}T00:00:00` 
          : `${this.newEvent.start_date}T${this.newEvent.start_time}:00`,
        end_time: this.newEvent.all_day 
          ? `${this.newEvent.end_date}T23:59:59` 
          : `${this.newEvent.end_date}T${this.newEvent.end_time}:00`,
        all_day: this.newEvent.all_day,
        calendar_id: this.calendars.length > 0 ? this.calendars[0].id : 1,
        // Convert reminder to CalDAV VALARM format
        valarm: this.newEvent.reminder.enabled ? {
          trigger: this.generateVALARMTrigger(this.newEvent.reminder),
          action: 'DISPLAY',
          description: `Reminder: ${this.newEvent.summary}`
        } : null,
        attendees: this.newEvent.attendees.map(attendee => ({
          email: attendee.email,
          name: attendee.name,
          response: attendee.response,
          role: attendee.role
        }))
      };

      console.log('Sending event data:', eventData);

              this.http.post<any>('http://localhost:8001/events', eventData, { withCredentials: true }).subscribe({
        next: (response) => {
          console.log('Backend response:', response);
          
          if (response.success) {
            console.log('Event created successfully:', response.data);
            console.log('Event reminder data:', response.data.reminder);
            console.log('Event VALARM data:', response.data.valarm);
            
            // Add the new event to the current events list, ensuring no duplicates
            const newEvents = [...this.events, response.data];
            this.events = this.removeDuplicateEvents(newEvents);
            console.log('Updated events list:', this.events);
            console.log('Events with reminders:', this.events.filter(e => (e.reminder && e.reminder.enabled) || e.valarm));
            
            // Check if this new event has a reminder that should trigger immediately
            if (response.data.reminder && response.data.reminder.enabled || response.data.valarm) {
              console.log('New event has reminder, checking if it should trigger immediately...');
              // Use setTimeout to ensure the event is fully added to state before checking
              setTimeout(() => {
                // Force immediate check for this specific event
                const now = new Date();
                const eventStart = new Date(response.data.start_time);
                const eventEnd = new Date(response.data.end_time);
                
                let reminderTime;
                if (response.data.valarm) {
                  reminderTime = this.calculateVALARMTime(response.data.valarm, eventStart, eventEnd);
                } else if (response.data.reminder && response.data.reminder.enabled) {
                  reminderTime = this.calculateLegacyReminderTime(response.data.reminder, eventStart, eventEnd);
                }
                
                if (reminderTime) {
                  const timeDiff = now.getTime() - reminderTime.getTime();
                  console.log(`Immediate check for new event "${response.data.title}": reminder time ${reminderTime.toLocaleString()}, now ${now.toLocaleString()}, diff ${timeDiff}ms`);
                  
                  // Only trigger immediately if reminder is overdue (timeDiff > 0)
                  if (timeDiff > 0) {
                    console.log(`🚨 IMMEDIATE TRIGGER for new event "${response.data.title}" - OVERDUE by ${Math.floor(timeDiff / 60000)} minutes`);
                    this.showReminderNotification(response.data);
                    this.notifiedEvents.add(response.data.id);
                  } else {
                    console.log(`New event reminder not overdue, will be checked in regular interval`);
                  }
                }
              }, 100);
            }
            
            // Close modal and reset form
            this.showAddEventModal = false;
            this.resetNewEvent();
            // Show success message
            alert('Event created successfully!');
          } else {
            console.error('Failed to create event:', response.message);
            alert(`Failed to create event: ${response.message}`);
          }
        },
        error: (error) => {
          console.error('Error creating event:', error);
          alert(`Error creating event: ${error.message || error}`);
        }
      });
    } catch (error) {
      console.error('Error in handleAddEvent:', error);
      alert(`Error creating event: ${error}`);
    }
  }

  handleInputChange(field: string, value: any): void {
    if (field === 'reminder') {
      this.newEvent.reminder = value;
    } else if (field === 'attendees') {
      this.newEvent.attendees = value;
    } else {
      (this.newEvent as any)[field] = value;
    }
  }

  // Attendee management methods
  addAttendee(): void {
    this.newEvent.attendees.push({
      email: '',
      name: '',
      response: 'pending',
      role: 'required'
    });
  }

  removeAttendee(index: number): void {
    this.newEvent.attendees.splice(index, 1);
  }

  updateAttendee(index: number, field: string, value: any): void {
    this.newEvent.attendees[index] = {
      ...this.newEvent.attendees[index],
      [field]: value
    };
  }

  downloadCalendarFile(): void {
    if (this.newEvent.attendees && this.newEvent.attendees.length > 0) {
      // Filter out attendees without email addresses
      const validAttendees = this.newEvent.attendees.filter(a => a.email && a.email.trim());
      
      if (validAttendees.length === 0) {
        alert('No valid email addresses found for attendees.');
        return;
      }

      // Prepare event details for the calendar file
      const eventDetails = {
        title: this.newEvent.summary,
        description: this.newEvent.description,
        location: this.newEvent.location,
        startTime: this.newEvent.start_time,
        endTime: this.newEvent.end_time,
        allDay: this.newEvent.all_day,
        organizer: 'organizer@example.com' // TODO: Get from user profile
      };

      // Download the iCalendar file
      this.emailService.downloadICalendar(validAttendees, eventDetails);
    } else {
      alert('No attendees found for this event.');
    }
  }

  // Initialize new event with current date when modal opens
  openAddEventModal(): void {
    this.showAddEventModal = true;
    const now = new Date();
    const currentTime = now.toTimeString().slice(0, 5);
    const endTime = new Date(now.getTime() + 60 * 60 * 1000).toTimeString().slice(0, 5);
    
    this.newEvent = {
      summary: '',
      location: '',
      description: '',
      start_date: now.toISOString().split('T')[0],
      start_time: currentTime,
      end_date: now.toISOString().split('T')[0],
      end_time: endTime,
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

  // Check for reminders and show notifications
  checkReminders(): void {
    const now = new Date();
    console.log('Checking reminders at:', now.toLocaleString());
    
    console.log(`Total events to check: ${this.events.length}`);
    console.log(`Already notified events:`, Array.from(this.notifiedEvents));
    
    this.events.forEach(event => {
      console.log(`Checking event: "${event.title}" - Has reminder: ${!!(event.reminder && event.reminder.enabled)}, Has VALARM: ${!!event.valarm}`);
      
      // Check for both old reminder format and new VALARM format
      const hasReminder = (event.reminder && event.reminder.enabled) || event.valarm;
      
      if (hasReminder) {
        // Skip if we've already notified for this event recently
        if (this.notifiedEvents.has(event.id)) {
          console.log(`Skipping ${event.title} - already notified`);
          return;
        }
        
        console.log(`Checking reminder for event: ${event.title}`);
        console.log(`Event reminder data:`, event.reminder);
        console.log(`Event VALARM data:`, event.valarm);
        
        const eventStart = new Date(event.start_time);
        const eventEnd = new Date(event.end_time);
        
        // Calculate when reminder should trigger
        let reminderTime;
        
        if (event.valarm) {
          // Use CalDAV VALARM data
          console.log(`Using VALARM data for ${event.title}`);
          reminderTime = this.calculateVALARMTime(event.valarm, eventStart, eventEnd);
        } else if (event.reminder && event.reminder.enabled) {
          // Use old reminder format (for backward compatibility)
          console.log(`Using legacy reminder data for ${event.title}`);
          reminderTime = this.calculateLegacyReminderTime(event.reminder, eventStart, eventEnd);
        }
        
        if (!reminderTime) {
          console.log(`No reminder time calculated for ${event.title}`);
          return;
        }
        
        console.log(`Event: ${event.title}, Reminder time: ${reminderTime.toLocaleString()}, Now: ${now.toLocaleString()}`);
        
        // Check if reminder should trigger now
        const timeDiff = now.getTime() - reminderTime.getTime();
        const minutesDiff = Math.floor(Math.abs(timeDiff) / 60000);
        
        if (timeDiff > 0) {
          console.log(`🚨 REMINDER OVERDUE: ${event.title} - reminder was due ${minutesDiff} minutes ago!`);
        } else if (timeDiff >= -300000) {
          console.log(`⏰ Reminder due soon: ${event.title} - will trigger in ${minutesDiff} minutes`);
        } else {
          console.log(`⏳ Reminder not due yet: ${event.title} - will trigger in ${minutesDiff} minutes`);
        }
        
        // Trigger reminder if:
        // 1. Reminder is overdue (timeDiff > 0) - trigger immediately
        // 2. Reminder is due within 5 minutes (timeDiff <= 300000) - trigger now
        if (timeDiff > 0 || timeDiff >= -300000) {
          console.log(`Triggering reminder for: ${event.title} - ${timeDiff > 0 ? 'OVERDUE' : 'due soon'}`);
          this.showReminderNotification(event);
          // Mark this event as notified to prevent duplicate notifications
          this.notifiedEvents.add(event.id);
          console.log(`Marked ${event.title} as notified. Total notified: ${this.notifiedEvents.size}`);
        } else {
          console.log(`Reminder for ${event.title} not due yet. Will trigger in ${minutesDiff} minutes.`);
        }
      } else {
        console.log(`Event "${event.title}" has no reminder configured`);
      }
    });
  }

  // Show reminder notification
  showReminderNotification(event: Event): void {
    // Check if browser supports notifications
    if (!('Notification' in window)) {
      // Fallback: show alert
      alert(`Reminder: ${event.title} is starting soon!`);
      return;
    }

    // Request permission if not granted
    if (Notification.permission === 'default') {
      Notification.requestPermission().then(permission => {
        if (permission === 'granted') {
          this.createNotification(event);
        }
      });
    } else if (Notification.permission === 'granted') {
      this.createNotification(event);
    }
  }

  // Test function to show a notification immediately
  testNotification(): void {
    console.log('Testing notification...');
    
    // Check if browser supports notifications
    if (!('Notification' in window)) {
      alert('This browser does not support notifications');
      return;
    }

    console.log('Current notification permission:', Notification.permission);
    console.log('Notification in window:', 'Notification' in window);

    // Request permission if not granted
    if (Notification.permission === 'default') {
      console.log('Requesting notification permission...');
      Notification.requestPermission().then(permission => {
        console.log('Permission result:', permission);
        if (permission === 'granted') {
          this.showTestNotification();
        } else {
          alert('Notification permission denied');
        }
      });
    } else if (Notification.permission === 'granted') {
      console.log('Permission already granted, showing test notification...');
      this.showTestNotification();
    } else {
      console.log('Permission denied, showing alert...');
      alert('Notification permission denied. Please enable notifications in your browser settings.');
    }
  }

  showTestNotification(): void {
    const testEvent = {
      id: 'test',
      title: 'Test Reminder',
      description: 'This is a test notification to verify the reminder system is working!',
      start_time: new Date().toISOString(),
      end_time: new Date(Date.now() + 3600000).toISOString(),
      location: 'Test Location'
    } as Event;
    
    this.createNotification(testEvent);
  }

  // Create and show the notification
  createNotification(event: Event): void {
    try {
      const eventStart = new Date(event.start_time);
      const eventEnd = new Date(event.end_time);
      
      console.log('Creating notification for event:', event.title);
      
      // Try browser notification first
      if ('Notification' in window && Notification.permission === 'granted') {
        console.log('Notification permission granted, creating browser notification...');
        
        const notification = new Notification(`Reminder: ${event.title}`, {
          body: `${event.description || 'No description'}\n\nStart: ${eventStart.toLocaleString()}\nEnd: ${eventEnd.toLocaleString()}\nLocation: ${event.location || 'No location'}`,
          icon: '/favicon.ico',
          tag: `event-${event.id}`,
          requireInteraction: true,
          silent: false // Ensure sound plays
        });

        console.log('Browser notification created successfully');

        notification.onclick = () => {
          console.log('Notification clicked, focusing window...');
          window.focus();
          notification.close();
        };

        notification.onerror = (error) => {
          console.error('Notification error:', error);
          // Fallback to alert if notification fails
          this.showAlertReminder(event);
        };

        // Also show alert as backup to ensure user sees it
        console.log('Showing backup alert...');
        this.showAlertReminder(event);

        setTimeout(() => {
          notification.close();
        }, 10000);
      } else {
        // Fallback to alert if notifications not available
        console.log('Notifications not available, showing alert...');
        this.showAlertReminder(event);
      }
       
     } catch (error) {
       console.error('Error creating notification:', error);
       // Fallback to alert
       this.showAlertReminder(event);
     }
   }

     // Fallback reminder using browser alert
  showAlertReminder(event: Event): void {
    const eventStart = new Date(event.start_time);
    const eventEnd = new Date(event.end_time);
    
    const message = `🔔 REMINDER: ${event.title}\n\n` +
                   `${event.description || 'No description'}\n\n` +
                   `Start: ${eventStart.toLocaleString()}\n` +
                   `End: ${eventEnd.toLocaleString()}\n` +
                   `Location: ${event.location || 'No location'}`;
    
    alert(message);
    console.log('Alert reminder shown for:', event.title);
  }

  // Check notification status and provide troubleshooting info
  checkNotificationStatus(): void {
    console.log('=== NOTIFICATION STATUS CHECK ===');
    console.log('Browser supports notifications:', 'Notification' in window);
    console.log('Current permission:', Notification.permission);
    console.log('User agent:', navigator.userAgent);
    console.log('Platform:', navigator.platform);
    console.log('Do not disturb:', navigator.doNotTrack);
    
    let statusMessage = '=== NOTIFICATION STATUS ===\n\n';
    statusMessage += `Browser supports notifications: ${'Notification' in window ? 'YES' : 'NO'}\n`;
    statusMessage += `Current permission: ${Notification.permission}\n`;
    statusMessage += `Platform: ${navigator.platform}\n`;
    statusMessage += `User Agent: ${navigator.userAgent}\n\n`;
    
    if (!('Notification' in window)) {
      statusMessage += '❌ This browser does not support notifications\n';
    } else if (Notification.permission === 'denied') {
      statusMessage += '❌ Notifications are blocked by browser\n';
      statusMessage += '💡 To fix: Click the lock/info icon in address bar and allow notifications\n';
    } else if (Notification.permission === 'default') {
      statusMessage += '⚠️ Notification permission not yet requested\n';
      statusMessage += '💡 Click "Test Notification" to request permission\n';
    } else if (Notification.permission === 'granted') {
      statusMessage += '✅ Notifications are enabled!\n';
      statusMessage += '💡 If you still don\'t see them, check:\n';
      statusMessage += '   - Browser focus (notifications may be hidden)\n';
      statusMessage += '   - System notification settings\n';
      statusMessage += '   - Do Not Disturb mode\n';
    }
    
    alert(statusMessage);
    console.log('Status check complete');
  }

  changeView(viewType: string): void {
    const view = this.views.find(v => v.type === viewType);
    if (view) {
      this.currentView = view;
    }
  }

  goToPrevious(): void {
    const newDate = new Date(this.currentDate);
    switch (this.currentView.type) {
      case 'day':
        newDate.setDate(newDate.getDate() - 1);
        break;
      case 'week':
        newDate.setDate(newDate.getDate() - 7);
        break;
      case 'month':
        newDate.setMonth(newDate.getMonth() - 1);
        break;
      default:
        newDate.setDate(newDate.getDate() - 1);
    }
    this.currentDate = newDate;
  }

  goToNext(): void {
    const newDate = new Date(this.currentDate);
    switch (this.currentView.type) {
      case 'day':
        newDate.setDate(newDate.getDate() + 1);
        break;
      case 'week':
        newDate.setDate(newDate.getDate() + 7);
        break;
      case 'month':
        newDate.setMonth(newDate.getMonth() + 1);
        break;
      default:
        newDate.setDate(newDate.getDate() + 1);
    }
    this.currentDate = newDate;
  }

  goToToday(): void {
    this.currentDate = new Date();
  }

  // Helper functions for calendar views
  getWeekDays(): Date[] {
    const days = [];
    const startOfWeek = new Date(this.currentDate);
    startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay());
    
    for (let i = 0; i < 7; i++) {
      const day = new Date(startOfWeek);
      day.setDate(startOfWeek.getDate() + i);
      days.push(day);
    }
    return days;
  }

  getMonthDays(): Date[] {
    const year = this.currentDate.getFullYear();
    const month = this.currentDate.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());
    
    const days = [];
    const current = new Date(startDate);
    
    while (current <= lastDay || current.getDay() !== 0) {
      days.push(new Date(current));
      current.setDate(current.getDate() + 1);
    }
    
    return days;
  }

  getEventsForDate(date: Date): Event[] {
    return this.events.filter(event => {
      const eventDate = new Date(event.start_time);
      return eventDate.toDateString() === date.toDateString();
    });
  }

  getEventsForTimeSlot(date: Date, hour: number): Event[] {
    return this.events.filter(event => {
      const eventDate = new Date(event.start_time);
      return eventDate.toDateString() === date.toDateString() && 
             eventDate.getHours() === hour;
    });
  }

  formatTime(hour: number): string {
    return hour === 0 ? '12 AM' : hour === 12 ? '12 PM' : hour > 12 ? `${hour - 12} PM` : `${hour} AM`;
  }

  // Generate CalDAV VALARM trigger string (e.g., -PT15M for 15 minutes before)
  generateVALARMTrigger(reminder: any): string {
    const { time, unit, relativeTo } = reminder;
    
    // Convert to ISO 8601 duration format
    let duration = '';
    
    switch (unit) {
      case 'minutes':
        duration = `PT${time}M`;
        break;
      case 'hours':
        duration = `PT${time}H`;
        break;
      case 'days':
        duration = `P${time}D`;
        break;
      case 'minutes_after':
        duration = `PT${time}M`;
        break;
      case 'hours_after':
        duration = `PT${time}H`;
        break;
      case 'days_after':
        duration = `P${time}D`;
        break;
      case 'on_time':
        duration = 'PT0M'; // At exact time
        break;
      case 'on_date':
        duration = 'PT9H'; // 9 AM on the date
        break;
      default:
        duration = `PT${time}M`;
    }
    
    // Add minus sign for "before" triggers
    if (relativeTo === 'start' && !unit.includes('after') && unit !== 'on_time' && unit !== 'on_date') {
      duration = `-${duration}`;
    }
    
    return duration;
  }

  // Calculate reminder time from CalDAV VALARM data
  calculateVALARMTime(valarm: any, eventStart: Date, eventEnd: Date): Date | null {
    if (!valarm.trigger) return null;
    
    const trigger = valarm.trigger;
    let reminderTime = new Date(eventStart); // Default to start time
    
    console.log(`Calculating VALARM time for trigger: "${trigger}"`);
    console.log(`Event start: ${eventStart.toLocaleString()}`);
    
    // Parse ISO 8601 duration (e.g., -PT15M, PT1H, P1D)
    if (trigger.startsWith('-')) {
      // Negative duration means "before"
      const duration = trigger.substring(1);
      console.log(`Negative duration detected: "${duration}", calculating reminder before event start`);
      reminderTime = this.subtractDuration(eventStart, duration);
    } else if (trigger.startsWith('PT')) {
      // Positive duration means "after"
      const duration = trigger;
      console.log(`Positive duration detected: "${duration}", calculating reminder after event start`);
      reminderTime = this.addDuration(eventStart, duration);
    } else if (trigger.startsWith('P') && !trigger.includes('T')) {
      // Days only (e.g., P1D)
      const days = parseInt(trigger.substring(1));
      console.log(`Days duration detected: ${days} days before event start`);
      reminderTime.setDate(reminderTime.getDate() - days);
    }
    
    console.log(`Calculated reminder time: ${reminderTime.toLocaleString()}`);
    return reminderTime;
  }

  // Calculate reminder time from legacy reminder format
  calculateLegacyReminderTime(reminder: any, eventStart: Date, eventEnd: Date): Date | null {
    let reminderTime;
    if (reminder.relativeTo === 'start') {
      reminderTime = new Date(eventStart);
    } else {
      reminderTime = new Date(eventEnd);
    }
    
    console.log(`Calculating legacy reminder time for: ${reminder.time} ${reminder.unit} ${reminder.relativeTo === 'start' ? 'before start' : 'before end'}`);
    console.log(`Base time: ${reminderTime.toLocaleString()}`);
    
    // Adjust reminder time based on unit
    switch (reminder.unit) {
      case 'minutes':
        reminderTime.setMinutes(reminderTime.getMinutes() - reminder.time);
        console.log(`Subtracted ${reminder.time} minutes`);
        break;
      case 'hours':
        reminderTime.setHours(reminderTime.getHours() - reminder.time);
        console.log(`Subtracted ${reminder.time} hours`);
        break;
      case 'days':
        reminderTime.setDate(reminderTime.getDate() - reminder.time);
        console.log(`Subtracted ${reminder.time} days`);
        break;
      case 'minutes_after':
        reminderTime.setMinutes(reminderTime.getMinutes() + reminder.time);
        console.log(`Added ${reminder.time} minutes`);
        break;
      case 'hours_after':
        reminderTime.setHours(reminderTime.getHours() + reminder.time);
        console.log(`Added ${reminder.time} hours`);
        break;
      case 'days_after':
        reminderTime.setDate(reminderTime.getDate() + reminder.time);
        console.log(`Added ${reminder.time} days`);
        break;
      case 'on_time':
        // Reminder at exact time
        console.log(`Reminder at exact time`);
        break;
      case 'on_date':
        // Reminder on the date (at 9 AM)
        reminderTime.setHours(9, 0, 0, 0);
        console.log(`Reminder at 9 AM on the date`);
        break;
    }
    
    console.log(`Final reminder time: ${reminderTime.toLocaleString()}`);
    return reminderTime;
  }

  // Helper function to subtract duration from a date
  subtractDuration(date: Date, duration: string): Date {
    const newDate = new Date(date);
    console.log(`Subtracting duration "${duration}" from date ${date.toLocaleString()}`);
    
    if (duration.includes('H')) {
      const hours = parseInt(duration.replace('PT', '').replace('H', ''));
      console.log(`Subtracting ${hours} hours`);
      newDate.setHours(newDate.getHours() - hours);
    } else if (duration.includes('M')) {
      const minutes = parseInt(duration.replace('PT', '').replace('M', ''));
      console.log(`Subtracting ${minutes} minutes`);
      newDate.setMinutes(newDate.getMinutes() - minutes);
    } else if (duration.includes('D')) {
      const days = parseInt(duration.replace('P', '').replace('D', ''));
      console.log(`Subtracting ${days} days`);
      newDate.setDate(newDate.getDate() - days);
    }
    
    console.log(`Result: ${newDate.toLocaleString()}`);
    return newDate;
  }

  // Helper function to add duration to a date
  addDuration(date: Date, duration: string): Date {
    const newDate = new Date(date);
    
    if (duration.includes('H')) {
      const hours = parseInt(duration.replace('PT', '').replace('H', ''));
      newDate.setHours(newDate.getHours() + hours);
    } else if (duration.includes('M')) {
      const minutes = parseInt(duration.replace('PT', '').replace('M', ''));
      newDate.setMinutes(newDate.getMinutes() + minutes);
    } else if (duration.includes('D')) {
      const days = parseInt(duration.replace('PT', '').replace('D', ''));
      newDate.setDate(newDate.getDate() + days);
    }
    
    return newDate;
  }

  // Utility function to remove duplicate events
  removeDuplicateEvents(events: Event[]): Event[] {
    const originalCount = events.length;
    
    // Remove duplicates based on multiple criteria
    const uniqueEvents = events.filter((event, index, self) => {
      // For test events, also check title to prevent duplicates
      if (event.title === 'Test Event with Reminder') {
        return index === self.findIndex(e => 
          e.title === event.title && e.start_time === event.start_time
        );
      }
      
      // For regular events, check multiple criteria to catch duplicates
      const isDuplicate = self.findIndex(e => {
        // Same ID is always a duplicate
        if (e.id === event.id) return true;
        
        // Same title + same start time + same end time is likely a duplicate
        if (e.title === event.title && 
            e.start_time === event.start_time && 
            e.end_time === event.end_time) return true;
        
        // Same title + very close start times (within 1 minute) might be duplicates
        if (e.title === event.title) {
          const time1 = new Date(e.start_time);
          const time2 = new Date(event.start_time);
          const timeDiff = Math.abs(time1.getTime() - time2.getTime());
          if (timeDiff < 60000) return true; // Within 1 minute
        }
        
        return false;
      });
      
      // Keep only the first occurrence
      return index === isDuplicate;
    });
    
    if (originalCount !== uniqueEvents.length) {
      console.warn(`Found ${originalCount - uniqueEvents.length} duplicate events, removed them`);
    }
    
    return uniqueEvents;
  }

  // Function to find overlapping events for Week View
  findOverlappingEvents(event: Event, dayEvents: Event[]): Event[] {
    const eventStart = new Date(event.start_time);
    const eventEnd = new Date(event.end_time);
    
    return dayEvents.filter(otherEvent => {
      if (otherEvent.id === event.id) return false;
      
      const otherStart = new Date(otherEvent.start_time);
      const otherEnd = new Date(otherEvent.end_time);
      
      // Check if events overlap in time
      return eventStart < otherEnd && eventEnd > otherStart;
    });
  }

  // Function to calculate event position for Week View
  getWeekEventPosition(event: Event, group: Event[], day: Date): any {
    const eventStart = new Date(event.start_time);
    const eventEnd = new Date(event.end_time);
    const dayStart = new Date(day);
    dayStart.setHours(0, 0, 0, 0);
    
    // Calculate position from start of day (in minutes)
    const startMinutes = (eventStart.getTime() - dayStart.getTime()) / (1000 * 60);
    const durationMinutes = (eventEnd.getTime() - eventStart.getTime()) / (1000 * 60);
    
    // Find the index of this event within its group
    const eventIndex = group.findIndex(e => e.id === event.id);
    const totalOverlapping = group.length;
    
    // Calculate width and left position for overlapping events
    let eventWidth, leftOffset;
    
    if (totalOverlapping > 1) {
      // Multiple overlapping events - divide space equally within the day column
      const singleEventWidth = Math.max(100 / totalOverlapping, 20); // Use percentage, minimum 20%
      eventWidth = `${singleEventWidth}%`;
      leftOffset = `${eventIndex * singleEventWidth}%`;
    } else {
      // Single event - use full width with small margins
      eventWidth = 'calc(100% - 8px)';
      leftOffset = '4px';
    }
    
    return {
      top: `${startMinutes}px`,
      height: `${Math.max(durationMinutes, 20)}px`,
      width: eventWidth,
      left: leftOffset
    };
  }

  // Event detail modal methods
  openEventDetail(event: Event): void {
    this.selectedEvent = event;
    this.showEventDetailModal = true;
  }

  closeEventDetail(): void {
    this.showEventDetailModal = false;
    this.selectedEvent = null;
  }

  editEvent(event: Event): void {
    console.log('Edit event:', event);
    console.log('🔍 DEBUG: Current events in frontend:', this.events);
    console.log('🔍 DEBUG: Event IDs in frontend:', this.events.map(e => ({ id: e.id, title: e.title })));
    console.log('🔍 DEBUG: Looking for event with ID:', event.id);
    
    // Check if this event actually exists in our events array
    const eventExists = this.events.find(e => e.id === event.id);
    if (!eventExists) {
      console.error('🚨 CRITICAL: Event not found in frontend events array! This indicates a synchronization issue.');
      console.error('Event to edit:', event);
      console.error('Available events:', this.events);
      
      // Try to refresh events from server first
      console.log('🔄 Attempting to refresh events from server...');
      this.fetchEvents().then(() => {
        const refreshedEventExists = this.events.find(e => e.id === event.id);
        if (refreshedEventExists) {
          console.log('✅ Event found after refresh, proceeding with edit...');
          // Recursively call editEvent with the refreshed event
          this.editEvent(refreshedEventExists);
        } else {
          console.error('❌ Event still not found after refresh. This event may not exist on the server.');
          alert('Event not found on server. Please refresh the page and try again.');
        }
      }).catch(error => {
        console.error('❌ Failed to refresh events:', error);
        alert('Failed to refresh events. Please refresh the page and try again.');
      });
      return;
    }
    
    // Prepare the event data for the backend
    const eventData = {
      title: event.title,
      description: event.description || '',
      location: event.location || '',
      start_time: event.start_time,
      end_time: event.end_time,
      all_day: event.all_day,
      calendar_id: event.calendar_id || 1,
      valarm: event.valarm || null,
      attendees: event.attendees ? event.attendees.map(attendee => ({
        email: attendee.email,
        name: attendee.name,
        response: attendee.response,
        role: attendee.role
      })) : []
    };

    console.log('Sending updated event data:', eventData);

    // Send PUT request to update the event
            this.http.put<any>(`http://localhost:8001/events/${event.id}`, eventData, { withCredentials: true }).subscribe({
      next: (response) => {
        console.log('Backend response:', response);

        if (response.success) {
          console.log('Event updated successfully:', response.data);
          
          // Update the local events array
          const updatedEvents = this.events.map(e => 
            e.id === event.id ? response.data : e
          );
          this.events = this.removeDuplicateEvents(updatedEvents);
          
          // Close the modal
          this.closeEventDetail();
          
          // Show success message
          alert('Event updated successfully!');
        } else {
          console.error('Failed to update event:', response.message);
          alert(`Failed to update event: ${response.message}`);
        }
      },
      error: (error) => {
        console.error('Error updating event:', error);
        alert(`Error updating event: ${error.message || error}`);
      }
    });
  }

  deleteEventFromModal(event: Event): void {
    // Use the existing delete functionality
    this.confirmDeleteEvent(event);
  }

  // Function to force refresh events and clear any stale data
  async forceRefreshEvents(): Promise<void> {
    try {
      console.log('🔄 Force refreshing events and clearing stale data...');
      
      // Clear current events
      this.events = [];
      
      // Fetch fresh events from server
      await this.fetchEvents();
      
      console.log('✅ Events refreshed successfully');
      console.log('Current events:', this.events);
      
      alert('Events refreshed successfully!');
    } catch (error) {
      console.error('❌ Failed to refresh events:', error);
      alert('Failed to refresh events');
    }
  }
}

