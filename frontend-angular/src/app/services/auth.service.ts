import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject } from 'rxjs';
import { map } from 'rxjs/operators';
import { Router } from '@angular/router';

export interface User {
  username: string;
  calendars: number;
}

export interface LoginResponse {
  success: boolean;
  message: string;
  data?: {
    user: User;
  };
}

export interface SSOLoginResponse {
  success: boolean;
  message: string;
  data?: {
    user: User;
  };
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private currentUserSubject = new BehaviorSubject<User | null>(null);
  public currentUser = this.currentUserSubject.asObservable();

  constructor(private http: HttpClient, private router: Router) {
    // Check if user is already logged in (stored in localStorage)
    const storedUser = localStorage.getItem('currentUser');
    if (storedUser) {
      this.currentUserSubject.next(JSON.parse(storedUser));
    }
  }

  login(username: string, password: string): Observable<LoginResponse> {
    return this.http.post<LoginResponse>('http://localhost:8000/auth/login', {
      username,
      password
    }, { withCredentials: true }).pipe(
      map(response => {
        if (response.success && response.data) {
          // Store user info in localStorage
          localStorage.setItem('currentUser', JSON.stringify(response.data.user));
          this.currentUserSubject.next(response.data.user);
        }
        return response;
      })
    );
  }

  logout(): void {
    // Remove user from localStorage
    localStorage.removeItem('currentUser');
    this.currentUserSubject.next(null);
  }

  isLoggedIn(): boolean {
    return this.currentUserSubject.value !== null;
  }

  getCurrentUser(): User | null {
    return this.currentUserSubject.value;
  }

  /**
   * Check for SSO token in URL parameters and automatically login
   */
  private checkForSSOToken(): void {
    const urlParams = new URLSearchParams(window.location.search);
    const ssoToken = urlParams.get('sso_token');
    
    if (ssoToken) {
      console.log('🎯 SSO token found in URL, attempting auto-login...');
      this.loginWithSSOToken(ssoToken).subscribe({
        next: (response) => {
          if (response.success) {
            console.log('🎯 SSO auto-login successful!');
            // Clear the SSO token from URL for security
            this.clearSSOTokenFromURL();
            // Navigate to calendar after successful SSO login
            this.router.navigate(['/calendar']);
          } else {
            console.error('❌ SSO auto-login failed:', response.message);
            // Redirect to login if SSO fails
            this.router.navigate(['/login']);
          }
        },
        error: (error) => {
          console.error('❌ SSO auto-login error:', error);
        }
      });
    }
  }

  /**
   * Login using SSO token
   */
  loginWithSSOToken(token: string): Observable<SSOLoginResponse> {
    return this.http.post<SSOLoginResponse>('http://localhost:8000/auth/sso-login', {
      token: token
    }, { withCredentials: true }).pipe(
      map(response => {
        if (response.success && response.data) {
          // Store user info in localStorage
          localStorage.setItem('currentUser', JSON.stringify(response.data.user));
          this.currentUserSubject.next(response.data.user);
        }
        return response;
      })
    );
  }

  /**
   * Clear SSO token from URL for security
   */
  private clearSSOTokenFromURL(): void {
    const url = new URL(window.location.href);
    url.searchParams.delete('sso_token');
    window.history.replaceState({}, document.title, url.toString());
  }
}
