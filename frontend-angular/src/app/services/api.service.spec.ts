import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { of, throwError } from 'rxjs';

import { ApiService } from './api.service';

describe('ApiService', () => {
  let service: ApiService;
  let httpMock: HttpTestingController;

  const mockCalendar = {
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

  const mockEvent = {
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

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [ApiService]
    });

    service = TestBed.inject(ApiService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should use environment API URL by default', () => {
    expect(service['baseUrl']).toBe(environment.apiUrl);
  });

  it('should use window API_URL if available', () => {
    (window as any).API_URL = 'http://custom-api-url';
    
    const newService = new ApiService(TestBed.inject(HttpClientTestingModule));
    
    expect(newService['baseUrl']).toBe('http://custom-api-url');
  });

  describe('Calendar Methods', () => {
    it('should get calendars', () => {
      const mockResponse = {
        success: true,
        data: [mockCalendar]
      };

      service.getCalendars().subscribe(response => {
        expect(response).toEqual(mockResponse);
      });

      const req = httpMock.expectOne(`${service['baseUrl']}/calendars/user`);
      expect(req.request.method).toBe('GET');
      req.flush(mockResponse);
    });

    it('should create calendar', () => {
      const calendarData = {
        name: 'New Calendar',
        color: '#4285f4',
        url: 'http://test-caldav-server/new-calendar/'
      };

      const mockResponse = {
        success: true,
        data: { ...mockCalendar, ...calendarData }
      };

      service.createCalendar(calendarData).subscribe(response => {
        expect(response).toEqual(mockResponse);
      });

      const req = httpMock.expectOne(`${service['baseUrl']}/calendars`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual(calendarData);
      req.flush(mockResponse);
    });

    it('should update calendar', () => {
      const calendarId = '1';
      const updateData = { enabled: false };

      const mockResponse = {
        success: true,
        data: { ...mockCalendar, enabled: false }
      };

      service.updateCalendar(calendarId, updateData).subscribe(response => {
        expect(response).toEqual(mockResponse);
      });

      const req = httpMock.expectOne(`${service['baseUrl']}/calendars/${calendarId}/toggle`);
      expect(req.request.method).toBe('PUT');
      expect(req.request.body).toEqual(updateData);
      req.flush(mockResponse);
    });

    it('should delete calendar', () => {
      const calendarId = '1';

      const mockResponse = {
        success: true,
        message: 'Calendar deleted successfully'
      };

      service.deleteCalendar(calendarId).subscribe(response => {
        expect(response).toEqual(mockResponse);
      });

      const req = httpMock.expectOne(`${service['baseUrl']}/calendars/${calendarId}`);
      expect(req.request.method).toBe('DELETE');
      req.flush(mockResponse);
    });
  });

  describe('Event Methods', () => {
    it('should get events without calendar URL', () => {
      const mockResponse = {
        success: true,
        data: [mockEvent]
      };

      service.getEvents().subscribe(response => {
        expect(response).toEqual(mockResponse);
      });

      const req = httpMock.expectOne(`${service['baseUrl']}/events`);
      expect(req.request.method).toBe('GET');
      req.flush(mockResponse);
    });

    it('should get events with calendar URL', () => {
      const calendarUrl = 'http://test-caldav-server/calendar1/';
      const mockResponse = {
        success: true,
        data: [mockEvent]
      };

      service.getEvents(calendarUrl).subscribe(response => {
        expect(response).toEqual(mockResponse);
      });

      const req = httpMock.expectOne(
        `${service['baseUrl']}/events?calendar_url=${encodeURIComponent(calendarUrl)}`
      );
      expect(req.request.method).toBe('GET');
      req.flush(mockResponse);
    });

    it('should create event', () => {
      const eventData = {
        title: 'New Event',
        start_time: '2024-01-01T10:00:00Z',
        end_time: '2024-01-01T11:00:00Z',
        calendar_id: 1
      };

      const mockResponse = {
        success: true,
        data: { ...mockEvent, ...eventData }
      };

      service.createEvent(eventData).subscribe(response => {
        expect(response).toEqual(mockResponse);
      });

      const req = httpMock.expectOne(`${service['baseUrl']}/events`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual(eventData);
      req.flush(mockResponse);
    });

    it('should update event', () => {
      const eventId = 'event1';
      const updateData = {
        title: 'Updated Event',
        description: 'Updated description'
      };

      const mockResponse = {
        success: true,
        data: { ...mockEvent, ...updateData }
      };

      service.updateEvent(eventId, updateData).subscribe(response => {
        expect(response).toEqual(mockResponse);
      });

      const req = httpMock.expectOne(`${service['baseUrl']}/events/${eventId}`);
      expect(req.request.method).toBe('PUT');
      expect(req.request.body).toEqual(updateData);
      req.flush(mockResponse);
    });

    it('should delete event with default action', () => {
      const eventId = 'event1';
      const calendarUrl = 'http://test-caldav-server/calendar1/';

      const mockResponse = {
        success: true,
        message: 'Event deleted successfully'
      };

      service.deleteEvent(eventId, calendarUrl).subscribe(response => {
        expect(response).toEqual(mockResponse);
      });

      const req = httpMock.expectOne(
        `${service['baseUrl']}/events/${eventId}?calendar_url=${encodeURIComponent(calendarUrl)}&action=single`
      );
      expect(req.request.method).toBe('DELETE');
      req.flush(mockResponse);
    });

    it('should delete event with custom action', () => {
      const eventId = 'event1';
      const calendarUrl = 'http://test-caldav-server/calendar1/';
      const action = 'all';

      const mockResponse = {
        success: true,
        message: 'All occurrences deleted successfully'
      };

      service.deleteEvent(eventId, calendarUrl, action).subscribe(response => {
        expect(response).toEqual(mockResponse);
      });

      const req = httpMock.expectOne(
        `${service['baseUrl']}/events/${eventId}?calendar_url=${encodeURIComponent(calendarUrl)}&action=${action}`
      );
      expect(req.request.method).toBe('DELETE');
      req.flush(mockResponse);
    });
  });

  describe('Auth Methods', () => {
    it('should login with credentials', () => {
      const credentials = {
        username: 'testuser',
        password: 'testpassword'
      };

      const mockResponse = {
        success: true,
        message: 'Login successful',
        data: {
          user: {
            username: 'testuser',
            calendars: 1
          }
        }
      };

      service.login(credentials).subscribe(response => {
        expect(response).toEqual(mockResponse);
      });

      const req = httpMock.expectOne(`${service['baseUrl']}/auth/login`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual(credentials);
      req.flush(mockResponse);
    });

    it('should perform SSO login', () => {
      const mockResponse = {
        success: true,
        message: 'SSO login successful',
        data: {
          user: {
            username: 'testuser',
            calendars: 1
          }
        }
      };

      service.ssoLogin().subscribe(response => {
        expect(response).toEqual(mockResponse);
      });

      const req = httpMock.expectOne(`${service['baseUrl']}/auth/sso-login`);
      expect(req.request.method).toBe('POST');
      expect(req.request.body).toEqual({});
      req.flush(mockResponse);
    });
  });

  describe('Error Handling', () => {
    it('should handle HTTP errors in getCalendars', () => {
      service.getCalendars().subscribe({
        next: () => fail('Should not succeed'),
        error: error => {
          expect(error).toBeTruthy();
        }
      });

      const req = httpMock.expectOne(`${service['baseUrl']}/calendars/user`);
      req.error(new ErrorEvent('Network error'));
    });

    it('should handle HTTP errors in createEvent', () => {
      const eventData = {
        title: 'New Event',
        start_time: '2024-01-01T10:00:00Z',
        end_time: '2024-01-01T11:00:00Z',
        calendar_id: 1
      };

      service.createEvent(eventData).subscribe({
        next: () => fail('Should not succeed'),
        error: error => {
          expect(error).toBeTruthy();
        }
      });

      const req = httpMock.expectOne(`${service['baseUrl']}/events`);
      req.error(new ErrorEvent('Server error'));
    });

    it('should handle malformed responses', () => {
      service.getCalendars().subscribe({
        next: response => {
          expect(response).toBeTruthy();
        },
        error: () => fail('Should not throw error')
      });

      const req = httpMock.expectOne(`${service['baseUrl']}/calendars/user`);
      req.flush('invalid json response');
    });
  });

  describe('URL Encoding', () => {
    it('should properly encode calendar URLs with special characters', () => {
      const calendarUrl = 'http://test-caldav-server/calendar with spaces/';
      
      service.getEvents(calendarUrl).subscribe();

      const req = httpMock.expectOne(
        `${service['baseUrl']}/events?calendar_url=${encodeURIComponent(calendarUrl)}`
      );
      expect(req.request.url).toContain(encodeURIComponent(calendarUrl));
    });

    it('should properly encode calendar URLs with query parameters', () => {
      const calendarUrl = 'http://test-caldav-server/calendar?param=value&other=test';
      
      service.getEvents(calendarUrl).subscribe();

      const req = httpMock.expectOne(
        `${service['baseUrl']}/events?calendar_url=${encodeURIComponent(calendarUrl)}`
      );
      expect(req.request.url).toContain(encodeURIComponent(calendarUrl));
    });
  });

  describe('Request Headers', () => {
    it('should include proper content type for POST requests', () => {
      const eventData = {
        title: 'New Event',
        start_time: '2024-01-01T10:00:00Z',
        end_time: '2024-01-01T11:00:00Z',
        calendar_id: 1
      };

      service.createEvent(eventData).subscribe();

      const req = httpMock.expectOne(`${service['baseUrl']}/events`);
      expect(req.request.headers.get('Content-Type')).toBe('application/json');
    });

    it('should include proper content type for PUT requests', () => {
      const updateData = { title: 'Updated Event' };

      service.updateEvent('event1', updateData).subscribe();

      const req = httpMock.expectOne(`${service['baseUrl']}/events/event1`);
      expect(req.request.headers.get('Content-Type')).toBe('application/json');
    });
  });

  afterEach(() => {
    httpMock.verify();
  });
});

