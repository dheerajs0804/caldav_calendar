import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import * as dayjs from 'dayjs';
import { EmailService } from '../../services/email.service';

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
  calendar_name?: string;
  calendar_url?: string;
  calendar_color?: string;
  color?: string;
  availability?: 'free' | 'busy' | 'tentative';
  status?: 'confirmed' | 'tentative' | 'cancelled';
  recurrence?: {
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
  exdate?: string | string[]; // Excluded dates in UTC format (can be single string or array)
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

@Component({
  selector: 'app-event-detail-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './event-detail-modal.component.html',
  styleUrls: ['./event-detail-modal.component.scss']
})
export class EventDetailModalComponent {
  @Input() event: Event | null = null;
  @Input() calendars: Calendar[] = [];
  @Input() isVisible: boolean = false;
  
  @Output() closeModal = new EventEmitter<void>();
  @Output() editEvent = new EventEmitter<Event>();
  @Output() deleteEvent = new EventEmitter<Event>();

  isEditMode: boolean = false;
  editedEvent: Event | null = null;
  
  // Recurrence UI state properties
  endType: 'never' | 'count' | 'until' = 'never';
  monthType: 'day' | 'position' = 'day';
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

  constructor(private emailService: EmailService) {}

  ngOnChanges(): void {
    if (this.event) {
      // Create a copy of the event for editing with default values for new fields
      this.editedEvent = { 
        ...this.event,
        availability: this.event.availability || 'busy',
        status: this.event.status || 'confirmed',
        attendees: this.event.attendees || [],
        recurrence: this.event.recurrence || {
          frequency: 'never',
          interval: 1
        },
        reminder: this.event.reminder || {
          enabled: false,
          type: 'message',
          time: 15,
          unit: 'minutes',
          relativeTo: 'start'
        }
      };
      
      // Initialize recurrence UI state
      this.initializeRecurrenceProperties();
      
      console.log('🔄 Event detail modal initialized with event:', this.event);
      console.log('🔄 Edited event created:', this.editedEvent);
      console.log('🗑️ EXDATE data in event:', this.event.exdate);
      console.log('🗑️ EXDATE data type:', typeof this.event.exdate, Array.isArray(this.event.exdate) ? 'array' : 'not array');
      if (this.event.exdate) {
        if (Array.isArray(this.event.exdate)) {
          console.log('🗑️ EXDATE array length:', this.event.exdate.length);
        } else {
          console.log('🗑️ EXDATE single value:', this.event.exdate);
        }
      }
      console.log('🔄 Event recurrence data:', {
        originalRecurrence: this.event.recurrence,
        editedRecurrence: this.editedEvent.recurrence,
        hasOriginalRecurrence: !!this.event.recurrence,
        hasEditedRecurrence: !!this.editedEvent.recurrence
      });
      console.log('📅 Initial calendar_id:', this.editedEvent.calendar_id);
    }
  }

  getEventCalendar(): Calendar | undefined {
    if (!this.event) return undefined;
    return this.calendars.find(cal => Number(cal.id) === Number(this.event!.calendar_id));
  }

  formatDateTime(dateTime: string): string {
    return dayjs(dateTime).format('DD MMMM YYYY HH:mm');
  }

  onClose(): void {
    this.closeModal.emit();
  }

  onEdit(): void {
    this.isEditMode = true;
  }

  onCalendarChange(event: any): void {
    const target = event.target as HTMLSelectElement;
    const selectedCalendarId = target.value;
    console.log('🚀 onCalendarChange called with:', selectedCalendarId, 'Type:', typeof selectedCalendarId);
    console.log('📅 Available calendars:', this.calendars.map(cal => ({ id: cal.id, name: cal.name, type: typeof cal.id })));
    
    if (this.editedEvent) {
      // Convert to number to ensure type consistency
      this.editedEvent.calendar_id = Number(selectedCalendarId);
      console.log('📅 Calendar changed to:', selectedCalendarId, 'Type:', typeof selectedCalendarId);
      console.log('📅 Updated editedEvent.calendar_id:', this.editedEvent.calendar_id, 'Type:', typeof this.editedEvent.calendar_id);
      
      // Also update calendar-related fields
      const selectedCalendar = this.calendars.find(cal => Number(cal.id) === Number(selectedCalendarId));
      if (selectedCalendar) {
        this.editedEvent.calendar_name = selectedCalendar.name;
        this.editedEvent.calendar_url = selectedCalendar.url;
        this.editedEvent.calendar_color = selectedCalendar.color;
        this.editedEvent.color = selectedCalendar.color;
        console.log('📅 Updated calendar info:', {
          name: selectedCalendar.name,
          url: selectedCalendar.url,
          color: selectedCalendar.color
        });
      } else {
        console.error('❌ Calendar not found for ID:', selectedCalendarId);
      }
    } else {
      console.error('❌ No editedEvent found in onCalendarChange!');
    }
  }

  onSave(): void {
    console.log('🚀 onSave method called in event detail modal!');
    if (this.editedEvent) {
      console.log('💾 Saving edited event:', this.editedEvent);
      console.log('🕐 Start time:', this.editedEvent.start_time);
      console.log('🕐 End time:', this.editedEvent.end_time);
      console.log('📅 Calendar ID being saved:', this.editedEvent.calendar_id);
      console.log('📅 Calendar ID type:', typeof this.editedEvent.calendar_id);
      console.log('📤 Emitting editEvent with data:', this.editedEvent);
      this.editEvent.emit(this.editedEvent);
      this.isEditMode = false;
    } else {
      console.error('❌ No editedEvent found!');
    }
  }

  onCancel(): void {
    this.isEditMode = false;
    // Reset edited event to original
    if (this.event) {
      this.editedEvent = { 
        ...this.event,
        attendees: this.event.attendees || []
      };
      // Reinitialize recurrence properties
      this.initializeRecurrenceProperties();
    }
  }

  onRecurrenceFrequencyChange(value: string): void {
    if (this.editedEvent) {
      if (!this.editedEvent.recurrence) {
        this.editedEvent.recurrence = {
          frequency: 'never',
          interval: 1
        };
      }
      this.editedEvent.recurrence.frequency = value as any;
    }
  }

  onReminderEnabledChange(enabled: boolean): void {
    if (this.editedEvent) {
      if (!this.editedEvent.reminder) {
        this.editedEvent.reminder = {
          enabled: false,
          type: 'message',
          time: 15,
          unit: 'minutes',
          relativeTo: 'start'
        };
      }
      this.editedEvent.reminder.enabled = enabled;
    }
  }

  onReminderTimeChange(time: number): void {
    if (this.editedEvent && this.editedEvent.reminder) {
      this.editedEvent.reminder.time = time;
    }
  }

  onReminderUnitChange(unit: string): void {
    if (this.editedEvent && this.editedEvent.reminder) {
      this.editedEvent.reminder.unit = unit;
    }
  }

  onDelete(): void {
    if (this.event) {
      if (confirm('Are you sure you want to delete this event?')) {
        this.deleteEvent.emit(this.event);
      }
    }
  }

  onStartTimeChange(event: any): void {
    const target = event.target as HTMLInputElement;
    if (this.editedEvent && target.value) {
      // Convert datetime-local format (YYYY-MM-DDTHH:MM) to full datetime format
      this.editedEvent.start_time = target.value + ':00';
      console.log('🕐 Start time updated to:', this.editedEvent.start_time);
      console.log('🕐 Original start time was:', this.event?.start_time);
    }
  }

  onEndTimeChange(event: any): void {
    const target = event.target as HTMLInputElement;
    if (this.editedEvent && target.value) {
      // Convert datetime-local format (YYYY-MM-DDTHH:MM) to full datetime format
      this.editedEvent.end_time = target.value + ':00';
      console.log('🕐 End time updated to:', this.editedEvent.end_time);
      console.log('🕐 Original end time was:', this.event?.end_time);
    }
  }

  // Attendee management methods
  addAttendee(): void {
    if (this.editedEvent) {
      if (!this.editedEvent.attendees) {
        this.editedEvent.attendees = [];
      }
      this.editedEvent.attendees.push({
        email: '',
        name: '',
        response: 'pending',
        role: 'required'
      });
    }
  }

  removeAttendee(index: number): void {
    if (this.editedEvent && this.editedEvent.attendees) {
      this.editedEvent.attendees.splice(index, 1);
    }
  }

  updateAttendee(index: number, field: string, value: any): void {
    if (this.editedEvent && this.editedEvent.attendees) {
      this.editedEvent.attendees[index] = {
        ...this.editedEvent.attendees[index],
        [field]: value
      };
    }
  }

  downloadCalendarFile(): void {
    if (this.event && this.event.attendees && this.event.attendees.length > 0) {
      // Filter out attendees without email addresses
      const validAttendees = this.event.attendees.filter(a => a.email && a.email.trim());
      
      if (validAttendees.length === 0) {
        alert('No valid email addresses found for attendees.');
        return;
      }

      // Prepare event details for the calendar file
      const eventDetails = {
        title: this.event.title,
        description: this.event.description,
        location: this.event.location,
        startTime: this.event.start_time,
        endTime: this.event.end_time,
        allDay: this.event.all_day,
        organizer: 'organizer@example.com' // TODO: Get from user profile
      };

      // Download the iCalendar file
      this.emailService.downloadICalendar(validAttendees, eventDetails);
    } else {
      alert('No attendees found for this event.');
    }
  }

  // Helper methods for formatting recurrence data
  formatDate(dateString: string): string {
    if (!dateString) return '';
    try {
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    } catch (error) {
      return dateString;
    }
  }

  formatDaysOfWeek(days: string[]): string {
    if (!days || days.length === 0) return '';
    
    const dayMap: { [key: string]: string } = {
      'MO': 'Monday',
      'TU': 'Tuesday', 
      'WE': 'Wednesday',
      'TH': 'Thursday',
      'FR': 'Friday',
      'SA': 'Saturday',
      'SU': 'Sunday'
    };
    
    return days.map(day => dayMap[day] || day).join(', ');
  }

  // Helper method to check if exdate is an array
  isExdateArray(): boolean {
    return Array.isArray(this.event?.exdate);
  }

  // Helper method to calculate remaining occurrences
  getRemainingOccurrences(): number {
    if (!this.event?.recurrence?.count) return 0;
    
    const originalCount = this.event.recurrence.count;
    const excludedCount = this.getExdateArray().length;
    const remaining = Math.max(0, originalCount - excludedCount);
    
    console.log('🗑️ Occurrence calculation:', {
      originalCount,
      excludedCount,
      remaining,
      exdateArray: this.getExdateArray()
    });
    
    return remaining;
  }

  // Helper method to get exdate as array
  getExdateArray(): string[] {
    if (!this.event?.exdate) return [];
    if (Array.isArray(this.event.exdate)) {
      return this.event.exdate;
    } else {
      return [this.event.exdate];
    }
  }

  formatExdate(exdate: string): string {
    console.log('🗑️ Formatting EXDATE:', exdate);
    try {
      // Handle UTC format: 20250920T111800Z
      if (/^\d{8}T\d{6}Z$/.test(exdate)) {
        const year = exdate.substring(0, 4);
        const month = exdate.substring(4, 6);
        const day = exdate.substring(6, 8);
        const hour = exdate.substring(9, 11);
        const minute = exdate.substring(11, 13);
        
        // Create a readable date format
        const date = new Date(`${year}-${month}-${day}T${hour}:${minute}:00Z`);
        const formatted = date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'long',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });
        console.log('🗑️ Formatted UTC EXDATE:', exdate, '->', formatted);
        return formatted;
      }
      
      // Handle ISO format
      const date = new Date(exdate);
      if (!isNaN(date.getTime())) {
        const formatted = date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'long',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });
        console.log('🗑️ Formatted ISO EXDATE:', exdate, '->', formatted);
        return formatted;
      }
      
      console.log('🗑️ Could not parse EXDATE, returning original:', exdate);
      return exdate; // Return original if parsing fails
    } catch (error) {
      console.error('🗑️ Error formatting EXDATE:', error);
      return exdate;
    }
  }

  // Recurrence helper methods
  initializeRecurrenceProperties(): void {
    if (!this.editedEvent?.recurrence) return;
    
    const recurrence = this.editedEvent.recurrence;
    
    // Initialize end type
    if (recurrence.count) {
      this.endType = 'count';
    } else if (recurrence.until) {
      this.endType = 'until';
    } else {
      this.endType = 'never';
    }
    
    // Initialize month type
    if (recurrence.bySetPos) {
      this.monthType = 'position';
      this.selectedDayOfWeek = recurrence.byDay?.[0] || 'MO';
    } else {
      this.monthType = 'day';
    }
  }

  getRecurrenceIntervalText(): string {
    if (!this.editedEvent?.recurrence?.frequency) return '';
    
    const frequency = this.editedEvent.recurrence.frequency;
    const interval = this.editedEvent.recurrence.interval || 1;
    
    if (interval === 1) {
      return frequency === 'daily' ? 'day' : 
             frequency === 'weekly' ? 'week' : 
             frequency === 'monthly' ? 'month' : 
             frequency === 'annually' ? 'year' : '';
    } else {
      return frequency === 'daily' ? 'days' : 
             frequency === 'weekly' ? 'weeks' : 
             frequency === 'monthly' ? 'months' : 
             frequency === 'annually' ? 'years' : '';
    }
  }

  getEndType(): string {
    return this.endType;
  }

  setEndType(value: string): void {
    this.endType = value as 'never' | 'count' | 'until';
    
    if (!this.editedEvent?.recurrence) return;
    
    // Clear other end conditions
    if (value === 'never') {
      delete this.editedEvent.recurrence.count;
      delete this.editedEvent.recurrence.until;
    } else if (value === 'count') {
      delete this.editedEvent.recurrence.until;
      if (!this.editedEvent.recurrence.count) {
        this.editedEvent.recurrence.count = 1;
      }
    } else if (value === 'until') {
      delete this.editedEvent.recurrence.count;
      if (!this.editedEvent.recurrence.until) {
        // Set until date to 1 year from start date
        const startDate = new Date(this.editedEvent.start_time);
        const untilDate = new Date(startDate);
        untilDate.setFullYear(untilDate.getFullYear() + 1);
        this.editedEvent.recurrence.until = untilDate.toISOString().split('T')[0];
      }
    }
  }

  getMonthType(): string {
    return this.monthType;
  }

  setMonthType(value: string): void {
    this.monthType = value as 'day' | 'position';
    
    if (!this.editedEvent?.recurrence) return;
    
    if (value === 'day') {
      delete this.editedEvent.recurrence.bySetPos;
      delete this.editedEvent.recurrence.byDay;
      if (!this.editedEvent.recurrence.byMonthDay) {
        this.editedEvent.recurrence.byMonthDay = [1];
      }
    } else if (value === 'position') {
      delete this.editedEvent.recurrence.byMonthDay;
      if (!this.editedEvent.recurrence.bySetPos) {
        this.editedEvent.recurrence.bySetPos = 1;
      }
      if (!this.editedEvent.recurrence.byDay) {
        this.editedEvent.recurrence.byDay = ['MO'];
      }
    }
  }

  updateRecurrenceInterval(value: number): void {
    if (this.editedEvent?.recurrence) {
      this.editedEvent.recurrence.interval = Math.max(1, value);
    }
  }

  updateRecurrenceCount(value: number): void {
    if (this.editedEvent?.recurrence) {
      this.editedEvent.recurrence.count = Math.max(1, value);
    }
  }

  updateRecurrenceUntil(value: string): void {
    if (this.editedEvent?.recurrence) {
      this.editedEvent.recurrence.until = value;
    }
  }

  isDaySelected(dayValue: string): boolean {
    return this.editedEvent?.recurrence?.byDay?.includes(dayValue) || false;
  }

  toggleDayOfWeek(dayValue: string): void {
    if (!this.editedEvent?.recurrence) return;
    
    if (!this.editedEvent.recurrence.byDay) {
      this.editedEvent.recurrence.byDay = [];
    }
    
    const index = this.editedEvent.recurrence.byDay.indexOf(dayValue);
    if (index > -1) {
      this.editedEvent.recurrence.byDay.splice(index, 1);
    } else {
      this.editedEvent.recurrence.byDay.push(dayValue);
    }
    
    // Ensure at least one day is selected
    if (this.editedEvent.recurrence.byDay.length === 0) {
      this.editedEvent.recurrence.byDay.push(dayValue);
    }
  }

  updateMonthDay(value: number): void {
    if (this.editedEvent?.recurrence) {
      this.editedEvent.recurrence.byMonthDay = [Math.max(1, Math.min(31, value))];
    }
  }

  updateBySetPos(value: number): void {
    if (this.editedEvent?.recurrence) {
      this.editedEvent.recurrence.bySetPos = value;
    }
  }

  updateSelectedDayOfWeek(value: string): void {
    this.selectedDayOfWeek = value;
    if (this.editedEvent?.recurrence) {
      this.editedEvent.recurrence.byDay = [value];
    }
  }

  addSpecificDate(): void {
    if (!this.editedEvent?.recurrence) return;
    
    if (!this.editedEvent.recurrence.specificDates) {
      this.editedEvent.recurrence.specificDates = [];
    }
    
    // Add today's date as default
    const today = new Date().toISOString().split('T')[0];
    this.editedEvent.recurrence.specificDates.push(today);
  }

  removeSpecificDate(index: number): void {
    if (this.editedEvent?.recurrence?.specificDates) {
      this.editedEvent.recurrence.specificDates.splice(index, 1);
    }
  }

  updateSpecificDate(index: number, value: string): void {
    if (this.editedEvent?.recurrence?.specificDates) {
      this.editedEvent.recurrence.specificDates[index] = value;
    }
  }
}
