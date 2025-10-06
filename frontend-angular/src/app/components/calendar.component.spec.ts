import { ComponentFixture, TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { ChangeDetectorRef } from '@angular/core';
import { of, throwError } from 'rxjs';

import { CalendarComponent } from './calendar.component';
import { AuthService } from '../services/auth.service';
import { ApiService } from '../services/api.service';
import { EmailService } from '../services/email.service';
import { ColorRegistryService } from '../services/color-registry.service';
import { RecurrenceExpansionService } from '../services/recurrence-expansion.service';
import { CalendarEvent, Calendar } from '../interfaces/calendar-event.interface';

describe('CalendarComponent', () => {
  let component: CalendarComponent;
  let fixture: ComponentFixture<CalendarComponent>;
  let httpMock: HttpTestingController;
  let authService: jasmine.SpyObj<AuthService>;
  let apiService: jasmine.SpyObj<ApiService>;
  let emailService: jasmine.SpyObj<EmailService>;
  let colorRegistryService: jasmine.SpyObj<ColorRegistryService>;
  let recurrenceExpansionService: jasmine.SpyObj<RecurrenceExpansionService>;

  const mockCalendars: Calendar[] = [
    {
      id: 1,
      name: 'Test Calendar',
      color: '#4285f4',
      url: 'http://test-caldav-server/calendar1/',
      userId: 1,
      isActive: true,
      enabled: true,
      createdAt: '2024-01-01T00:00:00Z',
      updatedAt: '2024-01-01T00:00:00Z'
    }
  ];

  const mockEvents: CalendarEvent[] = [
    {
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
    }
  ];

  beforeEach(async () => {
    const authServiceSpy = jasmine.createSpyObj('AuthService', [
      'getCurrentUser', 'isLoggedIn', 'logout', 'currentUser'
    ]);
    const apiServiceSpy = jasmine.createSpyObj('ApiService', [
      'getCalendars', 'getEvents', 'createEvent', 'updateEvent', 'deleteEvent'
    ]);
    const emailServiceSpy = jasmine.createSpyObj('EmailService', [
      'sendEventInvitation'
    ]);
    const colorRegistryServiceSpy = jasmine.createSpyObj('ColorRegistryService', [
      'getColor', 'setColor'
    ]);
    const recurrenceExpansionServiceSpy = jasmine.createSpyObj('RecurrenceExpansionService', [
      'expandEvents'
    ]);

    await TestBed.configureTestingModule({
      imports: [
        CalendarComponent,
        HttpClientTestingModule,
        RouterTestingModule
      ],
      providers: [
        { provide: AuthService, useValue: authServiceSpy },
        { provide: ApiService, useValue: apiServiceSpy },
        { provide: EmailService, useValue: emailServiceSpy },
        { provide: ColorRegistryService, useValue: colorRegistryServiceSpy },
        { provide: RecurrenceExpansionService, useValue: recurrenceExpansionServiceSpy },
        ChangeDetectorRef
      ]
    }).compileComponents();

    fixture = TestBed.createComponent(CalendarComponent);
    component = fixture.componentInstance;
    httpMock = TestBed.inject(HttpTestingController);
    authService = TestBed.inject(AuthService) as jasmine.SpyObj<AuthService>;
    apiService = TestBed.inject(ApiService) as jasmine.SpyObj<ApiService>;
    emailService = TestBed.inject(EmailService) as jasmine.SpyObj<EmailService>;
    colorRegistryService = TestBed.inject(ColorRegistryService) as jasmine.SpyObj<ColorRegistryService>;
    recurrenceExpansionService = TestBed.inject(RecurrenceExpansionService) as jasmine.SpyObj<RecurrenceExpansionService>;

    // Setup default spy returns
    authService.getCurrentUser.and.returnValue({ username: 'testuser', calendars: 1 });
    authService.isLoggedIn.and.returnValue(true);
    authService.currentUser = of({ username: 'testuser', calendars: 1 });
    apiService.getCalendars.and.returnValue(of({ success: true, data: mockCalendars }));
    apiService.getEvents.and.returnValue(of({ success: true, data: mockEvents }));
    colorRegistryService.getColor.and.returnValue('#4285f4');
    recurrenceExpansionService.expandEvents.and.returnValue(mockEvents);
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize with default values', () => {
    expect(component.currentView.type).toBe('week');
    expect(component.loading).toBe(true);
    expect(component.error).toBeNull();
    expect(component.calendars).toEqual([]);
    expect(component.events).toEqual([]);
  });

  it('should load calendars on init', () => {
    component.ngOnInit();

    expect(apiService.getCalendars).toHaveBeenCalled();
    expect(component.calendars).toEqual(mockCalendars);
  });

  it('should handle calendar loading error', () => {
    const errorMessage = 'Failed to load calendars';
    apiService.getCalendars.and.returnValue(throwError(() => new Error(errorMessage)));

    component.ngOnInit();

    expect(component.error).toBe(errorMessage);
    expect(component.loading).toBe(false);
  });

  it('should change view correctly', () => {
    const newView = { type: 'month', label: 'Month', icon: '📅' };
    
    component.changeView(newView);

    expect(component.currentView).toBe(newView);
  });

  it('should navigate to previous period', () => {
    const initialDate = component.currentDate;
    
    component.previous();

    expect(component.currentDate.isBefore(initialDate)).toBe(true);
  });

  it('should navigate to next period', () => {
    const initialDate = component.currentDate;
    
    component.next();

    expect(component.currentDate.isAfter(initialDate)).toBe(true);
  });

  it('should navigate to today', () => {
    const today = dayjs();
    
    component.today();

    expect(component.currentDate.format('YYYY-MM-DD')).toBe(today.format('YYYY-MM-DD'));
  });

  it('should open add event modal', () => {
    component.openAddEventModal();

    expect(component.showAddEventModal).toBe(true);
  });

  it('should close add event modal', () => {
    component.showAddEventModal = true;
    
    component.closeAddEventModal();

    expect(component.showAddEventModal).toBe(false);
  });

  it('should create event successfully', () => {
    const eventData = {
      summary: 'New Event',
      start_date: '2024-01-01',
      start_time: '10:00',
      end_date: '2024-01-01',
      end_time: '11:00',
      calendar_id: 1
    };

    apiService.createEvent.and.returnValue(of({ success: true, data: mockEvents[0] }));

    component.createEvent(eventData);

    expect(apiService.createEvent).toHaveBeenCalledWith(jasmine.objectContaining({
      summary: 'New Event',
      calendar_id: 1
    }));
    expect(component.showAddEventModal).toBe(false);
  });

  it('should handle event creation error', () => {
    const eventData = {
      summary: 'New Event',
      start_date: '2024-01-01',
      start_time: '10:00',
      end_date: '2024-01-01',
      end_time: '11:00',
      calendar_id: 1
    };

    const errorMessage = 'Failed to create event';
    apiService.createEvent.and.returnValue(throwError(() => new Error(errorMessage)));

    component.createEvent(eventData);

    expect(component.error).toBe(errorMessage);
  });

  it('should open event detail modal', () => {
    const event = mockEvents[0];
    
    component.onEditEvent(event);

    expect(component.selectedEvent).toBe(event);
    expect(component.showEventDetailModal).toBe(true);
  });

  it('should close event detail modal', () => {
    component.showEventDetailModal = true;
    component.selectedEvent = mockEvents[0];
    
    component.closeEventDetailModal();

    expect(component.showEventDetailModal).toBe(false);
    expect(component.selectedEvent).toBeNull();
  });

  it('should delete event successfully', () => {
    const event = mockEvents[0];
    apiService.deleteEvent.and.returnValue(of({ success: true }));

    component.deleteEvent(event);

    expect(apiService.deleteEvent).toHaveBeenCalledWith(
      event.id,
      event.calendar_url,
      'single'
    );
  });

  it('should handle event deletion error', () => {
    const event = mockEvents[0];
    const errorMessage = 'Failed to delete event';
    apiService.deleteEvent.and.returnValue(throwError(() => new Error(errorMessage)));

    component.deleteEvent(event);

    expect(component.error).toBe(errorMessage);
  });

  it('should toggle sidebar', () => {
    const initialSidebarState = component.showSidebar;
    
    component.toggleSidebar();

    expect(component.showSidebar).toBe(!initialSidebarState);
  });

  it('should filter calendars by search term', () => {
    component.calendars = mockCalendars;
    component.searchTerm = 'Test';
    
    const filtered = component.filteredCalendars;

    expect(filtered.length).toBe(1);
    expect(filtered[0].name).toBe('Test Calendar');
  });

  it('should get calendar color', () => {
    const color = component.getCalendarColor('Test Calendar');

    expect(colorRegistryService.getColor).toHaveBeenCalledWith('Test Calendar');
    expect(color).toBe('#4285f4');
  });

  it('should get enabled calendars count', () => {
    component.calendars = mockCalendars;
    
    const count = component.enabledCalendarsCount;

    expect(count).toBe(1);
  });

  it('should handle logout', () => {
    spyOn(component, 'logout').and.callThrough();
    
    component.logout();

    expect(authService.logout).toHaveBeenCalled();
  });

  it('should check for reminders', () => {
    component.events = mockEvents;
    component.notifiedEvents = new Set();
    
    component.checkForReminders();

    // Should not throw error and should process events
    expect(component.events.length).toBe(1);
  });

  it('should handle recurring event deletion', () => {
    const recurringEvent = {
      ...mockEvents[0],
      recurrence: { frequency: 'weekly', interval: 1 }
    };
    
    component.onDeleteRecurringEvent(recurringEvent);

    expect(component.recurringDeleteEvent).toBe(recurringEvent);
    expect(component.showRecurringDeleteModal).toBe(true);
  });

  it('should export calendar', () => {
    component.openExportModal();

    expect(component.showExportModal).toBe(true);
  });

  it('should import calendar', () => {
    component.openImportModal();

    expect(component.showImportModal).toBe(true);
  });

  it('should print calendar', () => {
    spyOn(window, 'print');
    
    component.printCalendar();

    expect(window.print).toHaveBeenCalled();
  });

  it('should handle view change with data refresh', () => {
    spyOn(component, 'loadEvents');
    
    const newView = { type: 'day', label: 'Day', icon: '📅' };
    component.changeView(newView);

    expect(component.currentView).toBe(newView);
    expect(component.loadEvents).toHaveBeenCalled();
  });

  it('should unsubscribe on destroy', () => {
    spyOn(component, 'ngOnDestroy').and.callThrough();
    
    component.ngOnDestroy();

    expect(component.ngOnDestroy).toHaveBeenCalled();
  });

  it('should handle calendar selection', () => {
    const calendar = mockCalendars[0];
    
    component.onCalendarSelect(calendar);

    expect(component.selectedCalendar).toBe(calendar);
    expect(component.loadEvents).toHaveBeenCalled();
  });

  it('should handle calendar toggle', () => {
    const calendar = mockCalendars[0];
    apiService.updateCalendar.and.returnValue(of({ success: true }));

    component.onCalendarToggle(calendar);

    expect(apiService.updateCalendar).toHaveBeenCalledWith(
      calendar.id.toString(),
      { enabled: !calendar.enabled }
    );
  });

  it('should validate event data', () => {
    const validEvent = {
      summary: 'Valid Event',
      start_date: '2024-01-01',
      start_time: '10:00',
      end_date: '2024-01-01',
      end_time: '11:00',
      calendar_id: 1
    };

    const isValid = component.validateEventData(validEvent);

    expect(isValid).toBe(true);
  });

  it('should reject invalid event data', () => {
    const invalidEvent = {
      summary: '', // Empty title
      start_date: '2024-01-01',
      start_time: '10:00',
      end_date: '2024-01-01',
      end_time: '09:00', // End before start
      calendar_id: 1
    };

    const isValid = component.validateEventData(invalidEvent);

    expect(isValid).toBe(false);
  });

  afterEach(() => {
    httpMock.verify();
  });
});

