import { Component, EventEmitter, Input, Output, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

export interface ImportOptions {
  calendar: string;
  dateRange: string;
  customStartDate?: string;
  customEndDate?: string;
  file?: File;
}

@Component({
  selector: 'app-import-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="modal-overlay" (click)="onClose()">
      <div class="modal-content" (click)="$event.stopPropagation()">
        <div class="modal-header">
          <h3>Import events</h3>
          <button class="close-btn" (click)="onClose()">×</button>
        </div>
        
        <div class="modal-body">
          <div class="form-group">
            <label>Import from file</label>
            <div class="file-input-group">
              <input 
                type="text" 
                class="file-display" 
                [value]="selectedFileName" 
                placeholder="Choose file..."
                readonly
              />
              <input 
                type="file" 
                id="fileInput" 
                class="file-input" 
                accept=".ics,.csv,.json"
                (change)="onFileSelected($event)"
                #fileInput
              />
              <button 
                type="button" 
                class="browse-btn" 
                (click)="fileInput.click()"
              >
                Browse
              </button>
            </div>
            <small class="file-size-info">Maximum allowed file size is 25 MB</small>
          </div>
          
          <div class="form-group">
            <label>Calendar</label>
            <select [(ngModel)]="importOptions.calendar" class="form-control">
              <option *ngFor="let calendar of calendars" [value]="calendar.url || calendar.id">
                {{ calendar.name || 'Unnamed Calendar' }}
              </option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Events from</label>
            <select [(ngModel)]="importOptions.dateRange" class="form-control" (change)="onDateRangeChange()">
              <option value="all">All</option>
              <option value="1month">1 month back</option>
              <option value="2months">2 months back</option>
              <option value="3months">3 months back</option>
              <option value="6months">6 months back</option>
              <option value="12months">12 months back</option>
              <option value="custom">Custom date</option>
            </select>
          </div>
          
          <div *ngIf="importOptions.dateRange === 'custom'" class="form-group">
            <label>Start Date</label>
            <input 
              type="date" 
              [(ngModel)]="importOptions.customStartDate" 
              class="form-control"
            />
          </div>
          
          <div *ngIf="importOptions.dateRange === 'custom'" class="form-group">
            <label>End Date</label>
            <input 
              type="date" 
              [(ngModel)]="importOptions.customEndDate" 
              class="form-control"
            />
          </div>
        </div>
        
        <div class="modal-footer">
          <button class="btn btn-secondary" (click)="onClose()">Cancel</button>
          <button class="btn btn-primary" (click)="onImport()" [disabled]="!canImport()">
            Import
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
    
    .file-input-group {
      display: flex;
      gap: 8px;
      align-items: center;
    }
    
    .file-display {
      flex: 1;
      padding: 10px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
      background: #f8f9fa;
      color: #666;
    }
    
    .file-input {
      display: none;
    }
    
    .browse-btn {
      padding: 10px 16px;
      background: #6c757d;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      transition: background-color 0.2s ease;
    }
    
    .browse-btn:hover {
      background: #5a6268;
    }
    
    .file-size-info {
      display: block;
      margin-top: 5px;
      color: #666;
      font-size: 12px;
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
export class ImportModalComponent implements OnInit {
  @Input() calendars: any[] = [];
  @Input() show: boolean = false;
  
  @Output() close = new EventEmitter<void>();
  @Output() import = new EventEmitter<ImportOptions>();
  
  importOptions: ImportOptions = {
    calendar: '',
    dateRange: 'all'
  };
  
  selectedFileName: string = '';
  
  onClose(): void {
    this.close.emit();
  }
  
  onImport(): void {
    if (this.canImport()) {
      this.import.emit({ ...this.importOptions });
    }
  }
  
  onFileSelected(event: any): void {
    const file = event.target.files[0];
    if (file) {
      this.importOptions.file = file;
      this.selectedFileName = file.name;
      console.log('📥 Selected file:', file.name, 'Size:', file.size, 'bytes');
      
      // Check file size (25MB limit)
      if (file.size > 25 * 1024 * 1024) {
        alert('File size exceeds 25MB limit. Please choose a smaller file.');
        this.selectedFileName = '';
        this.importOptions.file = undefined;
        event.target.value = '';
        return;
      }
    }
  }
  
  onDateRangeChange(): void {
    if (this.importOptions.dateRange !== 'custom') {
      this.importOptions.customStartDate = undefined;
      this.importOptions.customEndDate = undefined;
    }
  }
  
  canImport(): boolean {
    if (!this.importOptions.calendar) {
      return false;
    }
    if (!this.importOptions.file) {
      return false;
    }
    if (this.importOptions.dateRange === 'custom') {
      return !!(this.importOptions.customStartDate && this.importOptions.customEndDate);
    }
    return true;
  }
  
  ngOnInit(): void {
    console.log('📥 Import modal calendars:', this.calendars);
    // Set default calendar to first available calendar
    if (this.calendars && this.calendars.length > 0) {
      const firstCalendar = this.calendars[0];
      const calendarValue = firstCalendar.url || firstCalendar.id;
      console.log('📥 Setting default calendar:', firstCalendar.name, 'Value:', calendarValue);
      this.importOptions.calendar = calendarValue;
    }
  }
}
