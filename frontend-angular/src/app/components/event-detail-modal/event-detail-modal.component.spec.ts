import { ComponentFixture, TestBed } from '@angular/core/testing';
import { FormsModule } from '@angular/forms';
import { of, throwError } from 'rxjs';

import { EventDetailModalComponent } from './event-detail-modal.component';
import { EmailService } from '../../services/email.service';
import { CalendarEvent, Calendar } from '../../interfaces/calendar-event.interface';

describe('EventDetailModalComponent', () => {
  let component: EventDetailModalComponent;
  let fixture: ComponentFixture<EventDetailModalComponent>;
  let emailService: jasmine.SpyObj<EmailService>;

  const mockCalendar: Calendar = {
    id: 1,
    name: 'Test Calendar',
    color: '#4285f4',
    url: 'http://test-caldav-server/calendar1/',
    userId: 1,
    isActive: true,
    enabled: true,
    createdAt: '2024-01-01T00:00:00Z',
    updatedAt: '2024-01-01T00:00:00Z'
  };

  const mockEvent: CalendarEvent = {
    id: 'event1',
    title: 'Test Event',
    description: 'Test event description',
    start_time: '2024-01-01T10:00:00Z',
    end_time: '2024-01-01T11:00:00Z',
    all_day: false,
    calendar_id: 1,
    calendar_name: 'Test Calendar',
    calendar_color: '#4285f4',
    availability: 'busy',
    status: 'confirmed'
  };

  const mockRecurringEvent: CalendarEvent = {
    ...mockEvent,
    recurrence: {
      frequency: 'weekly',
      interval: 1,
      count: 10
    }
  };

  beforeEach(async () => {
    const emailServiceSpy = jasmine.createSpyObj('EmailService', [
      'sendEventInvitation'
    ]);

    await TestBed.configureTestingModule({
      imports: [
        EventDetailModalComponent,
        FormsModule
      ],
      providers: [
        { provide: EmailService, useValue: emailServiceSpy }
      ]
    }).compileComponents();

    fixture = TestBed.createComponent(EventDetailModalComponent);
    component = fixture.componentInstance;
    emailService = TestBed.inject(EmailService) as jasmine.SpyObj<EmailService>;

    // Setup default spy returns
    emailService.sendEventInvitation.and.returnValue(of({ success: true }));
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize with default values', () => {
    expect(component.event).toBeNull();
    expect(component.calendars).toEqual([]);
    expect(component.isVisible).toBe(false);
    expect(component.isEditMode).toBe(false);
    expect(component.editedEvent).toBeNull();
    expect(component.editScope).toBe('this');
  });

  it('should initialize edited event on changes', () => {
    component.event = mockEvent;
    component.calendars = [mockCalendar];

    component.ngOnChanges();

    expect(component.editedEvent).toBeTruthy();
    expect(component.editedEvent?.title).toBe(mockEvent.title);
    expect(component.editedEvent?.start_time).toBe(mockEvent.start_time);
    expect(component.editedEvent?.end_time).toBe(mockEvent.end_time);
  });

  it('should set default values for missing event properties', () => {
    const incompleteEvent: CalendarEvent = {
      id: 'event1',
      title: 'Test Event',
      start_time: '2024-01-01T10:00:00Z',
      end_time: '2024-01-01T11:00:00Z',
      all_day: false,
      calendar_id: 1
    };

    component.event = incompleteEvent;
    component.calendars = [mockCalendar];

    component.ngOnChanges();

    expect(component.editedEvent?.availability).toBe('busy');
    expect(component.editedEvent?.status).toBe('confirmed');
    expect(component.editedEvent?.attendees).toEqual([]);
    expect(component.editedEvent?.recurrence).toEqual({
      frequency: 'never',
      interval: 1
    });
    expect(component.editedEvent?.reminder).toEqual({
      enabled: false,
      type: 'message',
      time: 15,
      unit: 'minutes',
      relativeTo: 'start'
    });
  });

  it('should detect recurring events', () => {
    component.event = mockRecurringEvent;
    component.ngOnChanges();

    const isRecurring = component.isRecurringEvent();

    expect(isRecurring).toBe(true);
  });

  it('should detect non-recurring events', () => {
    component.event = mockEvent;
    component.ngOnChanges();

    const isRecurring = component.isRecurringEvent();

    expect(isRecurring).toBe(false);
  });

  it('should show recurrence editing for non-recurring events', () => {
    component.event = mockEvent;
    component.ngOnChanges();

    const shouldShow = component.shouldShowRecurrenceEditing();

    expect(shouldShow).toBe(true);
  });

  it('should not show recurrence editing for recurring instances', () => {
    const recurringInstance: CalendarEvent = {
      ...mockRecurringEvent,
      isRecurringInstance: true
    };

    component.event = recurringInstance;
    component.ngOnChanges();

    const shouldShow = component.shouldShowRecurrenceEditing();

    expect(shouldShow).toBe(false);
  });

  it('should show recurrence info for recurring instances', () => {
    const recurringInstance: CalendarEvent = {
      ...mockRecurringEvent,
      isRecurringInstance: true
    };

    component.event = recurringInstance;
    component.ngOnChanges();

    const shouldShow = component.shouldShowRecurrenceInfo();

    expect(shouldShow).toBe(true);
  });

  it('should enter edit mode', () => {
    component.event = mockEvent;
    component.ngOnChanges();

    component.editEvent();

    expect(component.isEditMode).toBe(true);
  });

  it('should exit edit mode', () => {
    component.isEditMode = true;

    component.cancelEdit();

    expect(component.isEditMode).toBe(false);
  });

  it('should close modal', () => {
    spyOn(component.closeModal, 'emit');

    component.close();

    expect(component.closeModal.emit).toHaveBeenCalled();
  });

  it('should save event successfully', () => {
    spyOn(component.editEvent, 'emit');
    component.event = mockEvent;
    component.ngOnChanges();
    component.isEditMode = true;

    component.onSave();

    expect(component.editEvent.emit).toHaveBeenCalledWith(
      jasmine.objectContaining({
        title: mockEvent.title,
        start_time: mockEvent.start_time,
        end_time: mockEvent.end_time
      })
    );
  });

  it('should handle calendar change', () => {
    component.event = mockEvent;
    component.calendars = [mockCalendar];
    component.ngOnChanges();

    const newCalendarId = '2';
    component.onCalendarChange(newCalendarId);

    expect(component.editedEvent?.calendar_id).toBe(newCalendarId);
  });

  it('should add attendee', () => {
    component.event = mockEvent;
    component.ngOnChanges();

    component.addAttendee();

    expect(component.editedEvent?.attendees?.length).toBe(1);
    expect(component.editedEvent?.attendees?.[0]).toEqual({
      email: '',
      name: '',
      response: 'NEEDS-ACTION',
      role: 'REQ-PARTICIPANT'
    });
  });

  it('should remove attendee', () => {
    component.event = mockEvent;
    component.ngOnChanges();
    component.addAttendee();
    component.addAttendee();

    component.removeAttendee(0);

    expect(component.editedEvent?.attendees?.length).toBe(1);
  });

  it('should send email invitation', () => {
    component.event = mockEvent;
    component.ngOnChanges();
    component.addAttendee();
    component.editedEvent!.attendees![0] = {
      email: 'test@example.com',
      name: 'Test User',
      response: 'NEEDS-ACTION',
      role: 'REQ-PARTICIPANT'
    };

    component.sendInvitation();

    expect(emailService.sendEventInvitation).toHaveBeenCalledWith(
      jasmine.objectContaining({
        event: jasmine.any(Object),
        attendees: jasmine.any(Array)
      })
    );
  });

  it('should handle email invitation error', () => {
    const errorMessage = 'Failed to send invitation';
    emailService.sendEventInvitation.and.returnValue(
      throwError(() => new Error(errorMessage))
    );

    component.event = mockEvent;
    component.ngOnChanges();
    component.addAttendee();

    component.sendInvitation();

    // Should not throw error, just log it
    expect(emailService.sendEventInvitation).toHaveBeenCalled();
  });

  it('should initialize recurrence properties', () => {
    component.event = mockRecurringEvent;
    component.ngOnChanges();

    expect(component.endType).toBe('count');
    expect(component.monthType).toBe('day');
  });

  it('should handle recurrence frequency change', () => {
    component.event = mockEvent;
    component.ngOnChanges();

    component.onRecurrenceFrequencyChange('weekly');

    expect(component.editedEvent?.recurrence?.frequency).toBe('weekly');
  });

  it('should handle recurrence interval change', () => {
    component.event = mockEvent;
    component.ngOnChanges();

    component.onRecurrenceIntervalChange(2);

    expect(component.editedEvent?.recurrence?.interval).toBe(2);
  });

  it('should handle recurrence end type change', () => {
    component.event = mockEvent;
    component.ngOnChanges();

    component.onRecurrenceEndTypeChange('until');

    expect(component.endType).toBe('until');
  });

  it('should handle recurrence count change', () => {
    component.event = mockEvent;
    component.ngOnChanges();

    component.onRecurrenceCountChange(5);

    expect(component.editedEvent?.recurrence?.count).toBe(5);
  });

  it('should handle recurrence until date change', () => {
    component.event = mockEvent;
    component.ngOnChanges();

    const untilDate = '2024-12-31';
    component.onRecurrenceUntilChange(untilDate);

    expect(component.editedEvent?.recurrence?.until).toBe(untilDate);
  });

  it('should handle day of week selection', () => {
    component.event = mockEvent;
    component.ngOnChanges();

    component.onDayOfWeekChange('TU');

    expect(component.selectedDayOfWeek).toBe('TU');
    expect(component.editedEvent?.recurrence?.byDay).toContain('TU');
  });

  it('should handle month type change', () => {
    component.event = mockEvent;
    component.ngOnChanges();

    component.onMonthTypeChange('position');

    expect(component.monthType).toBe('position');
  });

  it('should validate event data', () => {
    component.event = mockEvent;
    component.ngOnChanges();

    const isValid = component.validateEventData();

    expect(isValid).toBe(true);
  });

  it('should reject invalid event data', () => {
    component.event = mockEvent;
    component.ngOnChanges();
    component.editedEvent!.title = ''; // Empty title

    const isValid = component.validateEventData();

    expect(isValid).toBe(false);
  });

  it('should handle all-day event toggle', () => {
    component.event = mockEvent;
    component.ngOnChanges();

    component.onAllDayChange(true);

    expect(component.editedEvent?.all_day).toBe(true);
  });

  it('should handle availability change', () => {
    component.event = mockEvent;
    component.ngOnChanges();

    component.onAvailabilityChange('free');

    expect(component.editedEvent?.availability).toBe('free');
  });

  it('should handle status change', () => {
    component.event = mockEvent;
    component.ngOnChanges();

    component.onStatusChange('tentative');

    expect(component.editedEvent?.status).toBe('tentative');
  });

  it('should handle reminder toggle', () => {
    component.event = mockEvent;
    component.ngOnChanges();

    component.onReminderToggle(true);

    expect(component.editedEvent?.reminder?.enabled).toBe(true);
  });

  it('should handle reminder time change', () => {
    component.event = mockEvent;
    component.ngOnChanges();

    component.onReminderTimeChange(30);

    expect(component.editedEvent?.reminder?.time).toBe(30);
  });

  it('should handle reminder unit change', () => {
    component.event = mockEvent;
    component.ngOnChanges();

    component.onReminderUnitChange('hours');

    expect(component.editedEvent?.reminder?.unit).toBe('hours');
  });

  it('should handle edit scope change', () => {
    component.event = mockRecurringEvent;
    component.ngOnChanges();

    component.onEditScopeChange('all');

    expect(component.editScope).toBe('all');
  });

  it('should format date for input', () => {
    const date = '2024-01-01T10:00:00Z';
    const formatted = component.formatDateForInput(date);

    expect(formatted).toBe('2024-01-01');
  });

  it('should format time for input', () => {
    const date = '2024-01-01T10:30:00Z';
    const formatted = component.formatTimeForInput(date);

    expect(formatted).toBe('10:30');
  });

  it('should get event calendar', () => {
    component.event = mockEvent;
    component.calendars = [mockCalendar];

    const eventCalendar = component.getEventCalendar();

    expect(eventCalendar).toEqual(mockCalendar);
  });

  it('should return undefined for non-existent calendar', () => {
    component.event = { ...mockEvent, calendar_id: 999 };
    component.calendars = [mockCalendar];

    const eventCalendar = component.getEventCalendar();

    expect(eventCalendar).toBeUndefined();
  });

  it('should handle missing event gracefully', () => {
    component.event = null;
    component.calendars = [mockCalendar];

    expect(() => component.ngOnChanges()).not.toThrow();
    expect(component.editedEvent).toBeNull();
  });

  it('should handle missing calendars gracefully', () => {
    component.event = mockEvent;
    component.calendars = [];

    expect(() => component.ngOnChanges()).not.toThrow();
    expect(component.editedEvent).toBeTruthy();
  });
});

