import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
<<<<<<< HEAD
import { Router } from '@angular/router';
import { AuthService } from '../services/auth.service';
=======
import { AuthService, AuthResponse } from '../services/auth.service';
import { Router } from '@angular/router';
>>>>>>> 7a0647a0a1dd634bb8dc15c71db3aef7d799893d

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="login-container">
      <div class="login-card">
        <div class="login-header">
          <h1>📅 Mithi Calendar</h1>
<<<<<<< HEAD
          <p>Enter your username and password to access your calendar</p>
=======
          <p>Sign in to access your calendar</p>
>>>>>>> 7a0647a0a1dd634bb8dc15c71db3aef7d799893d
        </div>
        
        <form (ngSubmit)="onLogin()" #loginForm="ngForm" class="login-form">
          <div class="form-group">
            <label for="username">Username</label>
            <input 
              type="text" 
              id="username" 
<<<<<<< HEAD
              name="username"
              [(ngModel)]="username" 
              required
              placeholder="dheeraj.sharma@mithi.com"
              class="form-control"
            />
=======
              name="username" 
              [(ngModel)]="credentials.username" 
              required 
              class="form-control"
              placeholder="Enter your username"
            >
>>>>>>> 7a0647a0a1dd634bb8dc15c71db3aef7d799893d
          </div>
          
          <div class="form-group">
            <label for="password">Password</label>
            <input 
              type="password" 
              id="password" 
<<<<<<< HEAD
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
=======
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
>>>>>>> 7a0647a0a1dd634bb8dc15c71db3aef7d799893d
        </div>
      </div>
    </div>
  `,
  styles: [`
    .login-container {
<<<<<<< HEAD
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
=======
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
>>>>>>> 7a0647a0a1dd634bb8dc15c71db3aef7d799893d
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 20px;
    }
    
    .login-card {
      background: white;
      border-radius: 12px;
<<<<<<< HEAD
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
=======
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
>>>>>>> 7a0647a0a1dd634bb8dc15c71db3aef7d799893d
      padding: 40px;
      width: 100%;
      max-width: 400px;
    }
    
    .login-header {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .login-header h1 {
<<<<<<< HEAD
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
=======
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
>>>>>>> 7a0647a0a1dd634bb8dc15c71db3aef7d799893d
      padding: 12px 16px;
      border: 2px solid #e1e5e9;
      border-radius: 8px;
      font-size: 16px;
<<<<<<< HEAD
      transition: border-color 0.2s ease;
=======
      transition: border-color 0.3s ease;
      box-sizing: border-box;
>>>>>>> 7a0647a0a1dd634bb8dc15c71db3aef7d799893d
    }
    
    .form-control:focus {
      outline: none;
      border-color: #667eea;
<<<<<<< HEAD
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
=======
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
>>>>>>> 7a0647a0a1dd634bb8dc15c71db3aef7d799893d
    }
  `]
})
export class LoginComponent {
<<<<<<< HEAD
  username: string = '';
  password: string = '';
  loading: boolean = false;
  error: string = '';
=======
  credentials = {
    username: '',
    password: ''
  };
  
  loading = false;
  error = '';
  success = '';
>>>>>>> 7a0647a0a1dd634bb8dc15c71db3aef7d799893d

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  onLogin(): void {
    this.loading = true;
    this.error = '';
<<<<<<< HEAD

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
=======
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
>>>>>>> 7a0647a0a1dd634bb8dc15c71db3aef7d799893d
  }
}
