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
      // Create a copy of the event for editing
      this.editedEvent = { ...this.event };
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
      this.editEvent.emit(this.editedEvent);
      this.isEditMode = false;
    }
  }

  onCancel(): void {
    this.isEditMode = false;
    // Reset edited event to original
    if (this.event) {
      this.editedEvent = { ...this.event };
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
      this.editedEvent.start_time = target.value + ':00';
    }
  }

  onEndTimeChange(event: any): void {
    const target = event.target as HTMLInputElement;
    if (this.editedEvent && target.value) {
      this.editedEvent.end_time = target.value + ':00';
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
}
