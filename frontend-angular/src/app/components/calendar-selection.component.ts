import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { ColorRegistryService } from '../services/color-registry.service';
import { LoglevelLoggingService } from '../services/loglevel-logging.service';
import { environment } from '../../environments/environment';

interface Calendar {
  id: number;
  name: string;
  url: string;
  color: string;
  description?: string;
  enabled?: boolean;
}

interface CalendarResponse {
  success: boolean;
  message: string;
  data?: {
    calendars: Calendar[];
  };
}

interface CreateCalendarRequest {
  name: string;
  description?: string;
  color?: string;
}

interface CreateCalendarResponse {
  success: boolean;
  message: string;
  data?: Calendar;
}

interface DeleteCalendarResponse {
  success: boolean;
  message: string;
}

@Component({
  selector: 'app-calendar-selection',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="calendar-selection-container">
      <div class="calendar-selection-card">
        <div class="header">
          <h1>Calendars</h1>
          <div class="header-actions">
            <button class="menu-btn" title="More options">⋮</button>
          </div>
        </div>
        
        <div class="search-bar">
          <div class="search-input">
            <span class="search-icon">🔍</span>
            <input type="text" placeholder="Find calendars..." [(ngModel)]="searchTerm" />
          </div>
        </div>
        
        <div *ngIf="loading" class="loading">
          🔄 Loading your calendars...
        </div>
        
        <div *ngIf="error" class="error">
          <div class="error-header">
            ❌ {{ error }}
          </div>
          <div *ngIf="errorSuggestions && errorSuggestions.length > 0" class="error-suggestions">
            <h4>💡 Suggestions:</h4>
            <ul>
              <li *ngFor="let suggestion of errorSuggestions">{{ suggestion }}</li>
            </ul>
          </div>
          <div class="error-actions">
            <button (click)="retryLoadCalendars()" class="retry-btn">
              🔄 Retry
            </button>
            <button (click)="goBack()" class="back-btn">
              ← Back to Login
            </button>
          </div>
        </div>
        
        <div *ngIf="!loading && !error && filteredCalendars.length > 0" class="calendar-list">
          <div 
            *ngFor="let calendar of filteredCalendars" 
            class="calendar-item"
            [class.selected]="calendar.enabled"
            [style.border-left-color]="getCalendarColor(calendar.name)"
          >
            <div class="calendar-icon" [style.color]="getCalendarColor(calendar.name)">
              📅
            </div>
            <div class="calendar-info">
              <h3>{{ calendar.name }}</h3>
              <p *ngIf="calendar.description" class="description">{{ calendar.description }}</p>
            </div>
            <div class="calendar-actions">
              <div class="toggle-switch" (click)="toggleCalendar(calendar, $event)">
                <div class="toggle-slider" [class.active]="calendar.enabled"></div>
              </div>
              <button class="view-btn" (click)="viewCalendar(calendar)" title="View calendar">
                👁️
              </button>
            </div>
          </div>
          
          <!-- Add Calendar Button -->
          <div class="add-calendar-item" (click)="openAddCalendarModal()">
            <div class="add-calendar-info">
              <h3>➕ Add New Calendar</h3>
              <p class="description">Create a new calendar for your events</p>
            </div>
            <div class="select-arrow">+</div>
          </div>
        </div>
        
        <div *ngIf="!loading && !error && calendars.length === 0" class="no-calendars">
          <p>No calendars found. Please check your CalDAV server configuration.</p>
        </div>
        
        <div class="footer">
          <button (click)="goToCalendarView()" class="continue-btn" [disabled]="enabledCalendarsCount === 0">
            Continue to Calendar View
          </button>
          <button (click)="goBack()" class="back-btn">
            ← Back to Login
          </button>
        </div>
      </div>
    </div>
    
    <!-- Add Calendar Modal -->
    <div *ngIf="showAddCalendarModal" class="modal-overlay" (click)="closeAddCalendarModal()">
      <div class="modal-content" (click)="$event.stopPropagation()">
        <div class="modal-header">
          <h2>➕ Add New Calendar</h2>
          <button (click)="closeAddCalendarModal()" class="close-btn">×</button>
        </div>
        
        <div class="modal-body">
          <form (ngSubmit)="createCalendar()" #calendarForm="ngForm">
            <div class="form-group">
              <label for="calendarName">Calendar Name *</label>
              <input 
                type="text" 
                id="calendarName" 
                [(ngModel)]="newCalendar.name" 
                name="calendarName"
                required
                placeholder="Enter calendar name"
              />
            </div>
            
            <div class="form-group">
              <label for="calendarDescription">Description</label>
              <textarea 
                id="calendarDescription" 
                [(ngModel)]="newCalendar.description" 
                name="calendarDescription"
                placeholder="Optional description"
                rows="3"
              ></textarea>
            </div>
            
            <div class="form-group">
              <label for="calendarColor">Color</label>
              <div class="color-picker">
                <input 
                  type="color" 
                  id="calendarColor" 
                  [(ngModel)]="newCalendar.color" 
                  name="calendarColor"
                />
                <span>{{ newCalendar.color }}</span>
              </div>
            </div>
          </form>
        </div>
        
        <div class="modal-footer">
          <button (click)="closeAddCalendarModal()" class="btn btn-secondary">
            Cancel
          </button>
          <button (click)="createCalendar()" class="btn btn-primary" [disabled]="!newCalendar.name.trim()">
            {{ creatingCalendar ? 'Creating...' : 'Create Calendar' }}
          </button>
        </div>
      </div>
    </div>
  `,
  styles: [`
    .calendar-selection-container {
      min-height: 100vh;
      background: #f5f5f5;
      padding: 20px;
    }
    
    .calendar-selection-card {
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 800px;
      margin: 0 auto;
    }
    
    .header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 20px 24px;
      border-bottom: 1px solid #e1e5e9;
      background: #f8f9fa;
    }
    
    .header h1 {
      margin: 0;
      color: #333;
      font-size: 24px;
      font-weight: 600;
    }
    
    .header-actions {
      display: flex;
      align-items: center;
    }
    
    .menu-btn {
      background: none;
      border: none;
      font-size: 18px;
      color: #666;
      cursor: pointer;
      padding: 8px;
      border-radius: 4px;
    }
    
    .menu-btn:hover {
      background: #e9ecef;
    }
    
    .search-bar {
      padding: 16px 24px;
      border-bottom: 1px solid #e1e5e9;
    }
    
    .search-input {
      position: relative;
      display: flex;
      align-items: center;
    }
    
    .search-icon {
      position: absolute;
      left: 12px;
      color: #666;
      font-size: 16px;
    }
    
    .search-input input {
      width: 100%;
      padding: 12px 12px 12px 40px;
      border: 1px solid #e1e5e9;
      border-radius: 6px;
      font-size: 16px;
      background: white;
    }
    
    .search-input input:focus {
      outline: none;
      border-color: #4285f4;
      box-shadow: 0 0 0 2px rgba(66, 133, 244, 0.1);
    }
    
    .loading, .error, .no-calendars {
      text-align: center;
      padding: 40px 20px;
      font-size: 16px;
    }
    
    .loading {
      color: #667eea;
    }
    
    .error {
      color: #e53e3e;
      background: #fed7d7;
      border-radius: 8px;
      padding: 20px;
      margin: 20px;
    }

    .error-header {
      font-weight: 600;
      margin-bottom: 15px;
      font-size: 16px;
    }

    .error-suggestions {
      margin: 15px 0;
      padding: 15px;
      background: #fef5e7;
      border-radius: 6px;
      border-left: 4px solid #f6ad55;
    }

    .error-suggestions h4 {
      margin: 0 0 10px 0;
      color: #c05621;
      font-size: 14px;
      font-weight: 600;
    }

    .error-suggestions ul {
      margin: 0;
      padding-left: 20px;
    }

    .error-suggestions li {
      margin-bottom: 5px;
      color: #744210;
      font-size: 14px;
    }

    .error-actions {
      display: flex;
      gap: 10px;
      margin-top: 15px;
    }

    .retry-btn {
      padding: 8px 16px;
      background: #4299e1;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .retry-btn:hover {
      background: #3182ce;
    }
    
    .no-calendars {
      color: #666;
    }
    
    .calendar-list {
      padding: 16px 24px;
    }
    
    .calendar-item {
      display: flex;
      align-items: center;
      padding: 16px;
      border-radius: 8px;
      margin-bottom: 8px;
      transition: all 0.2s ease;
      border-left: 4px solid transparent;
    }
    
    .calendar-item:hover {
      background: #f8f9fa;
    }
    
    .calendar-item.selected {
      background: #e3f2fd;
      border-left-color: #4285f4;
    }
    
    .calendar-icon {
      font-size: 20px;
      margin-right: 16px;
      width: 24px;
      text-align: center;
    }
    
    .calendar-info {
      flex: 1;
    }
    
    .calendar-info h3 {
      margin: 0 0 4px 0;
      color: #333;
      font-size: 16px;
      font-weight: 500;
    }
    
    .calendar-info .description {
      margin: 0;
      color: #666;
      font-size: 14px;
    }
    
    .calendar-actions {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    
    .toggle-switch {
      position: relative;
      width: 44px;
      height: 24px;
      background: #e1e5e9;
      border-radius: 12px;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }
    
    .toggle-switch:hover {
      background: #d1d5db;
    }
    
    .toggle-slider {
      position: absolute;
      top: 2px;
      left: 2px;
      width: 20px;
      height: 20px;
      background: white;
      border-radius: 50%;
      transition: transform 0.2s ease;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .toggle-switch.active {
      background: #4285f4;
    }
    
    .toggle-switch.active .toggle-slider {
      transform: translateX(20px);
    }
    
    .view-btn {
      background: none;
      border: none;
      font-size: 16px;
      color: #666;
      cursor: pointer;
      padding: 8px;
      border-radius: 4px;
    }
    
    .view-btn:hover {
      background: #e9ecef;
    }
    
    .delete-btn {
      background: #fee2e2;
      border: 1px solid #fecaca;
      border-radius: 6px;
      padding: 8px 12px;
      cursor: pointer;
      font-size: 16px;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      min-width: 40px;
      height: 40px;
    }
    
    .delete-btn:hover:not(:disabled) {
      background: #fecaca;
      border-color: #f87171;
      transform: scale(1.05);
    }
    
    .delete-btn:disabled {
      background: #f3f4f6;
      border-color: #d1d5db;
      cursor: not-allowed;
      transform: none;
    }
    
    .calendar-info h3 {
      margin: 0 0 8px 0;
      color: #333;
      font-size: 18px;
      font-weight: 600;
    }
    
    .calendar-info .description {
      margin: 0 0 8px 0;
      color: #666;
      font-size: 14px;
    }
    
    .calendar-info .url {
      margin: 0;
      color: #999;
      font-size: 12px;
      font-family: monospace;
    }
    
    .select-arrow {
      color: #667eea;
      font-size: 20px;
      font-weight: bold;
    }
    
    .footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 24px;
      border-top: 1px solid #e1e5e9;
      background: #f8f9fa;
    }
    
    .continue-btn {
      padding: 12px 24px;
      background: #4285f4;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    
    .continue-btn:hover:not(:disabled) {
      background: #3367d6;
    }
    
    .continue-btn:disabled {
      background: #ccc;
      cursor: not-allowed;
    }
    
    .back-btn {
      padding: 12px 24px;
      background: #6b7280;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    
    .back-btn:hover {
      background: #4b5563;
    }
    
    .add-calendar-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 20px;
      background: #f8f9fa;
      border: 2px dashed #667eea;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    
    .add-calendar-item:hover {
      background: #e3f2fd;
      border-color: #4285f4;
      transform: translateY(-2px);
    }
    
    .add-calendar-info h3 {
      margin: 0 0 8px 0;
      color: #667eea;
      font-size: 18px;
      font-weight: 600;
    }
    
    .add-calendar-info .description {
      margin: 0;
      color: #666;
      font-size: 14px;
    }
    
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }
    
    .modal-content {
      background: white;
      border-radius: 12px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
      width: 90%;
      max-width: 500px;
      max-height: 90vh;
      overflow-y: auto;
    }
    
    .modal-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 20px 24px;
      border-bottom: 1px solid #e1e5e9;
    }
    
    .modal-header h2 {
      margin: 0;
      color: #333;
      font-size: 20px;
      font-weight: 600;
    }
    
    .close-btn {
      background: none;
      border: none;
      font-size: 24px;
      color: #666;
      cursor: pointer;
      padding: 0;
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 4px;
    }
    
    .close-btn:hover {
      background: #f3f4f6;
      color: #333;
    }
    
    .modal-body {
      padding: 24px;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: #333;
      font-size: 14px;
    }
    
    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid #e1e5e9;
      border-radius: 8px;
      font-size: 16px;
      transition: border-color 0.2s ease;
      box-sizing: border-box;
    }
    
    .form-group input:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .color-picker {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .color-picker input[type="color"] {
      width: 50px;
      height: 40px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    
    .color-picker span {
      font-family: monospace;
      font-size: 14px;
      color: #666;
    }
    
    .modal-footer {
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      padding: 20px 24px;
      border-top: 1px solid #e1e5e9;
    }
    
    .btn {
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    
    .btn-primary {
      background: #667eea;
      color: white;
    }
    
    .btn-primary:hover:not(:disabled) {
      background: #5a67d8;
    }
    
    .btn-primary:disabled {
      background: #ccc;
      cursor: not-allowed;
    }
    
    .btn-secondary {
      background: #6b7280;
      color: white;
    }
    
    .btn-secondary:hover {
      background: #4b5563;
    }
  `]
})
export class CalendarSelectionComponent implements OnInit {
  calendars: Calendar[] = [];
  filteredCalendars: Calendar[] = [];
  loading: boolean = true;
  error: string = '';
  errorSuggestions: string[] = [];
  searchTerm: string = '';
  
  // Add Calendar Modal Properties
  showAddCalendarModal: boolean = false;
  creatingCalendar: boolean = false;
  newCalendar: CreateCalendarRequest = {
    name: '',
    description: '',
    color: '#4285f4'
  };
  
  // Delete Calendar Properties
  deletingCalendarId: number | null = null;

  constructor(
    private http: HttpClient,
    private router: Router,
    private colorRegistry: ColorRegistryService,
    private loggingService: LoglevelLoggingService
  ) {}

  ngOnInit(): void {
    this.loadCalendars();
  }

  getCalendarColor(calendarName: string): string {
    // 🎨 Thunderbird-style: Get color from local registry
    return this.colorRegistry.getCalendarColor(calendarName);
  }

  get enabledCalendarsCount(): number {
    return this.calendars.filter(cal => cal.enabled).length;
  }

  loadCalendars(): void {
    this.loading = true;
    this.error = '';
    this.errorSuggestions = [];

    this.http.get<CalendarResponse>(`${environment.apiUrl}/calendars/user`, { withCredentials: true }).subscribe({
      next: (response) => {
        this.loading = false;
        if (response.success && response.data) {
          this.calendars = response.data.calendars.map(cal => ({
            ...cal,
            enabled: cal.enabled ?? true // Default to enabled if not specified
          }));
          this.filterCalendars();
        } else {
          this.error = response.message || 'Failed to load calendars';
          // Check if the response includes suggestions
          if ((response as any).suggestions && Array.isArray((response as any).suggestions)) {
            this.errorSuggestions = (response as any).suggestions;
          }
        }
      },
      error: (error) => {
        this.loading = false;
        this.error = 'Connection error. Please try again.';
        this.errorSuggestions = [
          'Check if the backend server is running',
          'Verify your network connection',
          'Try refreshing the page',
          'Contact your administrator if the problem persists'
        ];
        this.loggingService.error('Calendar loading failed', {
          error: error.message,
          status: error.status,
          url: error.url
        });
      }
    });
  }

  filterCalendars(): void {
    if (!this.searchTerm.trim()) {
      this.filteredCalendars = [...this.calendars];
    } else {
      const term = this.searchTerm.toLowerCase();
      this.filteredCalendars = this.calendars.filter(cal => 
        cal.name.toLowerCase().includes(term) ||
        (cal.description && cal.description.toLowerCase().includes(term))
      );
    }
  }

  toggleCalendar(calendar: Calendar, event: Event): void {
    event.stopPropagation();
    calendar.enabled = !calendar.enabled;
    
    // Update the calendar state on the server
    this.updateCalendarState(calendar);
  }

  updateCalendarState(calendar: Calendar): void {
    this.http.put(`${environment.apiUrl}/calendars/${calendar.id}/toggle`, {
      enabled: calendar.enabled
    }, { withCredentials: true }).subscribe({
      next: (response) => {
        console.log('Calendar state updated:', calendar.name, calendar.enabled);
      },
      error: (error) => {
        console.error('Failed to update calendar state:', error);
        // Revert the change on error
        calendar.enabled = !calendar.enabled;
      }
    });
  }

  viewCalendar(calendar: Calendar): void {
    // Store the selected calendar and navigate to calendar view
    localStorage.setItem('selectedCalendar', JSON.stringify(calendar));
    this.router.navigate(['/calendar']);
  }

  goToCalendarView(): void {
    // Store all enabled calendars and navigate to calendar view
    const enabledCalendars = this.calendars.filter(cal => cal.enabled);
    localStorage.setItem('enabledCalendars', JSON.stringify(enabledCalendars));
    
    // If only one calendar is enabled, select it as the primary calendar
    if (enabledCalendars.length === 1) {
      localStorage.setItem('selectedCalendar', JSON.stringify(enabledCalendars[0]));
    }
    
    this.router.navigate(['/calendar']);
  }

  goBack(): void {
    this.router.navigate(['/login']);
  }

  retryLoadCalendars(): void {
    console.log('🔄 Retrying calendar load...');
    this.loadCalendars();
  }

  // Add Calendar Modal Methods
  openAddCalendarModal(): void {
    this.showAddCalendarModal = true;
    this.newCalendar = {
      name: '',
      description: '',
      color: '#4285f4'
    };
  }

  closeAddCalendarModal(): void {
    this.showAddCalendarModal = false;
    this.creatingCalendar = false;
    this.newCalendar = {
      name: '',
      description: '',
      color: '#4285f4'
    };
  }

  createCalendar(): void {
    if (!this.newCalendar.name.trim()) {
      return;
    }

    this.creatingCalendar = true;

    this.http.post<CreateCalendarResponse>(`${environment.apiUrl}/calendars`, {
      name: this.newCalendar.name.trim(),
      description: this.newCalendar.description?.trim() || '',
      color: this.newCalendar.color
    }, { withCredentials: true }).subscribe({
      next: (response) => {
        this.creatingCalendar = false;
        if (response.success && response.data) {
          // 🎨 Store color locally in color registry
          this.colorRegistry.setCalendarColor(response.data.name, this.newCalendar.color || '#4285f4');
          console.log(`🎨 Stored color for calendar '${response.data.name}': ${this.newCalendar.color || '#4285f4'}`);
          
          // Add the new calendar to the list
          this.calendars.push(response.data);
          this.closeAddCalendarModal();
          
          // Show success message (you could add a toast notification here)
          console.log('Calendar created successfully:', response.data);
        } else {
          this.error = response.message || 'Failed to create calendar';
        }
      },
      error: (error) => {
        this.creatingCalendar = false;
        this.error = 'Failed to create calendar. Please try again.';
        console.error('Calendar creation error:', error);
      }
    });
  }
  
  deleteCalendar(calendar: Calendar, event: Event): void {
    event.stopPropagation(); // Prevent triggering selectCalendar
    
    if (!confirm(`Are you sure you want to delete "${calendar.name}"? This action cannot be undone.`)) {
      return;
    }
    
    this.deletingCalendarId = calendar.id;
    
    this.http.delete<DeleteCalendarResponse>(`${environment.apiUrl}/calendars/${calendar.id}`, { 
      withCredentials: true 
    }).subscribe({
      next: (response) => {
        this.deletingCalendarId = null;
        if (response.success) {
          // Remove the calendar from the list
          this.calendars = this.calendars.filter(c => c.id !== calendar.id);
          console.log('Calendar deleted successfully:', calendar.name);
        } else {
          this.error = response.message || 'Failed to delete calendar';
        }
      },
      error: (error) => {
        this.deletingCalendarId = null;
        this.error = 'Failed to delete calendar. Please try again.';
        console.error('Calendar deletion error:', error);
      }
    });
  }
}
