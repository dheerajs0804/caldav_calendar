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
      console.log('🔄 Event detail modal initialized with event:', this.event);
      console.log('🔄 Edited event created:', this.editedEvent);
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
    return this.calendars.find(cal => cal.id === this.event!.calendar_id);
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

  onSave(): void {
    if (this.editedEvent) {
      console.log('💾 Saving edited event:', this.editedEvent);
      console.log('🕐 Start time:', this.editedEvent.start_time);
      console.log('🕐 End time:', this.editedEvent.end_time);
      console.log('📅 Calendar ID being saved:', this.editedEvent.calendar_id);
      console.log('📅 Calendar ID type:', typeof this.editedEvent.calendar_id);
      this.editEvent.emit(this.editedEvent);
      this.isEditMode = false;
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
}
