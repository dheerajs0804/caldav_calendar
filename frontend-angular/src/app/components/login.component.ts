import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AuthService, AuthResponse } from '../services/auth.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="login-container">
      <div class="login-card">
        <div class="login-header">
          <h1>📅 Mithi Calendar</h1>
          <p>Sign in to access your calendar</p>
        </div>
        
        <form (ngSubmit)="onLogin()" #loginForm="ngForm" class="login-form">
          <div class="form-group">
            <label for="username">Username</label>
            <input 
              type="text" 
              id="username" 
              name="username" 
              [(ngModel)]="credentials.username" 
              required 
              class="form-control"
              placeholder="Enter your username"
            >
          </div>
          
          <div class="form-group">
            <label for="password">Password</label>
            <input 
              type="password" 
              id="password" 
              name="password" 
              [(ngModel)]="credentials.password" 
              required 
              class="form-control"
              placeholder="Enter your password"
            >
          </div>
          
          <div class="form-group">
            <button 
              type="submit" 
              [disabled]="loading || !loginForm.form.valid" 
              class="btn btn-primary"
            >
              <span *ngIf="loading">Signing in...</span>
              <span *ngIf="!loading">Sign In</span>
            </button>
          </div>
          
          <div *ngIf="error" class="alert alert-error">
            {{ error }}
          </div>
          
          <div *ngIf="success" class="alert alert-success">
            {{ success }}
          </div>
        </form>
        
        <div class="login-footer">
          <p>Use your CalDAV server credentials to sign in</p>
        </div>
      </div>
    </div>
  `,
  styles: [`
    .login-container {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 20px;
    }
    
    .login-card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
      padding: 40px;
      width: 100%;
      max-width: 400px;
    }
    
    .login-header {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .login-header h1 {
      color: #333;
      margin: 0 0 10px 0;
      font-size: 28px;
    }
    
    .login-header p {
      color: #666;
      margin: 0;
    }
    
    .login-form {
      margin-bottom: 20px;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: #333;
      font-weight: 500;
    }
    
    .form-control {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid #e1e5e9;
      border-radius: 8px;
      font-size: 16px;
      transition: border-color 0.3s ease;
      box-sizing: border-box;
    }
    
    .form-control:focus {
      outline: none;
      border-color: #667eea;
    }
    
    .btn {
      width: 100%;
      padding: 12px 16px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }
    
    .btn-primary:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    .btn-primary:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
    
    .alert {
      padding: 12px 16px;
      border-radius: 8px;
      margin-top: 20px;
      font-size: 14px;
    }
    
    .alert-error {
      background: #fee;
      color: #c53030;
      border: 1px solid #feb2b2;
    }
    
    .alert-success {
      background: #f0fff4;
      color: #38a169;
      border: 1px solid #9ae6b4;
    }
    
    .login-footer {
      text-align: center;
      color: #666;
      font-size: 14px;
    }
  `]
})
export class LoginComponent {
  credentials = {
    username: '',
    password: ''
  };
  
  loading = false;
  error = '';
  success = '';

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  onLogin(): void {
    this.loading = true;
    this.error = '';
    this.success = '';

    this.authService.login(this.credentials.username, this.credentials.password)
      .subscribe({
        next: (response: AuthResponse) => {
          this.loading = false;
          if (response.success) {
            this.success = 'Login successful! Redirecting...';
            setTimeout(() => {
              this.router.navigate(['/']);
            }, 1000);
          } else {
            this.error = response.message || 'Login failed';
          }
        },
        error: (error) => {
          this.loading = false;
          this.error = 'Login failed. Please check your credentials and try again.';
          console.error('Login error:', error);
        }
      });
  }
}
