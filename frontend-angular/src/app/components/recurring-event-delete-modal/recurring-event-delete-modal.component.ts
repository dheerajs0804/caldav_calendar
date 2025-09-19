import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';

export type RecurringDeleteAction = 'current' | 'all';

@Component({
  selector: 'app-recurring-event-delete-modal',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './recurring-event-delete-modal.component.html',
  styleUrls: ['./recurring-event-delete-modal.component.scss']
})
export class RecurringEventDeleteModalComponent {
  @Input() isVisible: boolean = false;
  @Input() event: any = null;
  @Output() deleteConfirmed = new EventEmitter<RecurringDeleteAction>();
  @Output() cancelled = new EventEmitter<void>();

  onDelete(action: RecurringDeleteAction): void {
    console.log('🗑️ Modal button clicked:', action);
    console.log('🗑️ Event in modal:', this.event);
    this.deleteConfirmed.emit(action);
  }

  onCancel(): void {
    this.cancelled.emit();
  }

  getEventTitle(): string {
    return this.event?.title || 'this event';
  }

  getEventTime(): string {
    if (!this.event) return '';
    
    const startTime = new Date(this.event.start_time);
    const endTime = new Date(this.event.end_time);
    
    if (this.event.all_day) {
      return startTime.toLocaleDateString();
    }
    
    const startStr = startTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const endStr = endTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    
    return `${startStr} - ${endStr}`;
  }

  getRecurrenceDescription(): string {
    if (!this.event?.recurrence) return '';
    
    const recurrence = this.event.recurrence;
    const frequency = recurrence.frequency;
    const interval = recurrence.interval || 1;
    
    let description = '';
    
    switch (frequency) {
      case 'daily':
        description = interval === 1 ? 'Daily' : `Every ${interval} days`;
        break;
      case 'weekly':
        description = interval === 1 ? 'Weekly' : `Every ${interval} weeks`;
        break;
      case 'monthly':
        description = interval === 1 ? 'Monthly' : `Every ${interval} months`;
        break;
      case 'annually':
        description = interval === 1 ? 'Annually' : `Every ${interval} years`;
        break;
      case 'ondates':
        description = 'On specific dates';
        break;
      default:
        description = 'Recurring';
    }
    
    if (recurrence.count) {
      description += ` (${recurrence.count} occurrences)`;
    } else if (recurrence.until) {
      const untilDate = new Date(recurrence.until);
      description += ` (until ${untilDate.toLocaleDateString()})`;
    }
    
    return description;
  }
}
