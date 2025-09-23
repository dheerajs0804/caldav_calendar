import { Component, OnInit, OnDestroy, ChangeDetectorRef } from '@angular/core';
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
import { DateNavigationComponent } from './date-navigation.component';
import { ExportModalComponent, ExportOptions } from './export-modal/export-modal.component';
import { ImportModalComponent, ImportOptions } from './import-modal/import-modal.component';
import { SortByStartTimePipe } from '../pipes/sort-by-start-time.pipe';
import { EmailService } from '../services/email.service';
import { AuthService } from '../services/auth.service';
import { Router } from '@angular/router';
import { CalendarEvent, Calendar } from '../interfaces/calendar-event.interface';
import { ColorRegistryService } from '../services/color-registry.service';
import { RecurrenceExpansionService } from '../services/recurrence-expansion.service';
import { RecurringEventDeleteModalComponent, RecurringDeleteAction } from './recurring-event-delete-modal/recurring-event-delete-modal.component';


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
  availability: 'free' | 'busy' | 'tentative';
  status: 'confirmed' | 'tentative' | 'cancelled';
  recurrence: {
    frequency: 'never' | 'daily' | 'weekly' | 'monthly' | 'annually' | 'ondates';
    interval?: number;
    count?: number;
    until?: string;
    byDay?: string[];
    byMonth?: number[];
    byMonthDay?: number[];
    bySetPos?: number;
    specificDates?: string[];
  };
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
  imports: [CommonModule, FormsModule, DayViewComponent, WeekViewComponent, MonthViewComponent, EventDetailModalComponent, ReminderNotificationComponent, DateNavigationComponent, ExportModalComponent, ImportModalComponent, SortByStartTimePipe, RecurringEventDeleteModalComponent],
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
  currentDate: dayjs.Dayjs = dayjs();
  calendars: Calendar[] = [];
  events: CalendarEvent[] = []; // Master events from backend
  expandedEvents: CalendarEvent[] = []; // Expanded events for display
  loading: boolean = true;
  error: string | null = null;
  showAddEventModal: boolean = false;
  showEventDetailModal: boolean = false;
  selectedEvent: CalendarEvent | null = null;
  showRecurringDeleteModal: boolean = false;
  recurringDeleteEvent: CalendarEvent | null = null;
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
    availability: 'busy',
    status: 'confirmed',
    recurrence: {
      frequency: 'never',
      interval: 1
    },
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
  private dismissedEventsKey = 'calendar_dismissed_events';
  
  // Export modal
  showExportModal = false;
  
  // Import modal
  showImportModal = false;
  
  // Recurrence properties
  endType: string = 'never';
  monthType: string = 'day';
  selectedDayOfWeek: string = 'MO';
  weekDays = [
    { value: 'MO', label: 'Mon' },
    { value: 'TU', label: 'Tue' },
    { value: 'WE', label: 'Wed' },
    { value: 'TH', label: 'Thu' },
    { value: 'FR', label: 'Fri' },
    { value: 'SA', label: 'Sat' },
    { value: 'SU', label: 'Sun' }
  ];
  
  // New reminder notification properties
  activeReminders: ReminderNotification[] = [];
  showReminderWindow = false;

  constructor(
    private http: HttpClient, 
    private emailService: EmailService,
    private authService: AuthService,
    private router: Router,
    private colorRegistry: ColorRegistryService,
    private cdr: ChangeDetectorRef,
    private recurrenceExpansion: RecurrenceExpansionService
  ) {}

  ngOnInit(): void {
    console.log('Calendar component is loading...');
    
    // Load dismissed events from localStorage
    this.loadDismissedEvents();
    
    // Always fetch calendars and show calendar view directly after login
    this.fetchCalendars();
    
    // Set up periodic refresh to detect EXDATE changes from other clients
    this.setupPeriodicRefresh();
    
    // 🎨 Thunderbird-style: Expose color management methods to window for testing
    (window as any).calendarColorManager = {
      setColor: (calendarName: string, color: string) => this.setCalendarColor(calendarName, color),
      getColor: (calendarName: string) => this.getCalendarColor(calendarName),
      getAllColors: () => this.getAllCalendarColors(),
      getStats: () => this.colorRegistry.getRegistryStats(),
      testColors: () => {
        console.log('🎨 Testing Thunderbird-style color management...');
        this.setCalendarColor('Red Calendar', '#ff0000');
        this.setCalendarColor('Green Calendar', '#00ff00');
        this.setCalendarColor('Blue Calendar', '#0000ff');
        this.setCalendarColor('Orange Calendar', '#ffa500');
        console.log('🎨 Test colors set! Check the calendar view.');
      },
      colorActualCalendars: () => {
        console.log('🎨 Setting colors for actual calendars...');
        // Set colors for the actual calendars that exist in your system
        this.setCalendarColor('rdcal', '#ff0000');
        this.setCalendarColor('calendar2', '#00ff00');
        console.log('🎨 Actual calendar colors set! Events should now show different colors.');
      },
      autoColorCalendars: () => {
        console.log('🎨 Auto-coloring all calendars with random colors...');
        const colors = ['#ff0000', '#00ff00', '#0000ff', '#ffa500', '#800080', '#ffc0cb', '#00ffff', '#ffff00'];
        this.calendars.forEach((calendar, index) => {
          const color = colors[index % colors.length];
          this.setCalendarColor(calendar.name, color);
          console.log(`🎨 Set ${calendar.name} to ${color}`);
        });
        console.log('🎨 All calendars auto-colored!');
      }
    };
    
    // 🔕 Expose reminder management methods to window for testing
    (window as any).reminderManager = {
      clearDismissedEvents: () => this.clearDismissedEvents(),
      getDismissedEvents: () => Array.from(this.notifiedEvents),
      getDismissedCount: () => this.notifiedEvents.size
    };
  }

  getCalendarColor(calendarName: string): string {
    // 🎨 Thunderbird-style: Get color from local registry
    return this.colorRegistry.getCalendarColor(calendarName);
  }

  setCalendarColor(calendarName: string, color: string): void {
    this.colorRegistry.setCalendarColor(calendarName, color);
    console.log(`🎨 Manually set color for calendar '${calendarName}': ${color}`);
    this.fetchEvents(); // Refresh events to apply new colors
    this.cdr.detectChanges(); // Force change detection to re-render child components
  }

  getAllCalendarColors(): Map<string, string> {
    return this.colorRegistry.getAllCalendarColors();
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

  onDateNavigationChange(newDate: dayjs.Dayjs): void {
    console.log('📅 Date navigation changed to:', newDate.format('YYYY-MM-DD'));
    this.currentDate = newDate;
    this.fetchEvents(); // Refresh events for the new date
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
    const weekStart = this.currentDate.startOf('week');
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
        
        // 🎨 Register calendar colors in ColorRegistryService (preserve user colors)
        this.calendars.forEach(calendar => {
          if (calendar.color) {
            // Only set color if it's not already stored (preserve user's custom colors)
            const existingColor = this.colorRegistry.getCalendarColor(calendar.name);
            if (existingColor === '#4285f4') { // Only override default blue
              this.colorRegistry.setCalendarColor(calendar.name, calendar.color);
              console.log(`🎨 Registered color for calendar '${calendar.name}': ${calendar.color}`);
            } else {
              console.log(`🎨 Preserving existing color for calendar '${calendar.name}': ${existingColor}`);
            }
          } else {
            console.log(`⚠️ Calendar '${calendar.name}' has no color property:`, calendar);
          }
        });
        
        // Debug: Show all registered colors
        console.log('🎨 All registered colors:', this.colorRegistry.getAllCalendarColors());
        console.log('🎨 Color registry stats:', this.colorRegistry.getRegistryStats());
        
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
                const eventsWithCalendar = response.data.map((event: CalendarEvent) => {
                  // 🎨 Thunderbird-style: Get color from local registry
                  const thunderbirdColor = this.colorRegistry.getCalendarColor(calendar.name);
                  
                  return {
                    ...event,
                    calendar_name: calendar.name,
                    calendar_color: thunderbirdColor, // Use Thunderbird-style color
                    calendar_id: calendar.id,
                    calendar_url: calendar.url, // Store calendar URL for proper deletion
                    color: thunderbirdColor // Set event color to Thunderbird-style color
                  };
                });
                allEvents.push(...eventsWithCalendar);
                
                // Debug logging for calendar events
                console.log('📅 Calendar Events Debug:', {
                  calendarName: calendar.name,
                  calendarColor: calendar.color,
                  thunderbirdColor: this.colorRegistry.getCalendarColor(calendar.name),
                  calendarUrl: calendar.url,
                  eventsCount: eventsWithCalendar.length,
                  sampleEvent: eventsWithCalendar[0] || null
                });
                
                // Debug recurrence and EXDATE data specifically
                eventsWithCalendar.forEach((event: CalendarEvent, index: number) => {
                  console.log(`🔄 Event ${index + 1} recurrence data:`, {
                    title: event.title,
                    recurrence: event.recurrence,
                    hasRecurrence: !!event.recurrence,
                    recurrenceType: event.recurrence?.frequency || 'none',
                    exdate: event.exdate,
                    hasExdate: !!event.exdate,
                    exdateCount: event.exdate ? (Array.isArray(event.exdate) ? event.exdate.length : 1) : 0
                  });
                  
                  // Special debug for events with EXDATE
                  if (event.exdate) {
                    console.log(`🗑️ FRONTEND EXDATE DEBUG: Event "${event.title}" has EXDATE:`, event.exdate);
                    console.log(`🗑️ FRONTEND EXDATE DEBUG: EXDATE type:`, typeof event.exdate, Array.isArray(event.exdate) ? 'array' : 'not array');
                    console.log(`🗑️ FRONTEND EXDATE DEBUG: Event UID:`, event.uid);
                    console.log(`🗑️ FRONTEND EXDATE DEBUG: Event recurrence:`, event.recurrence);
                  }
                });
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
        
        // Store master events from backend
        this.events = uniqueEvents;
        
        // 🔄 Expand recurring events into individual instances for display
        this.expandedEvents = this.expandRecurringEvents(uniqueEvents);
        console.log('🔄 Recurrence expansion:', {
          originalCount: uniqueEvents.length,
          expandedCount: this.expandedEvents.length,
          expansionRatio: this.expandedEvents.length / uniqueEvents.length
        });
        
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
          
          // 🔄 Expand recurring events
          const expandedEvents = this.expandRecurringEvents(uniqueEvents);
          console.log('🔄 Single calendar recurrence expansion:', {
            originalCount: uniqueEvents.length,
            expandedCount: expandedEvents.length
          });
          
          this.events = uniqueEvents;
          this.expandedEvents = expandedEvents;
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

  // Set up periodic refresh to detect EXDATE changes from other clients
  private setupPeriodicRefresh(): void {
    // Refresh events every 30 seconds to detect changes from other clients
    setInterval(() => {
      if (this.events.length > 0) {
        console.log('🔄 Periodic refresh: checking for EXDATE changes from other clients');
        this.fetchEvents();
      }
    }, 30000); // 30 seconds
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

  // Load dismissed events from localStorage
  private loadDismissedEvents(): void {
    try {
      const dismissedEventsJson = localStorage.getItem(this.dismissedEventsKey);
      if (dismissedEventsJson) {
        const dismissedEvents = JSON.parse(dismissedEventsJson);
        this.notifiedEvents = new Set(dismissedEvents);
        console.log('🔕 Loaded dismissed events from localStorage:', this.notifiedEvents.size, 'events');
      } else {
        console.log('🔕 No dismissed events found in localStorage');
      }
    } catch (error) {
      console.error('🔕 Error loading dismissed events from localStorage:', error);
      this.notifiedEvents = new Set();
    }
  }

  // Save dismissed events to localStorage
  private saveDismissedEvents(): void {
    try {
      const dismissedEventsArray = Array.from(this.notifiedEvents);
      localStorage.setItem(this.dismissedEventsKey, JSON.stringify(dismissedEventsArray));
      console.log('🔕 Saved dismissed events to localStorage:', dismissedEventsArray.length, 'events');
    } catch (error) {
      console.error('🔕 Error saving dismissed events to localStorage:', error);
    }
  }

  // Clear all dismissed events (for testing/debugging)
  public clearDismissedEvents(): void {
    this.notifiedEvents.clear();
    localStorage.removeItem(this.dismissedEventsKey);
    console.log('🔕 Cleared all dismissed events');
  }

  // Handle reminder dismissal
  onReminderDismissed(reminderId: string): void {
    console.log('🔕 Reminder dismissed:', reminderId);
    
    // Find the reminder to get the event ID
    const dismissedReminder = this.activeReminders.find(r => r.id === reminderId);
    if (dismissedReminder) {
      console.log('🔕 Dismissed reminder for event:', dismissedReminder.event.title);
      
      // Remove from active reminders
      this.activeReminders = this.activeReminders.filter(r => r.id !== reminderId);
      
      // Mark the event as permanently dismissed (don't show again)
      this.notifiedEvents.add(dismissedReminder.event.id);
      
      // Save dismissed events to localStorage for persistence
      this.saveDismissedEvents();
      
      console.log('🔕 Active reminders remaining:', this.activeReminders.length);
      console.log('🔕 Notified events count:', this.notifiedEvents.size);
      
      // Close reminder window if no more active reminders
      if (this.activeReminders.length === 0) {
        this.showReminderWindow = false;
        console.log('🔕 Reminder window closed - no more active reminders');
      }
    } else {
      console.warn('🔕 Could not find reminder with ID:', reminderId);
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
    console.log('🔕 Reminder window closed event received');
    this.showReminderWindow = false;
    console.log('🔕 showReminderWindow set to:', this.showReminderWindow);
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

  // Expand recurring events into individual instances
  private expandRecurringEvents(events: CalendarEvent[]): CalendarEvent[] {
    const expandedEvents: CalendarEvent[] = [];
    
    // Calculate date range for expansion (current view + some buffer)
    const startDate = this.getExpansionStartDate();
    const endDate = this.getExpansionEndDate();
    
    console.log('🔄 Expanding events for date range:', {
      startDate: startDate.toISOString().split('T')[0],
      endDate: endDate.toISOString().split('T')[0],
      viewType: this.currentView.type
    });
    
    for (const event of events) {
      if (event.recurrence && event.recurrence.frequency !== 'never') {
        // Expand recurring event (EXDATE handling is done in the expansion service)
        const expanded = this.recurrenceExpansion.expandRecurringEvent(event, startDate, endDate);
        
        expandedEvents.push(...expanded);
        
        console.log(`🔄 Expanded "${event.title}":`, {
          original: 1,
          expanded: expanded.length,
          frequency: event.recurrence.frequency,
          count: event.recurrence.count,
          exdateCount: event.exdate ? (Array.isArray(event.exdate) ? event.exdate.length : 1) : 0
        });
      } else {
        // Non-recurring event, add as-is
        expandedEvents.push(event);
      }
    }
    
    return expandedEvents;
  }

  // Note: Deleted occurrences are now handled via EXDATE in the recurrence expansion service

  // Get deleted occurrences from localStorage (legacy method - now using EXDATE)
  private getDeletedOccurrences(): any[] {
    try {
      const deletedEvents = localStorage.getItem('deleted_events');
      return deletedEvents ? JSON.parse(deletedEvents) : [];
    } catch (error) {
      console.error('Error loading deleted events:', error);
      return [];
    }
  }

  // Save deleted occurrence to localStorage (legacy method - now using EXDATE)
  private saveDeletedOccurrence(event: CalendarEvent, masterUid: string): void {
    try {
      const deletedEvents = this.getDeletedOccurrences();
      const occurrenceKey = `${masterUid}_${event.occurrenceIndex}`;
      
      const deletedOccurrence = {
        uid: occurrenceKey,
        masterUid: masterUid,
        occurrenceIndex: event.occurrenceIndex,
        action: 'current',
        deletedAt: new Date().toISOString(),
        title: event.title
      };
      
      // Check if already exists
      const exists = deletedEvents.some(deleted => deleted.uid === occurrenceKey);
      if (!exists) {
        deletedEvents.push(deletedOccurrence);
        localStorage.setItem('deleted_events', JSON.stringify(deletedEvents));
        console.log('🗑️ Saved deleted occurrence to localStorage:', occurrenceKey);
      }
    } catch (error) {
      console.error('Error saving deleted occurrence:', error);
    }
  }

  // Check if all occurrences of a master event have been deleted individually (legacy method)
  private checkIfAllOccurrencesDeleted(masterUid: string): boolean {
    try {
      // Find the master event to get its recurrence count
      const masterEvent = this.events.find(e => {
        const eventUid = e.uid || e.id;
        const eventMasterUid = eventUid.replace(/_(\d+)$/, '');
        return eventMasterUid === masterUid && !e.isRecurringInstance;
      });

      if (!masterEvent || !masterEvent.recurrence) {
        return false;
      }

      const totalOccurrences = masterEvent.recurrence.count || 10; // Default to 10 if no count
      const deletedOccurrences = this.getDeletedOccurrences();
      
      // Count how many occurrences of this master event have been deleted
      const deletedCount = deletedOccurrences.filter(deleted => 
        deleted.masterUid === masterUid && deleted.action === 'current'
      ).length;

      console.log(`🗑️ Checking occurrences for ${masterUid}:`, {
        totalOccurrences,
        deletedCount,
        shouldDeleteFromServer: deletedCount >= totalOccurrences
      });

      return deletedCount >= totalOccurrences;
    } catch (error) {
      console.error('Error checking if all occurrences deleted:', error);
      return false;
    }
  }

  // Delete master event from CalDAV server (legacy method)
  private async deleteMasterEventFromServer(masterUid: string, calendarUrl: string): Promise<void> {
    try {
      const deleteUrl = `http://localhost:8000/events/${masterUid}?calendar_url=${encodeURIComponent(calendarUrl)}&action=all`;
      
      console.log('🗑️ Deleting master event from server:', deleteUrl);
      
      const response = await this.http.delete<any>(deleteUrl, { withCredentials: true }).toPromise();
      
      if (response.success) {
        console.log('✅ Master event deleted from CalDAV server');
        // Clear the deleted occurrences from localStorage since the master event is now deleted
        this.clearDeletedOccurrencesForMaster(masterUid);
      } else {
        console.error('❌ Failed to delete master event from server:', response.message);
      }
    } catch (error) {
      console.error('Error deleting master event from server:', error);
    }
  }

  // Clear deleted occurrences from localStorage for a specific master event (legacy method)
  private clearDeletedOccurrencesForMaster(masterUid: string): void {
    try {
      const deletedEvents = this.getDeletedOccurrences();
      const filteredEvents = deletedEvents.filter(deleted => deleted.masterUid !== masterUid);
      localStorage.setItem('deleted_events', JSON.stringify(filteredEvents));
      console.log('🗑️ Cleared deleted occurrences for master event:', masterUid);
    } catch (error) {
      console.error('Error clearing deleted occurrences:', error);
    }
  }

  // Get start date for recurrence expansion
  private getExpansionStartDate(): Date {
    const viewType = this.currentView.type;
    const currentDate = this.currentDate.toDate();
    
    switch (viewType) {
      case 'day':
        return new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate() - 7);
      case 'week':
        return new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate() - 14);
      case 'month':
        return new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, currentDate.getDate());
      default:
        return new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate() - 30);
    }
  }

  // Get end date for recurrence expansion
  private getExpansionEndDate(): Date {
    const viewType = this.currentView.type;
    const currentDate = this.currentDate.toDate();
    
    switch (viewType) {
      case 'day':
        return new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate() + 7);
      case 'week':
        return new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate() + 14);
      case 'month':
        return new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, currentDate.getDate());
      default:
        return new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate() + 30);
    }
  }

  // Navigation methods
  previous(): void {
    if (this.currentView.type === 'day') {
      this.currentDate = this.currentDate.subtract(1, 'day');
    } else if (this.currentView.type === 'week') {
      this.currentDate = this.currentDate.subtract(1, 'week');
    } else if (this.currentView.type === 'month') {
      this.currentDate = this.currentDate.subtract(1, 'month');
    }
    this.fetchEvents();
  }

  next(): void {
    if (this.currentView.type === 'day') {
      this.currentDate = this.currentDate.add(1, 'day');
    } else if (this.currentView.type === 'week') {
      this.currentDate = this.currentDate.add(1, 'week');
    } else if (this.currentView.type === 'month') {
      this.currentDate = this.currentDate.add(1, 'month');
    }
    this.fetchEvents();
  }

  today(): void {
    this.currentDate = dayjs();
    this.fetchEvents();
  }

  changeView(view: View): void {
    this.currentView = view;
  }

  // Modal methods
  openAddEventModal(): void {
    this.showAddEventModal = true;
  }

  closeAddEventModal(): void {
    console.log('🔄 Closing add event modal...');
    this.showAddEventModal = false;
    this.resetNewEvent();
    console.log('✅ Add event modal closed, showAddEventModal:', this.showAddEventModal);
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

  // Recurrence management methods
  onRecurrenceFrequencyChange(): void {
    // Reset recurrence options when frequency changes
    this.newEvent.recurrence.interval = 1;
    this.newEvent.recurrence.count = undefined;
    this.newEvent.recurrence.until = undefined;
    this.newEvent.recurrence.byDay = undefined;
    this.newEvent.recurrence.byMonth = undefined;
    this.newEvent.recurrence.byMonthDay = undefined;
    this.newEvent.recurrence.bySetPos = undefined;
    this.newEvent.recurrence.specificDates = undefined;
  }

  addSpecificDate(): void {
    if (!this.newEvent.recurrence.specificDates) {
      this.newEvent.recurrence.specificDates = [];
    }
    this.newEvent.recurrence.specificDates.push('');
  }

  removeSpecificDate(index: number): void {
    if (this.newEvent.recurrence.specificDates) {
      this.newEvent.recurrence.specificDates.splice(index, 1);
    }
  }

  addDayOfWeek(day: string): void {
    if (!this.newEvent.recurrence.byDay) {
      this.newEvent.recurrence.byDay = [];
    }
    if (!this.newEvent.recurrence.byDay.includes(day)) {
      this.newEvent.recurrence.byDay.push(day);
    }
  }

  removeDayOfWeek(day: string): void {
    if (this.newEvent.recurrence.byDay) {
      const index = this.newEvent.recurrence.byDay.indexOf(day);
      if (index > -1) {
        this.newEvent.recurrence.byDay.splice(index, 1);
      }
    }
  }

  addMonthOfYear(month: number): void {
    if (!this.newEvent.recurrence.byMonth) {
      this.newEvent.recurrence.byMonth = [];
    }
    if (!this.newEvent.recurrence.byMonth.includes(month)) {
      this.newEvent.recurrence.byMonth.push(month);
    }
  }

  removeMonthOfYear(month: number): void {
    if (this.newEvent.recurrence.byMonth) {
      const index = this.newEvent.recurrence.byMonth.indexOf(month);
      if (index > -1) {
        this.newEvent.recurrence.byMonth.splice(index, 1);
      }
    }
  }

  addDayOfMonth(day: number): void {
    if (!this.newEvent.recurrence.byMonthDay) {
      this.newEvent.recurrence.byMonthDay = [];
    }
    if (!this.newEvent.recurrence.byMonthDay.includes(day)) {
      this.newEvent.recurrence.byMonthDay.push(day);
    }
  }

  removeDayOfMonth(day: number): void {
    if (this.newEvent.recurrence.byMonthDay) {
      const index = this.newEvent.recurrence.byMonthDay.indexOf(day);
      if (index > -1) {
        this.newEvent.recurrence.byMonthDay.splice(index, 1);
      }
    }
  }

  // Additional recurrence helper methods
  getRecurrenceIntervalText(): string {
    switch (this.newEvent.recurrence.frequency) {
      case 'daily':
        return this.newEvent.recurrence.interval === 1 ? 'day' : 'days';
      case 'weekly':
        return this.newEvent.recurrence.interval === 1 ? 'week' : 'weeks';
      case 'monthly':
        return this.newEvent.recurrence.interval === 1 ? 'month' : 'months';
      case 'annually':
        return this.newEvent.recurrence.interval === 1 ? 'year' : 'years';
      default:
        return '';
    }
  }

  isDaySelected(day: string): boolean {
    return this.newEvent.recurrence.byDay?.includes(day) || false;
  }

  toggleDayOfWeek(day: string): void {
    if (!this.newEvent.recurrence.byDay) {
      this.newEvent.recurrence.byDay = [];
    }
    
    if (this.newEvent.recurrence.byDay.includes(day)) {
      this.removeDayOfWeek(day);
    } else {
      this.addDayOfWeek(day);
    }
  }

  updateMonthDay(value: string): void {
    if (!this.newEvent.recurrence.byMonthDay) {
      this.newEvent.recurrence.byMonthDay = [];
    }
    this.newEvent.recurrence.byMonthDay[0] = parseInt(value) || 1;
  }

  updateSpecificDate(index: number, value: string): void {
    if (!this.newEvent.recurrence.specificDates) {
      this.newEvent.recurrence.specificDates = [];
    }
    this.newEvent.recurrence.specificDates[index] = value;
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
        availability: this.newEvent.availability,
        status: this.newEvent.status,
        attendees: this.newEvent.attendees,
        reminder: this.newEvent.reminder,
        recurrence: this.newEvent.recurrence,
        calendar_url: targetCalendar.url  // Pass the target calendar URL
      };

      console.log('📤 Sending event data to backend:', eventData);
      console.log('🔍 About to make POST request to:', 'http://localhost:8000/events');
      
      const response = await this.http.post<any>('http://localhost:8000/events', eventData, {
        withCredentials: true
      }).toPromise();
      
      console.log('🔍 POST request completed successfully');
      
      console.log('📥 Backend response:', response);
      
      if (response.success) {
        console.log('✅ Event created successfully in calendar:', targetCalendar.name);
        console.log('Event response:', response.data);
        
        // Close the modal (this also resets the form)
        this.closeAddEventModal();
        
        // Refresh events
        await this.fetchEvents();
        
        // Show success message
        alert('Event created successfully!');
      } else {
        console.error('❌ Failed to create event:', response.message);
        alert('Failed to create event: ' + (response.message || 'Unknown error'));
      }
    } catch (error: any) {
      console.error('❌ Error creating event:', error);
      console.error('🔍 Error details:', {
        message: error?.message,
        status: error?.status,
        statusText: error?.statusText,
        url: error?.url,
        error: error?.error
      });
      alert('Error creating event. Please try again.');
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
      availability: 'busy',
      status: 'confirmed',
      calendar_id: defaultCalendar ? defaultCalendar.id : undefined, // Set default but allow user to change
      recurrence: {
        frequency: 'never',
        interval: 1
      },
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
    console.log('🗑️ Delete event requested:', event);
    console.log('🗑️ Event recurrence:', event.recurrence);
    console.log('🗑️ Event properties:', {
      id: event.id,
      uid: event.uid,
      isRecurringInstance: event.isRecurringInstance,
      occurrenceIndex: event.occurrenceIndex,
      originalEventId: event.originalEventId
    });
    
    // Check if this is a recurring event
    if (event.recurrence && event.recurrence.frequency !== 'never') {
      console.log('🔄 This is a recurring event, showing deletion options');
      this.showRecurringDeleteModal = true;
      this.recurringDeleteEvent = event;
      return;
    }
    
    // For non-recurring events, proceed with normal deletion
    await this.performEventDeletion(event, 'all');
  }

  // Close recurring delete modal
  closeRecurringDeleteModal(): void {
    this.showRecurringDeleteModal = false;
    this.recurringDeleteEvent = null;
  }

  // Handle recurring delete confirmation
  async onRecurringDeleteConfirmed(action: RecurringDeleteAction): Promise<void> {
    console.log('🗑️ ===== MODAL CONFIRMATION DEBUG =====');
    console.log('🗑️ Action received:', action);
    console.log('🗑️ Recurring delete event exists:', !!this.recurringDeleteEvent);
    console.log('🗑️ Recurring delete event:', this.recurringDeleteEvent);
    
    if (!this.recurringDeleteEvent) {
      console.error('❌ No recurring delete event found!');
      return;
    }
    
    console.log('🗑️ Recurring deletion confirmed:', action, 'for event:', this.recurringDeleteEvent.title);
    
    // Store the event before closing the modal
    const eventToDelete = this.recurringDeleteEvent;
    
    // Close the modal first
    this.closeRecurringDeleteModal();
    
    console.log('🗑️ About to call performEventDeletion with:', {
      event: eventToDelete.title,
      action: action
    });
    
    // Perform the deletion with the stored event
    await this.performEventDeletion(eventToDelete, action);
  }

  // Perform event deletion based on action
  async performEventDeletion(event: CalendarEvent, action: RecurringDeleteAction): Promise<void> {
    try {
      // Check if event is null or undefined
      if (!event) {
        console.error('❌ Event is null or undefined');
        alert('Cannot delete event: Event information is missing.');
        return;
      }
      
      console.log('🗑️ ===== DELETION DEBUG START =====');
      console.log('🗑️ Performing deletion for action:', action);
      console.log('🗑️ Event details:', {
        title: event.title,
        uid: event.uid,
        id: event.id,
        calendar_url: event.calendar_url,
        recurrence: event.recurrence,
        isRecurringInstance: event.isRecurringInstance,
        occurrenceIndex: event.occurrenceIndex
      });
      
      // Use the event's calendar URL
      if (!event.calendar_url) {
        console.error('❌ No calendar URL found for event');
        alert('Cannot delete event: No calendar information available.');
        return;
      }

      // Use UID for deletion (the actual CalDAV identifier)
      // For "current" deletion, we need to send the master UID but track the specific occurrence
      let eventIdentifier = event.uid || event.id;
      
      console.log('🗑️ Initial eventIdentifier:', eventIdentifier);
      
      console.log('🗑️ Event details for deletion:', {
        action: action,
        eventId: event.id,
        eventUid: event.uid,
        isRecurringInstance: event.isRecurringInstance,
        occurrenceIndex: event.occurrenceIndex,
        originalEventId: event.originalEventId
      });
      
      if (action === 'current' && event.isRecurringInstance && event.occurrenceIndex !== undefined) {
        // For current occurrence deletion, we need to extract the master UID
        // The expanded UID is like "uid_12345_0", we need "uid_12345"
        const masterUid = event.uid?.replace(/_(\d+)$/, '') || event.originalEventId || event.id;
        eventIdentifier = masterUid;
        console.log('🗑️ Current occurrence deletion - using master UID:', eventIdentifier);
        console.log('🗑️ Occurrence index:', event.occurrenceIndex);
        console.log('🗑️ Original expanded UID:', event.uid);
      } else if (action === 'all' && event.isRecurringInstance) {
        // For 'all' deletion, we also need to extract the master UID
        // The expanded UID is like "uid_12345_0", we need "uid_12345"
        const masterUid = event.uid?.replace(/_(\d+)$/, '') || event.originalEventId || event.id;
        eventIdentifier = masterUid;
        console.log('🗑️ All occurrence deletion - using master UID:', eventIdentifier);
        console.log('🗑️ Original expanded UID:', event.uid);
      } else {
        console.log('🗑️ Using identifier for deletion:', eventIdentifier);
      }
      
      // Build the delete URL with action parameter
      let deleteUrl = `http://localhost:8000/events/${eventIdentifier}?calendar_url=${encodeURIComponent(event.calendar_url)}&action=${action}`;
      
      // For current deletion, add occurrence index parameter
      if (action === 'current' && event.isRecurringInstance && event.occurrenceIndex !== undefined) {
        deleteUrl += `&occurrence_index=${event.occurrenceIndex}`;
      }
      
      console.log('🗑️ Deleting event from URL:', deleteUrl);
      
      const response = await this.http.delete<any>(deleteUrl, { withCredentials: true }).toPromise();
      
      console.log('🗑️ Delete response:', response);
      
      if (response.success) {
        // Handle different deletion actions
        if (action === 'all') {
          // For 'all' deletion, the backend should have deleted from CalDAV server
          // Remove the master event from this.events (this will automatically remove all expanded occurrences)
          const masterUid = eventIdentifier;
          
          console.log('🗑️ ALL deletion - Before filtering:');
          console.log('🗑️ Master UID to remove:', masterUid);
          console.log('🗑️ Current events count:', this.events.length);
          console.log('🗑️ Current expanded events count:', this.expandedEvents.length);
          console.log('🗑️ Events before filtering:', this.events.map(e => ({ uid: e.uid, title: e.title })));
          
          this.events = this.events.filter(e => {
            const eventUid = e.uid || e.id;
            const shouldKeep = eventUid !== masterUid;
            console.log(`🗑️ Event ${eventUid} (${e.title}): ${shouldKeep ? 'KEEP' : 'REMOVE'}`);
            return shouldKeep;
          });
          
          console.log('🗑️ After filtering master events:', this.events.length);
          
          // Re-expand events to update expandedEvents
          this.expandedEvents = this.expandRecurringEvents(this.events);
          
          console.log('🗑️ After re-expansion:', this.expandedEvents.length);
          console.log('✅ All occurrences deleted successfully from server');
        } else if (action === 'current') {
          // For 'current' deletion, the backend adds EXDATE to master event
          // We need to refresh events to get the updated event with EXDATE
          console.log('✅ Current occurrence deletion - EXDATE added to master event');
          
          // Refresh events to get the updated event with EXDATE
          await this.fetchEvents();
        }
        
        // Close any open modals
        this.closeEventDetailModal();
        this.closeRecurringDeleteModal();
        
        // Show success message
        alert(`Event ${action} deletion completed successfully!`);
      } else {
        console.error('❌ Delete failed:', response.message);
        alert('Failed to delete event: ' + (response.message || 'Unknown error'));
      }
    } catch (error) {
      console.error('❌ Error deleting event:', error);
      alert('Error deleting event. Please try again.');
    }
  }

  async onEditEvent(event: CalendarEvent): Promise<void> {
    try {
      console.log('✏️ Edit event requested:', event);
      console.log('🕐 Event start_time:', event.start_time);
      console.log('🕐 Event end_time:', event.end_time);
      console.log('📅 Original calendar_id:', event.calendar_id);
      
      if (!event.calendar_url) {
        console.error('❌ No calendar URL found for event');
        alert('Cannot edit event: No calendar information available.');
        return;
      }

      const eventIdentifier = event.uid || event.id;
      console.log('✏️ Using identifier for edit:', eventIdentifier);
      
      const editData = {
        title: event.title,
        description: event.description,
        location: event.location,
        start_time: event.start_time,
        end_time: event.end_time,
        all_day: event.all_day,
        availability: event.availability || 'busy',
        status: event.status || 'confirmed',
        calendar_id: event.calendar_id, // This now contains the UPDATED calendar_id from the form
        attendees: event.attendees,
        recurrence: event.recurrence,
        reminder: event.reminder,
        calendar_url: event.calendar_url
      };
      
      console.log('✏️ Edit data being sent to backend:', editData);
      console.log('📅 Calendar ID in edit data:', editData.calendar_id);
      console.log('📅 Calendar ID type:', typeof editData.calendar_id);
      
      const editUrl = `http://localhost:8000/events/${eventIdentifier}?calendar_url=${encodeURIComponent(event.calendar_url)}`;
      
      const response = await this.http.put<any>(editUrl, editData, {
        withCredentials: true
      }).toPromise();
      
      console.log('📥 Backend edit response:', response);
      
      if (response.success) {
        console.log('✅ Event updated successfully');
        this.closeEventDetailModal();
        await this.fetchEvents(); // Refresh events
        alert('Event updated successfully!');
      } else {
        console.error('❌ Failed to update event:', response.message);
        alert('Failed to update event: ' + (response.message || 'Unknown error'));
      }
    } catch (error) {
      console.error('❌ Error updating event:', error);
      alert('Error updating event. Please try again.');
    }
  }

  // Print functionality
  printCalendar(): void {
    console.log('🖨️ Printing calendar view:', this.currentView.type);
    
    // Hide sidebar and other UI elements for printing
    const originalSidebarState = this.showSidebar;
    this.showSidebar = false;
    
    // Create print styles
    const printStyles = this.createPrintStyles();
    
    // Add print styles to document
    const styleElement = document.createElement('style');
    styleElement.id = 'print-styles';
    styleElement.textContent = printStyles;
    document.head.appendChild(styleElement);
    
    // Wait for DOM to update, then print
    setTimeout(() => {
      window.print();
      
      // Clean up after printing
      setTimeout(() => {
        // Remove print styles
        const printStyleElement = document.getElementById('print-styles');
        if (printStyleElement) {
          printStyleElement.remove();
        }
        
        // Restore sidebar state
        this.showSidebar = originalSidebarState;
        
        console.log('🖨️ Print completed, UI restored');
      }, 1000);
    }, 100);
  }

  private createPrintStyles(): string {
    const currentDate = this.currentDate.format('MMMM YYYY');
    const viewTitle = this.getViewTitle();
    
    return `
      @media print {
        /* Hide non-essential elements */
        .sidebar,
        .calendar-header,
        .modal-overlay,
        .reminder-overlay,
        .action-buttons,
        .print-btn,
        .add-event-btn,
        .logout-btn,
        .sidebar-toggle-btn {
          display: none !important;
        }
        
        /* Print header - ultra compact */
        body::before {
          content: "${viewTitle} - ${currentDate}";
          display: block;
          font-size: 12px;
          font-weight: bold;
          text-align: center;
          margin-bottom: 5px;
          padding: 2px;
          border-bottom: 1px solid #333;
        }
        
        /* Main content adjustments - ultra compact */
        .main-content {
          margin: 0 !important;
          padding: 0 !important;
          width: 100% !important;
          height: auto !important;
          overflow: visible !important;
        }
        
        .calendar-content {
          overflow: visible !important;
          height: auto !important;
          padding: 0 !important;
        }
        
        /* Calendar view specific styles - ultra compact */
        .day-view,
        .week-view,
        .month-view,
        .agenda-view {
          width: 100% !important;
          margin: 0 !important;
          padding: 0 !important;
          font-size: 8px !important;
        }
        
        /* Event styling for print - ultra compact */
        .event-item {
          border: 1px solid #333 !important;
          background: white !important;
          color: black !important;
          margin: 0 !important;
          padding: 1px 2px !important;
          font-size: 7px !important;
          page-break-inside: avoid;
          line-height: 1.1 !important;
        }
        
        /* Calendar grid styling - ultra compact */
        .calendar-grid {
          border: 1px solid #333 !important;
        }
        
        .calendar-day {
          border: 1px solid #ccc !important;
          min-height: 30px !important;
          padding: 1px !important;
        }
        
        /* Time slots - ultra compact */
        .time-slot {
          border-bottom: 1px solid #eee !important;
          height: 15px !important;
          font-size: 7px !important;
        }
        
        /* Week view specific - ultra compact */
        .week-view {
          font-size: 7px !important;
        }
        
        .week-day {
          min-height: 25px !important;
          padding: 1px !important;
        }
        
        /* Month view specific - ultra compact */
        .month-view {
          font-size: 7px !important;
        }
        
        .month-day {
          min-height: 20px !important;
          padding: 0 !important;
        }
        
        /* Agenda view specific - ultra compact */
        .agenda-item {
          border-bottom: 1px solid #eee !important;
          padding: 1px 0 !important;
          page-break-inside: avoid;
          font-size: 7px !important;
        }
        
        /* Day view timeline - ultra compact */
        .day-timeline {
          font-size: 7px !important;
        }
        
        .timeline-hour {
          height: 15px !important;
          font-size: 6px !important;
        }
        
        /* Ensure proper page breaks */
        .calendar-week,
        .calendar-month {
          page-break-inside: avoid;
        }
        
        /* Print margins - minimal for maximum space */
        @page {
          margin: 0.1in;
          size: A4;
        }
        
        /* Hide scrollbars */
        * {
          overflow: visible !important;
        }
        
        /* General text sizing - ultra compact */
        body {
          font-size: 7px !important;
          line-height: 1.1 !important;
        }
        
        /* Compact headers */
        h1, h2, h3 {
          font-size: 9px !important;
          margin: 2px 0 !important;
        }
        
        /* Compact labels */
        .event-title {
          font-size: 7px !important;
          font-weight: bold !important;
        }
        
        .event-time {
          font-size: 6px !important;
        }
        
        .event-location {
          font-size: 5px !important;
        }
        
        /* Additional ultra-compact styles */
        .calendar-header,
        .view-selector,
        .date-navigation {
          font-size: 6px !important;
          margin: 0 !important;
          padding: 0 !important;
        }
        
        /* Remove all unnecessary spacing */
        * {
          margin: 0 !important;
          padding: 0 !important;
        }
        
        /* Restore minimal padding only where needed */
        .event-item {
          padding: 1px 2px !important;
        }
        
        .calendar-day {
          padding: 1px !important;
        }
        
        .week-day {
          padding: 1px !important;
        }
        
        /* Ultra-compact table cells */
        td, th {
          padding: 0 !important;
          margin: 0 !important;
          font-size: 7px !important;
        }
        
        /* Ultra-compact divs */
        div {
          margin: 0 !important;
          padding: 0 !important;
        }
        
        /* Restore essential padding */
        .event-item {
          padding: 1px 2px !important;
        }
        
        .calendar-day {
          padding: 1px !important;
        }
        
        .week-day {
          padding: 1px !important;
        }
        
        .month-day {
          padding: 0 !important;
        }
        
        .agenda-item {
          padding: 1px 0 !important;
        }
      }
    `;
  }

  private getViewTitle(): string {
    switch (this.currentView.type) {
      case 'day':
        return `Day View - ${this.currentDate.format('dddd, MMMM D, YYYY')}`;
      case 'week':
        return `Week View - ${this.getWeekDateRange()}`;
      case 'month':
        return `Month View - ${this.currentDate.format('MMMM YYYY')}`;
      case 'agenda':
        return `Agenda View - ${this.currentDate.format('MMMM YYYY')}`;
      default:
        return 'Calendar View';
    }
  }

  // Get events for a specific day
  getEventsForDay(date: dayjs.Dayjs): CalendarEvent[] {
    return this.expandedEvents.filter(event => {
      const eventDate = dayjs(event.start_time);
      return eventDate.isSame(date, 'day');
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
  
  // Export functionality
  openExportModal(): void {
    console.log('📤 Opening export modal');
    console.log('📤 Available calendars:', this.calendars);
    this.showExportModal = true;
  }
  
  closeExportModal(): void {
    console.log('📤 Closing export modal');
    this.showExportModal = false;
  }
  
  async onExport(options: ExportOptions): Promise<void> {
    try {
      console.log('📤 Export options:', options);
      
      // Get events based on options
      let eventsToExport = this.events;
      
      // Filter by calendar if not "all"
      if (options.calendar && options.calendar !== 'all') {
        eventsToExport = eventsToExport.filter(event => {
          // Try both calendar_url and calendar_id for matching
          return event.calendar_url === options.calendar || 
                 event.calendar_id?.toString() === options.calendar;
        });
      }
      
      // Filter by date range
      if (options.dateRange !== 'all') {
        const now = dayjs();
        let startDate: dayjs.Dayjs;
        let endDate: dayjs.Dayjs;
        
        if (options.dateRange === 'custom') {
          startDate = dayjs(options.customStartDate);
          endDate = dayjs(options.customEndDate);
        } else {
          const monthsBack = parseInt(options.dateRange.replace('months', '').replace('month', ''));
          startDate = now.subtract(monthsBack, 'month');
          endDate = now;
        }
        
        eventsToExport = eventsToExport.filter(event => {
          const eventDate = dayjs(event.start_time);
          return eventDate.isAfter(startDate) && eventDate.isBefore(endDate);
        });
      }
      
      console.log('📤 Events to export:', eventsToExport.length);
      
      // Generate iCalendar content
      const icalContent = this.generateICalendarContent(eventsToExport);
      
      // Create and download file
      this.downloadICalendarFile(icalContent, 'calendar-export.ics');
      
      this.closeExportModal();
      alert(`Successfully exported ${eventsToExport.length} events to iCalendar format!`);
      
    } catch (error) {
      console.error('❌ Export error:', error);
      alert('Error exporting calendar. Please try again.');
    }
  }
  
  private generateICalendarContent(events: CalendarEvent[]): string {
    const now = dayjs().format('YYYYMMDDTHHmmss[Z]');
    
    let ical = [
      'BEGIN:VCALENDAR',
      'VERSION:2.0',
      'PRODID:-//Calendar App//Calendar Export//EN',
      'CALSCALE:GREGORIAN',
      'METHOD:PUBLISH'
    ];
    
    events.forEach(event => {
      const startTime = dayjs(event.start_time).format('YYYYMMDDTHHmmss[Z]');
      const endTime = dayjs(event.end_time).format('YYYYMMDDTHHmmss[Z]');
      const created = dayjs().format('YYYYMMDDTHHmmss[Z]');
      
      ical.push(
        'BEGIN:VEVENT',
        `UID:${event.uid || event.id}@calendar-app.com`,
        `DTSTART:${startTime}`,
        `DTEND:${endTime}`,
        `DTSTAMP:${created}`,
        `SUMMARY:${event.title}`,
        `DESCRIPTION:${event.description || ''}`,
        `LOCATION:${event.location || ''}`,
        `STATUS:CONFIRMED`,
        `TRANSP:OPAQUE`,
        'END:VEVENT'
      );
    });
    
    ical.push('END:VCALENDAR');
    
    return ical.join('\r\n');
  }
  
  private downloadICalendarFile(content: string, filename: string): void {
    const blob = new Blob([content], { type: 'text/calendar;charset=utf-8' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  }
  
  // Import functionality
  openImportModal(): void {
    console.log('📥 Opening import modal');
    console.log('📥 Available calendars:', this.calendars);
    this.showImportModal = true;
  }
  
  closeImportModal(): void {
    console.log('📥 Closing import modal');
    this.showImportModal = false;
  }
  
  async onImport(options: ImportOptions): Promise<void> {
    try {
      console.log('📥 Import options:', options);
      
      if (!options.file) {
        alert('Please select a file to import.');
        return;
      }
      
      // Parse the file based on its type
      const events = await this.parseImportFile(options.file);
      console.log('📥 Parsed events:', events.length);
      
      if (events.length === 0) {
        alert('No events found in the selected file.');
        return;
      }
      
      // Filter events by date range if specified
      let eventsToImport = events;
      if (options.dateRange !== 'all') {
        const now = dayjs();
        let startDate: dayjs.Dayjs;
        let endDate: dayjs.Dayjs;
        
        if (options.dateRange === 'custom') {
          startDate = dayjs(options.customStartDate);
          endDate = dayjs(options.customEndDate);
        } else {
          const monthsBack = parseInt(options.dateRange.replace('months', '').replace('month', ''));
          startDate = now.subtract(monthsBack, 'month');
          endDate = now;
        }
        
        eventsToImport = events.filter(event => {
          const eventDate = dayjs(event.start_time);
          return eventDate.isAfter(startDate) && eventDate.isBefore(endDate);
        });
      }
      
      console.log('📥 Events to import after filtering:', eventsToImport.length);
      
      // Import events to the selected calendar
      let successCount = 0;
      let errorCount = 0;
      
      for (const event of eventsToImport) {
        try {
          const eventData = {
            title: event.title,
            description: event.description || '',
            location: event.location || '',
            start_time: event.start_time,
            end_time: event.end_time,
            all_day: event.all_day || false,
            attendees: event.attendees || [],
            calendar_url: options.calendar
          };
          
          const response = await this.http.post<any>('http://localhost:8000/events', eventData, {
            withCredentials: true
          }).toPromise();
          
          if (response.success) {
            successCount++;
          } else {
            errorCount++;
            console.error('❌ Failed to import event:', event.title, response.message);
          }
        } catch (error) {
          errorCount++;
          console.error('❌ Error importing event:', event.title, error);
        }
      }
      
      this.closeImportModal();
      
      if (successCount > 0) {
        await this.fetchEvents(); // Refresh events
        alert(`Successfully imported ${successCount} events!${errorCount > 0 ? ` ${errorCount} events failed to import.` : ''}`);
      } else {
        alert('No events were successfully imported. Please check the file format and try again.');
      }
      
    } catch (error) {
      console.error('❌ Import error:', error);
      alert('Error importing events. Please check the file format and try again.');
    }
  }
  
  private async parseImportFile(file: File): Promise<any[]> {
    const fileExtension = file.name.split('.').pop()?.toLowerCase();
    const fileContent = await this.readFileContent(file);
    
    switch (fileExtension) {
      case 'ics':
        return this.parseICalendarFile(fileContent);
      case 'csv':
        return this.parseCSVFile(fileContent);
      case 'json':
        return this.parseJSONFile(fileContent);
      default:
        throw new Error(`Unsupported file format: ${fileExtension}`);
    }
  }
  
  private readFileContent(file: File): Promise<string> {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = (e) => resolve(e.target?.result as string || '');
      reader.onerror = (e) => reject(e);
      reader.readAsText(file);
    });
  }
  
  private parseICalendarFile(content: string): any[] {
    const events: any[] = [];
    const lines = content.split('\n');
    let currentEvent: any = {};
    let inEvent = false;
    
    for (const line of lines) {
      const trimmedLine = line.trim();
      
      if (trimmedLine === 'BEGIN:VEVENT') {
        inEvent = true;
        currentEvent = {};
      } else if (trimmedLine === 'END:VEVENT') {
        if (inEvent && currentEvent.title) {
          events.push(currentEvent);
        }
        inEvent = false;
      } else if (inEvent) {
        const [key, ...valueParts] = trimmedLine.split(':');
        const value = valueParts.join(':');
        
        switch (key) {
          case 'SUMMARY':
            currentEvent.title = value;
            break;
          case 'DESCRIPTION':
            currentEvent.description = value;
            break;
          case 'LOCATION':
            currentEvent.location = value;
            break;
          case 'DTSTART':
            currentEvent.start_time = this.parseICalendarDate(value);
            break;
          case 'DTEND':
            currentEvent.end_time = this.parseICalendarDate(value);
            break;
        }
      }
    }
    
    return events;
  }
  
  private parseICalendarDate(dateStr: string): string {
    // Parse iCalendar date format (YYYYMMDDTHHMMSSZ or YYYYMMDD)
    if (dateStr.length === 15 && dateStr.endsWith('Z')) {
      // Format: YYYYMMDDTHHMMSSZ
      const year = dateStr.substring(0, 4);
      const month = dateStr.substring(4, 6);
      const day = dateStr.substring(6, 8);
      const hour = dateStr.substring(9, 11);
      const minute = dateStr.substring(11, 13);
      const second = dateStr.substring(13, 15);
      return `${year}-${month}-${day}T${hour}:${minute}:${second}`;
    } else if (dateStr.length === 8) {
      // Format: YYYYMMDD (all-day event)
      const year = dateStr.substring(0, 4);
      const month = dateStr.substring(4, 6);
      const day = dateStr.substring(6, 8);
      return `${year}-${month}-${day}T00:00:00`;
    }
    return dateStr;
  }
  
  private parseCSVFile(content: string): any[] {
    const events: any[] = [];
    const lines = content.split('\n');
    const headers = lines[0].split(',').map(h => h.trim());
    
    for (let i = 1; i < lines.length; i++) {
      const values = lines[i].split(',').map(v => v.trim());
      if (values.length >= headers.length) {
        const event: any = {};
        headers.forEach((header, index) => {
          event[header.toLowerCase()] = values[index];
        });
        
        // Map common CSV fields to our event structure
        if (event.title || event.summary) {
          events.push({
            title: event.title || event.summary,
            description: event.description || '',
            location: event.location || '',
            start_time: event.start_time || event.start || event.startdate,
            end_time: event.end_time || event.end || event.enddate,
            all_day: event.all_day === 'true' || event.allday === 'true'
          });
        }
      }
    }
    
    return events;
  }
  
  private parseJSONFile(content: string): any[] {
    try {
      const data = JSON.parse(content);
      
      // Handle different JSON structures
      if (Array.isArray(data)) {
        return data;
      } else if (data.events && Array.isArray(data.events)) {
        return data.events;
      } else if (data.calendar && data.calendar.events) {
        return data.calendar.events;
      }
      
      return [];
    } catch (error) {
      console.error('❌ Error parsing JSON file:', error);
      throw new Error('Invalid JSON format');
    }
  }
}
