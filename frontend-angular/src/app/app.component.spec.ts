import { ComponentFixture, TestBed } from '@angular/core/testing';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { FormsModule } from '@angular/forms';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { RouterTestingModule } from '@angular/router/testing';

import { AppComponent } from './app.component';
import { DayViewComponent } from './components/day-view/day-view.component';
import { WeekViewComponent } from './components/week-view/week-view.component';
import { MonthViewComponent } from './components/month-view/month-view.component';
import { EventDetailModalComponent } from './components/event-detail-modal/event-detail-modal.component';
import { ReminderNotificationComponent } from './components/reminder-notification.component';
import { SortByStartTimePipe } from './pipes/sort-by-start-time.pipe';
import { EmailService } from './services/email.service';

describe('AppComponent', () => {
  let component: AppComponent;
  let fixture: ComponentFixture<AppComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [
        BrowserAnimationsModule,
        FormsModule,
        HttpClientTestingModule,
        RouterTestingModule,
        AppComponent,
        DayViewComponent,
        WeekViewComponent,
        MonthViewComponent,
        EventDetailModalComponent,
        ReminderNotificationComponent,
        SortByStartTimePipe
      ],
      providers: [EmailService]
    }).compileComponents();

    fixture = TestBed.createComponent(AppComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create the app', () => {
    expect(component).toBeTruthy();
  });

  it('should have title "Mithi Calendar"', () => {
    const compiled = fixture.nativeElement as HTMLElement;
    expect(compiled.querySelector('h1')?.textContent).toContain('Mithi Calendar');
  });

  it('should initialize with week view', () => {
    expect(component.currentView.type).toBe('week');
  });

  it('should have 4 view options', () => {
    expect(component.views.length).toBe(4);
  });

  it('should have empty events array on initialization', () => {
    expect(component.events).toEqual([]);
  });

  it('should have empty calendars array on initialization', () => {
    expect(component.calendars).toEqual([]);
  });

  it('should have showAddEventModal as false initially', () => {
    expect(component.showAddEventModal).toBe(false);
  });

  it('should have showEventDetailModal as false initially', () => {
    expect(component.showEventDetailModal).toBe(false);
  });

  it('should have showReminderWindow as false initially', () => {
    expect(component.showReminderWindow).toBe(false);
  });

  it('should have empty activeReminders array initially', () => {
    expect(component.activeReminders).toEqual([]);
  });

  describe('Navigation Methods', () => {
    it('should go to previous day/week/month', () => {
      const initialDate = new Date(component.currentDate);
      component.goToPrevious();
      expect(component.currentDate.getTime()).toBeLessThan(initialDate.getTime());
    });

    it('should go to next day/week/month', () => {
      const initialDate = new Date(component.currentDate);
      component.goToNext();
      expect(component.currentDate.getTime()).toBeGreaterThan(initialDate.getTime());
    });

    it('should go to today', () => {
      const today = new Date();
      component.goToToday();
      expect(component.currentDate.toDateString()).toBe(today.toDateString());
    });
  });

  describe('View Methods', () => {
    it('should change view correctly', () => {
      component.changeView('day');
      expect(component.currentView.type).toBe('day');
      
      component.changeView('month');
      expect(component.currentView.type).toBe('month');
    });
  });

  describe('Modal Methods', () => {
    it('should open add event modal', () => {
      component.openAddEventModal();
      expect(component.showAddEventModal).toBe(true);
    });

    it('should open event detail modal', () => {
      const testEvent = {
        id: 'test',
        title: 'Test Event',
        description: 'Test Description',
        start_time: new Date().toISOString(),
        end_time: new Date().toISOString(),
        all_day: false,
        calendar_id: 1
      };
      component.openEventDetail(testEvent);
      expect(component.showEventDetailModal).toBe(true);
      expect(component.selectedEvent).toBe(testEvent);
    });

    it('should close event detail modal', () => {
      component.showEventDetailModal = true;
      component.selectedEvent = {} as any;
      component.closeEventDetail();
      expect(component.showEventDetailModal).toBe(false);
      expect(component.selectedEvent).toBeNull();
    });
  });

  describe('Reminder Methods', () => {
    it('should create reminder notification', () => {
      const testEvent = {
        id: 'test',
        title: 'Test Event',
        description: 'Test Description',
        start_time: new Date().toISOString(),
        end_time: new Date().toISOString(),
        all_day: false,
        calendar_id: 1,
        color: '#8b5cf6'
      };
      
      component.createReminderNotification(testEvent);
      expect(component.activeReminders.length).toBe(1);
      expect(component.showReminderWindow).toBe(true);
    });

    it('should dismiss reminder', () => {
      const testEvent = {
        id: 'test',
        title: 'Test Event',
        description: 'Test Description',
        start_time: new Date().toISOString(),
        end_time: new Date().toISOString(),
        all_day: false,
        calendar_id: 1,
        color: '#8b5cf6'
      };
      
      component.createReminderNotification(testEvent);
      const reminderId = component.activeReminders[0].id;
      component.onReminderDismissed(reminderId);
      expect(component.activeReminders.length).toBe(0);
      expect(component.showReminderWindow).toBe(false);
    });
  });

  describe('Utility Methods', () => {
    it('should get week date range', () => {
      const dateRange = component.getWeekDateRange();
      expect(dateRange).toMatch(/[A-Za-z]{3} \d+ - [A-Za-z]{3} \d+, \d{4}/);
    });

    it('should get events for date', () => {
      const testDate = new Date();
      const events = component.getEventsForDate(testDate);
      expect(Array.isArray(events)).toBe(true);
    });

    it('should get events for time slot', () => {
      const testDate = new Date();
      const events = component.getEventsForTimeSlot(testDate, 10);
      expect(Array.isArray(events)).toBe(true);
    });

    it('should format time correctly', () => {
      expect(component.formatTime(0)).toBe('12 AM');
      expect(component.formatTime(12)).toBe('12 PM');
      expect(component.formatTime(15)).toBe('3 PM');
      expect(component.formatTime(9)).toBe('9 AM');
    });
  });
});
