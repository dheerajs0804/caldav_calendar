import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';

interface Calendar {
  id: number;
  name: string;
  url: string;
  color: string;
  description?: string;
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
          <h1>📅 Select Your Calendar</h1>
          <p>Choose which calendar you'd like to view</p>
        </div>
        
        <div *ngIf="loading" class="loading">
          🔄 Loading your calendars...
        </div>
        
        <div *ngIf="error" class="error">
          ❌ {{ error }}
        </div>
        
        <div *ngIf="!loading && !error && calendars.length > 0" class="calendar-list">
          <div 
            *ngFor="let calendar of calendars" 
            class="calendar-item"
            [style.border-left-color]="calendar.color"
          >
            <div class="calendar-info" (click)="selectCalendar(calendar)">
              <h3>{{ calendar.name }}</h3>
              <p *ngIf="calendar.description" class="description">{{ calendar.description }}</p>
              <p class="url">{{ calendar.url }}</p>
            </div>
            <div class="calendar-actions">
              <button 
                class="delete-btn" 
                (click)="deleteCalendar(calendar, $event)"
                [disabled]="deletingCalendarId === calendar.id"
                title="Delete calendar"
              >
                {{ deletingCalendarId === calendar.id ? '⏳' : '🗑️' }}
              </button>
              <div class="select-arrow" (click)="selectCalendar(calendar)">→</div>
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
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 20px;
    }
    
    .calendar-selection-card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
      padding: 40px;
      width: 100%;
      max-width: 600px;
    }
    
    .header {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .header h1 {
      margin: 0 0 10px 0;
      color: #333;
      font-size: 28px;
      font-weight: 600;
    }
    
    .header p {
      margin: 0;
      color: #666;
      font-size: 16px;
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
    }
    
    .no-calendars {
      color: #666;
    }
    
    .calendar-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-bottom: 30px;
    }
    
    .calendar-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 20px;
      border: 2px solid #e1e5e9;
      border-radius: 8px;
      border-left: 4px solid #4285f4;
      transition: all 0.2s ease;
    }
    
    .calendar-item:hover {
      border-color: #667eea;
      transform: translateY(-2px);
      box-shadow: 0 8px 16px rgba(102, 126, 234, 0.2);
    }
    
    .calendar-info {
      flex: 1;
      cursor: pointer;
    }
    
    .calendar-actions {
      display: flex;
      align-items: center;
      gap: 12px;
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
      text-align: center;
      margin-top: 30px;
    }
    
    .back-btn {
      padding: 12px 24px;
      background: #6b7280;
      color: white;
      border: none;
      border-radius: 8px;
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
  loading: boolean = true;
  error: string = '';
  
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
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadCalendars();
  }

  loadCalendars(): void {
    this.loading = true;
    this.error = '';

    this.http.get<CalendarResponse>('http://localhost:8000/calendars/user', { withCredentials: true }).subscribe({
      next: (response) => {
        this.loading = false;
        if (response.success && response.data) {
          this.calendars = response.data.calendars;
        } else {
          this.error = response.message || 'Failed to load calendars';
        }
      },
      error: (error) => {
        this.loading = false;
        this.error = 'Connection error. Please try again.';
        console.error('Calendar loading error:', error);
      }
    });
  }

  selectCalendar(calendar: Calendar): void {
    // Store the selected calendar in localStorage
    localStorage.setItem('selectedCalendar', JSON.stringify(calendar));
    
    // Navigate to the calendar view
    this.router.navigate(['/calendar']);
  }

  goBack(): void {
    this.router.navigate(['/login']);
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

    this.http.post<CreateCalendarResponse>('http://localhost:8000/calendars', {
      name: this.newCalendar.name.trim(),
      description: this.newCalendar.description?.trim() || '',
      color: this.newCalendar.color
    }, { withCredentials: true }).subscribe({
      next: (response) => {
        this.creatingCalendar = false;
        if (response.success && response.data) {
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
    
    this.http.delete<DeleteCalendarResponse>(`http://localhost:8000/calendars/${calendar.id}`, { 
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
