import { Component, Input, Output, EventEmitter, OnInit, OnDestroy, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { trigger, state, style, transition, animate } from '@angular/animations';

export interface ReminderEvent {
  id: string;
  title: string;
  description?: string;
  start_time: string;
  end_time: string;
  location?: string;
  color?: string;
}

export interface ReminderNotification {
  id: string;
  event: ReminderEvent;
  timestamp: Date;
  snoozed?: boolean;
  snoozeUntil?: Date;
}

@Component({
  selector: 'app-reminder-notification',
  standalone: true,
  imports: [CommonModule, FormsModule],
  animations: [
    trigger('slideIn', [
      transition(':enter', [
        style({ opacity: 0, transform: 'translateY(-20px) scale(0.95)' }),
        animate('300ms ease-out', style({ opacity: 1, transform: 'translateY(0) scale(1)' }))
      ])
    ])
  ],
  template: `
    <div class="reminder-overlay" *ngIf="isVisible" (click)="onOverlayClick($event)">
      <div class="reminder-window" [@slideIn]>
        <!-- Window Header -->
        <div class="reminder-header">
          <div class="reminder-title">
            <i class="bell-icon">🔔</i>
            <span>{{ activeReminders.length }} Reminder{{ activeReminders.length !== 1 ? 's' : '' }}</span>
          </div>
          <div class="window-controls">
            <button class="control-btn minimize" (click)="minimize()" title="Minimize">
              <span>─</span>
            </button>
            <button class="control-btn maximize" (click)="maximize()" title="Maximize">
              <span>□</span>
            </button>
            <button class="control-btn close" (click)="close()" title="Close">
              <span>×</span>
            </button>
          </div>
        </div>

        <!-- Reminders List -->
        <div class="reminders-container">
          <div 
            *ngFor="let reminder of activeReminders; trackBy: trackByReminderId" 
            class="reminder-item"
            [class.active]="reminder.id === selectedReminderId"
            (click)="selectReminder(reminder.id)"
          >
            <div class="reminder-content">
              <div class="reminder-icon">
                <i class="calendar-icon">📅</i>
              </div>
              <div class="reminder-details">
                <div class="reminder-title">{{ reminder.event.title }}</div>
                <div class="reminder-time">
                  {{ formatTimeRange(reminder.event.start_time, reminder.event.end_time) }}
                </div>
                <div class="reminder-location" *ngIf="reminder.event.location">
                  📍 {{ reminder.event.location }}
                </div>
                <div class="reminder-description" *ngIf="reminder.event.description">
                  {{ reminder.event.description }}
                </div>
                <a href="#" class="details-link" (click)="showDetails(reminder)">Details...</a>
              </div>
            </div>
            <div class="reminder-actions">
              <div class="action-buttons">
                <button class="action-btn snooze-btn" (click)="showSnoozeOptions(reminder.id, $event)">
                  <span>Snooze for</span>
                  <i class="chevron-down">▼</i>
                </button>
                <button class="action-btn dismiss-btn" (click)="dismissReminder(reminder.id)">
                  Dismiss
                </button>
              </div>
              
              <!-- Snooze Dropdown -->
              <div class="snooze-dropdown" *ngIf="activeSnoozeDropdown === reminder.id">
                <button class="snooze-option" (click)="snoozeReminder(reminder.id, 5)">
                  <span>5 minutes</span>
                </button>
                <button class="snooze-option" (click)="snoozeReminder(reminder.id, 15)">
                  <span>15 minutes</span>
                </button>
                <button class="snooze-option" (click)="snoozeReminder(reminder.id, 30)">
                  <span>30 minutes</span>
                </button>
                <button class="snooze-option" (click)="snoozeReminder(reminder.id, 60)">
                  <span>1 hour</span>
                </button>
                <button class="snooze-option" (click)="snoozeReminder(reminder.id, 1440)">
                  <span>1 day</span>
                </button>
                <button class="snooze-option custom" (click)="showCustomSnooze(reminder.id)">
                  <span>Custom time...</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Bottom Actions -->
        <div class="bottom-actions">
          <button class="bottom-btn snooze-all-btn" (click)="showSnoozeAllOptions($event)">
            <span>Snooze All for</span>
            <i class="chevron-down">▼</i>
          </button>
          <button class="bottom-btn dismiss-all-btn" (click)="dismissAllReminders()">
            Dismiss All
          </button>
        </div>

        <!-- Snooze All Dropdown -->
        <div class="snooze-all-dropdown" *ngIf="showSnoozeAllDropdown">
          <button class="snooze-option" (click)="snoozeAllReminders(5)">
            <span>5 minutes</span>
          </button>
          <button class="snooze-option" (click)="snoozeAllReminders(15)">
            <span>15 minutes</span>
          </button>
          <button class="snooze-option" (click)="snoozeAllReminders(30)">
            <span>30 minutes</span>
          </button>
          <button class="snooze-option" (click)="snoozeAllReminders(60)">
            <span>1 hour</span>
          </button>
          <button class="snooze-option" (click)="snoozeAllReminders(1440)">
            <span>1 day</span>
          </button>
        </div>
      </div>
    </div>
  `,
  styleUrls: ['./reminder-notification.component.scss']
})
export class ReminderNotificationComponent implements OnInit, OnDestroy {
  @Input() reminders: ReminderNotification[] = [];
  @Output() reminderDismissed = new EventEmitter<string>();
  @Output() reminderSnoozed = new EventEmitter<{id: string, minutes: number}>();
  @Output() reminderDetails = new EventEmitter<ReminderNotification>();
  @Output() windowClosed = new EventEmitter<void>();

  isVisible = false;
  activeReminders: ReminderNotification[] = [];
  selectedReminderId: string | null = null;
  activeSnoozeDropdown: string | null = null;
  showSnoozeAllDropdown = false;
  private clickOutsideListener?: (event: Event) => void;

  constructor(private cdr: ChangeDetectorRef) {}

  ngOnInit() {
    console.log('Reminder component - ngOnInit called');
    this.updateActiveReminders();
    this.setupClickOutsideListener();
  }

  ngOnChanges() {
    console.log('Reminder component - ngOnChanges called');
    console.log('Reminder component - Input reminders:', this.reminders);
    this.updateActiveReminders();
  }

  ngOnDestroy() {
    if (this.clickOutsideListener) {
      document.removeEventListener('click', this.clickOutsideListener);
    }
  }

  updateActiveReminders() {
    const now = new Date();
    this.activeReminders = this.reminders.filter(reminder => {
      if (reminder.snoozed && reminder.snoozeUntil) {
        return reminder.snoozeUntil <= now;
      }
      return true;
    });
    this.isVisible = this.activeReminders.length > 0;
    console.log('Reminder component - Active reminders:', this.activeReminders.length, 'Visible:', this.isVisible);
    console.log('Reminder component - Reminders input:', this.reminders);
    console.log('Reminder component - Active reminders:', this.activeReminders);
    
    // Force change detection
    this.cdr.detectChanges();
  }

  setupClickOutsideListener() {
    this.clickOutsideListener = (event: Event) => {
      const target = event.target as HTMLElement;
      if (!target.closest('.reminder-window') && !target.closest('.reminder-overlay')) {
        this.closeDropdowns();
      }
    };
    document.addEventListener('click', this.clickOutsideListener);
  }

  trackByReminderId(index: number, reminder: ReminderNotification): string {
    return reminder.id;
  }

  selectReminder(reminderId: string) {
    this.selectedReminderId = reminderId;
  }

  showSnoozeOptions(reminderId: string, event: Event) {
    event.stopPropagation();
    this.activeSnoozeDropdown = this.activeSnoozeDropdown === reminderId ? null : reminderId;
    this.showSnoozeAllDropdown = false;
  }

  showSnoozeAllOptions(event: Event) {
    event.stopPropagation();
    this.showSnoozeAllDropdown = !this.showSnoozeAllDropdown;
    this.activeSnoozeDropdown = null;
  }

  snoozeReminder(reminderId: string, minutes: number) {
    this.reminderSnoozed.emit({ id: reminderId, minutes });
    this.closeDropdowns();
  }

  snoozeAllReminders(minutes: number) {
    this.activeReminders.forEach(reminder => {
      this.reminderSnoozed.emit({ id: reminder.id, minutes });
    });
    this.closeDropdowns();
  }

  dismissReminder(reminderId: string) {
    this.reminderDismissed.emit(reminderId);
  }

  dismissAllReminders() {
    this.activeReminders.forEach(reminder => {
      this.reminderDismissed.emit(reminder.id);
    });
  }

  showDetails(reminder: ReminderNotification) {
    this.reminderDetails.emit(reminder);
  }

  showCustomSnooze(reminderId: string) {
    // TODO: Implement custom snooze time picker
    console.log('Custom snooze for reminder:', reminderId);
    this.closeDropdowns();
  }

  closeDropdowns() {
    this.activeSnoozeDropdown = null;
    this.showSnoozeAllDropdown = false;
  }

  onOverlayClick(event: Event) {
    if (event.target === event.currentTarget) {
      this.close();
    }
  }

  minimize() {
    // TODO: Implement minimize functionality
    console.log('Minimize clicked');
  }

  maximize() {
    // TODO: Implement maximize functionality
    console.log('Maximize clicked');
  }

  close() {
    this.isVisible = false;
    this.windowClosed.emit();
  }

  formatTimeRange(startTime: string, endTime: string): string {
    const start = new Date(startTime);
    const end = new Date(endTime);
    
    const startStr = start.toLocaleDateString('en-US', { 
      day: '2-digit', 
      month: 'long', 
      year: 'numeric' 
    });
    
    const startTimeStr = start.toLocaleTimeString('en-US', { 
      hour: '2-digit', 
      minute: '2-digit',
      hour12: false 
    });
    
    const endTimeStr = end.toLocaleTimeString('en-US', { 
      hour: '2-digit', 
      minute: '2-digit',
      hour12: false 
    });
    
    return `${startStr} ${startTimeStr} – ${endTimeStr}`;
  }
}
