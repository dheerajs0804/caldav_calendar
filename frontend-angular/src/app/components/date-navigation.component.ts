import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import * as dayjs from 'dayjs';
import * as weekOfYear from 'dayjs/plugin/weekOfYear';
import * as isoWeek from 'dayjs/plugin/isoWeek';

// Extend dayjs with week plugins
dayjs.extend(weekOfYear);
dayjs.extend(isoWeek);

@Component({
  selector: 'app-date-navigation',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="calendar-widget">
      <div class="widget-header">
        <h3>📅 Navigation</h3>
      </div>
      
      <!-- Small Calendar -->
      <div class="mini-calendar">
        <!-- Month/Year Navigation -->
        <div class="calendar-nav">
          <button (click)="previousMonth()" class="nav-arrow">◀</button>
          <div class="month-year">
            <select [(ngModel)]="selectedMonth" (change)="onMonthChange()" class="month-select">
              <option *ngFor="let month of months" [value]="month.value">{{ month.name }}</option>
            </select>
            <select [(ngModel)]="selectedYear" (change)="onYearChange()" class="year-select">
              <option *ngFor="let year of yearRange" [value]="year">{{ year }}</option>
            </select>
          </div>
          <button (click)="nextMonth()" class="nav-arrow">▶</button>
        </div>
        
        <!-- Calendar Grid -->
        <div class="calendar-grid">
          <!-- Day Headers -->
          <div class="day-headers">
            <div *ngFor="let day of weekDays" class="day-header">{{ day }}</div>
          </div>
          
          <!-- Calendar Days -->
          <div class="calendar-days">
            <div 
              *ngFor="let day of calendarDays" 
              class="calendar-day"
              [class.other-month]="!day.isCurrentMonth"
              [class.today]="day.isToday"
              [class.selected]="day.isSelected"
              (click)="selectDate(day.date)"
            >
              {{ day.dayNumber }}
            </div>
          </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
          <button (click)="goToToday()" class="quick-btn today-btn">Today</button>
        </div>
      </div>
    </div>
  `,
  styles: [`
    .calendar-widget {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 8px;
    }
    
    .widget-header {
      text-align: center;
      margin-bottom: 15px;
    }
    
    .widget-header h3 {
      margin: 0;
      color: #495057;
      font-size: 16px;
      font-weight: 600;
    }
    
    .mini-calendar {
      background: white;
      border-radius: 6px;
      padding: 12px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .calendar-nav {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 12px;
    }
    
    .nav-arrow {
      background: #007bff;
      color: white;
      border: none;
      border-radius: 4px;
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 12px;
    }
    
    .nav-arrow:hover {
      background: #0056b3;
    }
    
    .month-year {
      display: flex;
      gap: 8px;
    }
    
    .month-select, .year-select {
      padding: 4px 8px;
      border: 1px solid #ced4da;
      border-radius: 4px;
      background: white;
      font-size: 12px;
      cursor: pointer;
    }
    
    .month-select {
      min-width: 80px;
    }
    
    .year-select {
      min-width: 60px;
    }
    
    .calendar-grid {
      margin-bottom: 12px;
    }
    
    .day-headers {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 2px;
      margin-bottom: 4px;
    }
    
    .day-header {
      text-align: center;
      font-size: 10px;
      font-weight: 600;
      color: #6c757d;
      padding: 4px 0;
    }
    
    .calendar-days {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 2px;
    }
    
    .calendar-day {
      aspect-ratio: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      cursor: pointer;
      border-radius: 3px;
      transition: all 0.2s ease;
    }
    
    .calendar-day:hover {
      background: #e9ecef;
    }
    
    .calendar-day.other-month {
      color: #adb5bd;
    }
    
    .calendar-day.today {
      background: #007bff;
      color: white;
      font-weight: 600;
    }
    
    .calendar-day.selected {
      background: #28a745;
      color: white;
      font-weight: 600;
    }
    
    .quick-actions {
      text-align: center;
    }
    
    .quick-btn {
      padding: 6px 12px;
      background: #28a745;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 11px;
      transition: all 0.2s ease;
    }
    
    .quick-btn:hover {
      background: #218838;
    }
  `]
})
export class DateNavigationComponent {
  @Input() currentDate: dayjs.Dayjs = dayjs();
  @Output() dateChange = new EventEmitter<dayjs.Dayjs>();
  
  selectedYear: number = this.currentDate.year();
  selectedMonth: number = this.currentDate.month() + 1; // dayjs months are 0-indexed
  
  // Generate year range (current year ± 10)
  yearRange: number[] = [];
  
  // Month names
  months = [
    { value: 1, name: 'January' },
    { value: 2, name: 'February' },
    { value: 3, name: 'March' },
    { value: 4, name: 'April' },
    { value: 5, name: 'May' },
    { value: 6, name: 'June' },
    { value: 7, name: 'July' },
    { value: 8, name: 'August' },
    { value: 9, name: 'September' },
    { value: 10, name: 'October' },
    { value: 11, name: 'November' },
    { value: 12, name: 'December' }
  ];
  
  // Week day names
  weekDays = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
  
  // Calendar days for the current month
  calendarDays: any[] = [];
  
  ngOnInit() {
    this.generateYearRange();
    this.updateFromCurrentDate();
    this.generateCalendarDays();
  }
  
  ngOnChanges() {
    this.updateFromCurrentDate();
  }
  
  private updateFromCurrentDate() {
    this.selectedYear = this.currentDate.year();
    this.selectedMonth = this.currentDate.month() + 1;
    this.generateCalendarDays();
  }
  
  private generateYearRange() {
    const currentYear = dayjs().year();
    for (let i = currentYear - 10; i <= currentYear + 10; i++) {
      this.yearRange.push(i);
    }
  }
  
  previousYear() {
    this.selectedYear--;
    this.onYearChange();
  }
  
  nextYear() {
    this.selectedYear++;
    this.onYearChange();
  }
  
  previousMonth() {
    if (this.selectedMonth === 1) {
      this.selectedMonth = 12;
      this.selectedYear--;
    } else {
      this.selectedMonth--;
    }
    this.onMonthChange();
  }
  
  nextMonth() {
    if (this.selectedMonth === 12) {
      this.selectedMonth = 1;
      this.selectedYear++;
    } else {
      this.selectedMonth++;
    }
    this.onMonthChange();
  }
  
  onYearChange() {
    const newDate = dayjs().year(this.selectedYear).month(this.selectedMonth - 1).date(1);
    this.emitDateChange(newDate);
  }
  
  onMonthChange() {
    const newDate = dayjs().year(this.selectedYear).month(this.selectedMonth - 1).date(1);
    this.emitDateChange(newDate);
  }
  
  
  goToToday() {
    const today = dayjs();
    this.emitDateChange(today);
  }
  
  goToThisWeek() {
    // Use ISO week to ensure Monday start
    const thisWeek = dayjs().startOf('isoWeek');
    this.emitDateChange(thisWeek);
  }
  
  goToThisMonth() {
    const thisMonth = dayjs().startOf('month');
    this.emitDateChange(thisMonth);
  }
  
  private emitDateChange(newDate: dayjs.Dayjs) {
    this.currentDate = newDate;
    this.updateFromCurrentDate();
    this.dateChange.emit(newDate);
  }
  
  selectDate(date: dayjs.Dayjs) {
    this.emitDateChange(date);
  }
  
  private generateCalendarDays() {
    const startOfMonth = dayjs().year(this.selectedYear).month(this.selectedMonth - 1).startOf('month');
    const endOfMonth = startOfMonth.endOf('month');
    // Use ISO week to ensure Monday start
    const startOfCalendar = startOfMonth.startOf('isoWeek');
    const endOfCalendar = endOfMonth.endOf('isoWeek');
    
    this.calendarDays = [];
    let currentDay = startOfCalendar;
    
    while (currentDay.isBefore(endOfCalendar) || currentDay.isSame(endOfCalendar, 'day')) {
      this.calendarDays.push({
        date: currentDay,
        dayNumber: currentDay.date(),
        isCurrentMonth: currentDay.isSame(startOfMonth, 'month'),
        isToday: currentDay.isSame(dayjs(), 'day'),
        isSelected: currentDay.isSame(this.currentDate, 'day')
      });
      currentDay = currentDay.add(1, 'day');
    }
  }
}

