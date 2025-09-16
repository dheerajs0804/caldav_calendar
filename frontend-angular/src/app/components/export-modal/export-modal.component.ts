import { Component, EventEmitter, Input, Output, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

export interface ExportOptions {
  calendar: string;
  dateRange: string;
  customStartDate?: string;
  customEndDate?: string;
}

@Component({
  selector: 'app-export-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="modal-overlay" (click)="onClose()">
      <div class="modal-content" (click)="$event.stopPropagation()">
        <div class="modal-header">
          <h3>Export to iCalendar</h3>
          <button class="close-btn" (click)="onClose()">×</button>
        </div>
        
        <div class="modal-body">
          <div class="form-group">
            <label>Calendar</label>
            <select [(ngModel)]="exportOptions.calendar" class="form-control">
              <option *ngFor="let calendar of calendars" [value]="calendar.url || calendar.id">
                {{ calendar.name || 'Unnamed Calendar' }}
              </option>
              <option value="all">All Calendars</option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Events from</label>
            <select [(ngModel)]="exportOptions.dateRange" class="form-control" (change)="onDateRangeChange()">
              <option value="all">All</option>
              <option value="1month">1 month back</option>
              <option value="2months">2 months back</option>
              <option value="3months">3 months back</option>
              <option value="6months">6 months back</option>
              <option value="12months">12 months back</option>
              <option value="custom">Custom date</option>
            </select>
          </div>
          
          <div *ngIf="exportOptions.dateRange === 'custom'" class="form-group">
            <label>Start Date</label>
            <input 
              type="date" 
              [(ngModel)]="exportOptions.customStartDate" 
              class="form-control"
            />
          </div>
          
          <div *ngIf="exportOptions.dateRange === 'custom'" class="form-group">
            <label>End Date</label>
            <input 
              type="date" 
              [(ngModel)]="exportOptions.customEndDate" 
              class="form-control"
            />
          </div>
        </div>
        
        <div class="modal-footer">
          <button class="btn btn-secondary" (click)="onClose()">Cancel</button>
          <button class="btn btn-primary" (click)="onExport()" [disabled]="!canExport()">
            Export
          </button>
        </div>
      </div>
    </div>
  `,
  styles: [`
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }
    
    .modal-content {
      background: white;
      border-radius: 8px;
      width: 400px;
      max-width: 90vw;
      max-height: 90vh;
      overflow-y: auto;
    }
    
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px;
      border-bottom: 1px solid #eee;
    }
    
    .modal-header h3 {
      margin: 0;
      font-size: 18px;
      font-weight: 600;
    }
    
    .close-btn {
      background: none;
      border: none;
      font-size: 24px;
      cursor: pointer;
      color: #666;
      padding: 0;
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .close-btn:hover {
      color: #333;
    }
    
    .modal-body {
      padding: 20px;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: #333;
    }
    
    .form-control {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
      background: white;
    }
    
    .form-control:focus {
      outline: none;
      border-color: #007bff;
      box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
    }
    
    .modal-footer {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      padding: 20px;
      border-top: 1px solid #eee;
    }
    
    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 500;
      transition: background-color 0.2s ease;
    }
    
    .btn-secondary {
      background: #6c757d;
      color: white;
    }
    
    .btn-secondary:hover {
      background: #5a6268;
    }
    
    .btn-primary {
      background: #007bff;
      color: white;
    }
    
    .btn-primary:hover:not(:disabled) {
      background: #0056b3;
    }
    
    .btn-primary:disabled {
      background: #ccc;
      cursor: not-allowed;
    }
  `]
})
export class ExportModalComponent implements OnInit {
  @Input() calendars: any[] = [];
  @Input() show: boolean = false;
  
  @Output() close = new EventEmitter<void>();
  @Output() export = new EventEmitter<ExportOptions>();
  
  exportOptions: ExportOptions = {
    calendar: '',
    dateRange: 'all'
  };
  
  onClose(): void {
    this.close.emit();
  }
  
  onExport(): void {
    if (this.canExport()) {
      this.export.emit({ ...this.exportOptions });
    }
  }
  
  onDateRangeChange(): void {
    if (this.exportOptions.dateRange !== 'custom') {
      this.exportOptions.customStartDate = undefined;
      this.exportOptions.customEndDate = undefined;
    }
  }
  
  canExport(): boolean {
    if (!this.exportOptions.calendar) {
      return false;
    }
    if (this.exportOptions.dateRange === 'custom') {
      return !!(this.exportOptions.customStartDate && this.exportOptions.customEndDate);
    }
    return true;
  }
  
  ngOnInit(): void {
    console.log('📤 Export modal calendars:', this.calendars);
    // Set default calendar to first available calendar
    if (this.calendars && this.calendars.length > 0) {
      const firstCalendar = this.calendars[0];
      const calendarValue = firstCalendar.url || firstCalendar.id;
      console.log('📤 Setting default calendar:', firstCalendar.name, 'Value:', calendarValue);
      this.exportOptions.calendar = calendarValue;
    }
  }
}
