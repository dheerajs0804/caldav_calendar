import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  private baseUrl: string;

  constructor(private http: HttpClient) {
    // Use environment variable if available, otherwise fallback to hardcoded URL
    this.baseUrl = (window as any).API_URL || environment.apiUrl;
    console.log('🔗 Using API Base URL:', this.baseUrl);
  }

  // Calendar methods
  getCalendars(): Observable<any> {
    return this.http.get(`${this.baseUrl}/calendars/user`);
  }

  createCalendar(calendarData: any): Observable<any> {
    return this.http.post(`${this.baseUrl}/calendars`, calendarData);
  }

  updateCalendar(calendarId: string, data: any): Observable<any> {
    return this.http.put(`${this.baseUrl}/calendars/${calendarId}/toggle`, data);
  }

  deleteCalendar(calendarId: string): Observable<any> {
    return this.http.delete(`${this.baseUrl}/calendars/${calendarId}`);
  }

  // Event methods
  getEvents(calendarUrl?: string): Observable<any> {
    const url = calendarUrl 
      ? `${this.baseUrl}/events?calendar_url=${encodeURIComponent(calendarUrl)}`
      : `${this.baseUrl}/events`;
    return this.http.get(url);
  }

  createEvent(eventData: any): Observable<any> {
    return this.http.post(`${this.baseUrl}/events`, eventData);
  }

  updateEvent(eventId: string, eventData: any): Observable<any> {
    return this.http.put(`${this.baseUrl}/events/${eventId}`, eventData);
  }

  deleteEvent(eventId: string, calendarUrl: string, action: string = 'single'): Observable<any> {
    return this.http.delete(`${this.baseUrl}/events/${eventId}?calendar_url=${encodeURIComponent(calendarUrl)}&action=${action}`);
  }

  // Auth methods
  login(credentials: any): Observable<any> {
    return this.http.post(`${this.baseUrl}/auth/login`, credentials);
  }

  ssoLogin(): Observable<any> {
    return this.http.post(`${this.baseUrl}/auth/sso-login`, {});
  }
}
