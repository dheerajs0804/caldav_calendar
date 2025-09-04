import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
<<<<<<< HEAD
import { Observable, BehaviorSubject } from 'rxjs';
=======
import { BehaviorSubject, Observable } from 'rxjs';
>>>>>>> 7a0647a0a1dd634bb8dc15c71db3aef7d799893d
import { map } from 'rxjs/operators';

export interface User {
  username: string;
<<<<<<< HEAD
  calendars: number;
}

export interface LoginResponse {
  success: boolean;
  message: string;
  data?: {
    user: User;
  };
=======
  calendar_name?: string;
}

export interface AuthResponse {
  success: boolean;
  message: string;
  data?: User;
}

export interface AuthStatus {
  authenticated: boolean;
  username?: string;
>>>>>>> 7a0647a0a1dd634bb8dc15c71db3aef7d799893d
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
<<<<<<< HEAD
  private currentUserSubject = new BehaviorSubject<User | null>(null);
  public currentUser = this.currentUserSubject.asObservable();

  constructor(private http: HttpClient) {
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
    }).pipe(
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
=======
  private apiUrl = 'http://localhost:8001';
  private currentUserSubject: BehaviorSubject<User | null>;
  public currentUser: Observable<User | null>;

  constructor(private http: HttpClient) {
    this.currentUserSubject = new BehaviorSubject<User | null>(this.getUserFromStorage());
    this.currentUser = this.currentUserSubject.asObservable();
  }

  public get currentUserValue(): User | null {
    return this.currentUserSubject.value;
  }

  public get isAuthenticated(): boolean {
    return this.currentUserSubject.value !== null;
  }

  login(username: string, password: string): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${this.apiUrl}/auth/login`, { username, password }, { withCredentials: true })
      .pipe(
        map(response => {
          if (response.success && response.data) {
            // Store user info in localStorage
            localStorage.setItem('currentUser', JSON.stringify(response.data));
            this.currentUserSubject.next(response.data);
          }
          return response;
        })
      );
  }

  autoLogin(username: string, password: string): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${this.apiUrl}/auth/auto-login`, { username, password })
      .pipe(
        map(response => {
          if (response.success && response.data) {
            // Store user info in localStorage
            localStorage.setItem('currentUser', JSON.stringify(response.data));
            this.currentUserSubject.next(response.data);
          }
          return response;
        })
      );
  }

  ssoLogin(token: string): Observable<AuthResponse> {
    console.log('🔐 AuthService: SSO login with token:', token);
    console.log('🔐 AuthService: Making request to:', `${this.apiUrl}/auth/sso-login`);
    
    return this.http.post<AuthResponse>(`${this.apiUrl}/auth/sso-login`, { token }, { withCredentials: true })
      .pipe(
        map(response => {
          console.log('🔐 AuthService: SSO response received:', response);
          if (response.success && response.data) {
            // Store user info in localStorage
            localStorage.setItem('currentUser', JSON.stringify(response.data));
            this.currentUserSubject.next(response.data);
            console.log('🔐 AuthService: User authenticated and stored');
          }
          return response;
        })
      );
  }

  logout(): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${this.apiUrl}/auth/logout`, {})
      .pipe(
        map(response => {
          if (response.success) {
            // Remove user from localStorage
            localStorage.removeItem('currentUser');
            this.currentUserSubject.next(null);
          }
          return response;
        })
      );
  }

  getAuthStatus(): Observable<AuthStatus> {
    return this.http.get<{success: boolean, data: AuthStatus}>(`${this.apiUrl}/auth/status`, { withCredentials: true })
      .pipe(
        map(response => response.data)
      );
  }

  private getUserFromStorage(): User | null {
    const userStr = localStorage.getItem('currentUser');
    if (userStr) {
      try {
        return JSON.parse(userStr);
      } catch (e) {
        localStorage.removeItem('currentUser');
        return null;
      }
    }
    return null;
  }

  // Check if user is authenticated on app startup
  checkAuthStatus(): void {
    this.getAuthStatus().subscribe(
      status => {
        if (!status.authenticated) {
          // Clear any stored user data if not authenticated
          localStorage.removeItem('currentUser');
          this.currentUserSubject.next(null);
        }
      },
      error => {
        // If auth check fails, clear stored data
        localStorage.removeItem('currentUser');
        this.currentUserSubject.next(null);
      }
    );
>>>>>>> 7a0647a0a1dd634bb8dc15c71db3aef7d799893d
  }
}
