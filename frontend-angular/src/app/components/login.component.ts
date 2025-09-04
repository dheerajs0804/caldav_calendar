import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="login-container">
      <div class="login-card">
        <div class="login-header">
          <h1>📅 Mithi Calendar</h1>
          <p>Enter your username and password to access your calendar</p>
        </div>
        
        <form (ngSubmit)="onLogin()" #loginForm="ngForm" class="login-form">
          <div class="form-group">
            <label for="username">Username</label>
            <input 
              type="text" 
              id="username" 
              name="username"
              [(ngModel)]="username" 
              required
              placeholder="dheeraj.sharma@mithi.com"
              class="form-control"
            />
          </div>
          
          <div class="form-group">
            <label for="password">Password</label>
            <input 
              type="password" 
              id="password" 
              name="password"
              [(ngModel)]="password" 
              required
              placeholder="Enter your password"
              class="form-control"
            />
          </div>
          
          <div *ngIf="error" class="error-message">
            {{ error }}
          </div>
          
          <div *ngIf="loading" class="loading-message">
            🔄 Connecting to CalDAV server...
          </div>
          
          <button 
            type="submit" 
            [disabled]="loading || !loginForm.form.valid"
            class="login-btn"
          >
            {{ loading ? 'Connecting...' : 'Login' }}
          </button>
        </form>
        
        <div class="login-footer">
          <p>Your credentials will be stored securely for this session only.</p>
        </div>
      </div>
    </div>
  `,
  styles: [`
    .login-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 20px;
    }
    
    .login-card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
      padding: 40px;
      width: 100%;
      max-width: 400px;
    }
    
    .login-header {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .login-header h1 {
      margin: 0 0 10px 0;
      color: #333;
      font-size: 28px;
      font-weight: 600;
    }
    
    .login-header p {
      margin: 0;
      color: #666;
      font-size: 14px;
    }
    
    .login-form {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    
    .form-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    
    .form-group label {
      font-weight: 500;
      color: #333;
      font-size: 14px;
    }
    
    .form-control {
      padding: 12px 16px;
      border: 2px solid #e1e5e9;
      border-radius: 8px;
      font-size: 16px;
      transition: border-color 0.2s ease;
    }
    
    .form-control:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .login-btn {
      padding: 14px 24px;
      background: #667eea;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
      margin-top: 10px;
    }
    
    .login-btn:hover:not(:disabled) {
      background: #5a67d8;
      transform: translateY(-1px);
    }
    
    .login-btn:disabled {
      background: #ccc;
      cursor: not-allowed;
      transform: none;
    }
    
    .error-message {
      background: #fed7d7;
      color: #e53e3e;
      padding: 12px 16px;
      border-radius: 8px;
      font-size: 14px;
      text-align: center;
    }
    
    .loading-message {
      text-align: center;
      color: #667eea;
      font-size: 14px;
    }
    
    .login-footer {
      margin-top: 30px;
      text-align: center;
    }
    
    .login-footer p {
      margin: 0;
      color: #666;
      font-size: 12px;
    }
  `]
})
export class LoginComponent {
  username: string = '';
  password: string = '';
  loading: boolean = false;
  error: string = '';

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  onLogin(): void {
    this.loading = true;
    this.error = '';

    this.authService.login(this.username, this.password).subscribe({
      next: (response) => {
        this.loading = false;
        if (response.success) {
          this.router.navigate(['/calendar-selection']);
        } else {
          this.error = response.message || 'Login failed';
        }
      },
      error: (error) => {
        this.loading = false;
        this.error = 'Connection error. Please check your credentials and try again.';
        console.error('Login error:', error);
      }
    });
  }
}
