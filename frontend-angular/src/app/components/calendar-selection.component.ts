import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
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

@Component({
  selector: 'app-calendar-selection',
  standalone: true,
  imports: [CommonModule],
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
            (click)="selectCalendar(calendar)"
          >
            <div class="calendar-info">
              <h3>{{ calendar.name }}</h3>
              <p *ngIf="calendar.description" class="description">{{ calendar.description }}</p>
              <p class="url">{{ calendar.url }}</p>
            </div>
            <div class="select-arrow">→</div>
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
      cursor: pointer;
      transition: all 0.2s ease;
    }
    
    .calendar-item:hover {
      border-color: #667eea;
      transform: translateY(-2px);
      box-shadow: 0 8px 16px rgba(102, 126, 234, 0.2);
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
  `]
})
export class CalendarSelectionComponent implements OnInit {
  calendars: Calendar[] = [];
  loading: boolean = true;
  error: string = '';

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

    this.http.get<CalendarResponse>('http://localhost:8000/calendars/user').subscribe({
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
}
