import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { Router } from '@angular/router';
import { of, throwError } from 'rxjs';

import { AuthService, User, LoginResponse, SSOLoginResponse } from './auth.service';

describe('AuthService', () => {
  let service: AuthService;
  let httpMock: HttpTestingController;
  let router: jasmine.SpyObj<Router>;

  const mockUser: User = {
    username: 'testuser',
    calendars: 2
  };

  const mockLoginResponse: LoginResponse = {
    success: true,
    message: 'Login successful',
    data: {
      user: mockUser
    }
  };

  beforeEach(() => {
    const routerSpy = jasmine.createSpyObj('Router', ['navigate']);

    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [
        AuthService,
        { provide: Router, useValue: routerSpy }
      ]
    });

    service = TestBed.inject(AuthService);
    httpMock = TestBed.inject(HttpTestingController);
    router = TestBed.inject(Router) as jasmine.SpyObj<Router>;

    // Clear localStorage before each test
    localStorage.clear();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should initialize with stored user if available', () => {
    localStorage.setItem('currentUser', JSON.stringify(mockUser));

    const newService = new AuthService(
      TestBed.inject(HttpClientTestingModule),
      router
    );

    newService.getCurrentUser().subscribe(user => {
      expect(user).toEqual(mockUser);
    });
  });

  it('should login successfully', () => {
    const username = 'testuser';
    const password = 'testpassword';

    service.login(username, password).subscribe(response => {
      expect(response).toEqual(mockLoginResponse);
      expect(localStorage.getItem('currentUser')).toBe(JSON.stringify(mockUser));
    });

    const req = httpMock.expectOne(`${environment.apiUrl}/auth/login`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({ username, password });
    expect(req.request.withCredentials).toBe(true);

    req.flush(mockLoginResponse);
  });

  it('should handle login failure', () => {
    const username = 'testuser';
    const password = 'wrongpassword';
    const errorResponse = {
      success: false,
      message: 'Invalid credentials'
    };

    service.login(username, password).subscribe({
      next: response => {
        expect(response.success).toBe(false);
        expect(localStorage.getItem('currentUser')).toBeNull();
      },
      error: () => fail('Should not throw error')
    });

    const req = httpMock.expectOne(`${environment.apiUrl}/auth/login`);
    req.flush(errorResponse);
  });

  it('should handle login HTTP error', () => {
    const username = 'testuser';
    const password = 'testpassword';

    service.login(username, password).subscribe({
      next: () => fail('Should not succeed'),
      error: error => {
        expect(error).toBeTruthy();
        expect(localStorage.getItem('currentUser')).toBeNull();
      }
    });

    const req = httpMock.expectOne(`${environment.apiUrl}/auth/login`);
    req.error(new ErrorEvent('Network error'));
  });

  it('should logout successfully', () => {
    // Set up logged in state
    localStorage.setItem('currentUser', JSON.stringify(mockUser));
    service['currentUserSubject'].next(mockUser);

    service.logout();

    expect(localStorage.getItem('currentUser')).toBeNull();
    service.getCurrentUser().subscribe(user => {
      expect(user).toBeNull();
    });
  });

  it('should check if user is logged in', () => {
    // Not logged in
    expect(service.isLoggedIn()).toBe(false);

    // Logged in
    service['currentUserSubject'].next(mockUser);
    expect(service.isLoggedIn()).toBe(true);
  });

  it('should get current user', () => {
    service['currentUserSubject'].next(mockUser);

    service.getCurrentUser().subscribe(user => {
      expect(user).toEqual(mockUser);
    });
  });

  it('should handle SSO login successfully', () => {
    const ssoToken = 'test-sso-token';
    const mockSSOResponse: SSOLoginResponse = {
      success: true,
      message: 'SSO login successful',
      data: {
        user: mockUser
      }
    };

    service.loginWithSSOToken(ssoToken).subscribe(response => {
      expect(response).toEqual(mockSSOResponse);
    });

    const req = httpMock.expectOne(`${environment.apiUrl}/auth/sso-login`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({ sso_token: ssoToken });

    req.flush(mockSSOResponse);
  });

  it('should handle SSO login failure', () => {
    const ssoToken = 'invalid-sso-token';
    const errorResponse = {
      success: false,
      message: 'Invalid SSO token'
    };

    service.loginWithSSOToken(ssoToken).subscribe(response => {
      expect(response.success).toBe(false);
    });

    const req = httpMock.expectOne(`${environment.apiUrl}/auth/sso-login`);
    req.flush(errorResponse);
  });

  it('should check for SSO token in URL', () => {
    spyOn(service, 'loginWithSSOToken').and.returnValue(of(mockLoginResponse));
    spyOn(service, 'clearSSOTokenFromURL');

    // Mock URL with SSO token
    Object.defineProperty(window, 'location', {
      value: {
        search: '?sso_token=test-token'
      },
      writable: true
    });

    service['checkForSSOToken']();

    expect(service.loginWithSSOToken).toHaveBeenCalledWith('test-token');
  });

  it('should clear SSO token from URL', () => {
    const mockHistory = jasmine.createSpyObj('History', ['replaceState']);
    Object.defineProperty(window, 'history', {
      value: mockHistory,
      writable: true
    });

    Object.defineProperty(window, 'location', {
      value: {
        pathname: '/calendar',
        search: '?sso_token=test-token'
      },
      writable: true
    });

    service['clearSSOTokenFromURL']();

    expect(mockHistory.replaceState).toHaveBeenCalledWith(
      {},
      '',
      '/calendar'
    );
  });

  it('should handle auto-login on initialization', () => {
    spyOn(service, 'loginWithSSOToken').and.returnValue(of(mockLoginResponse));
    spyOn(service, 'clearSSOTokenFromURL');

    // Mock URL with SSO token
    Object.defineProperty(window, 'location', {
      value: {
        search: '?sso_token=test-token'
      },
      writable: true
    });

    // Create new service instance to trigger auto-login
    const newService = new AuthService(
      TestBed.inject(HttpClientTestingModule),
      router
    );

    expect(service.loginWithSSOToken).toHaveBeenCalledWith('test-token');
  });

  it('should navigate to calendar after successful SSO login', () => {
    spyOn(service, 'loginWithSSOToken').and.returnValue(of(mockLoginResponse));
    spyOn(service, 'clearSSOTokenFromURL');

    Object.defineProperty(window, 'location', {
      value: {
        search: '?sso_token=test-token'
      },
      writable: true
    });

    service['checkForSSOToken']();

    expect(router.navigate).toHaveBeenCalledWith(['/calendar']);
  });

  it('should navigate to login after failed SSO login', () => {
    const failedResponse = {
      success: false,
      message: 'SSO login failed'
    };

    spyOn(service, 'loginWithSSOToken').and.returnValue(of(failedResponse));
    spyOn(service, 'clearSSOTokenFromURL');

    Object.defineProperty(window, 'location', {
      value: {
        search: '?sso_token=test-token'
      },
      writable: true
    });

    service['checkForSSOToken']();

    expect(router.navigate).toHaveBeenCalledWith(['/login']);
  });

  it('should handle SSO login error', () => {
    spyOn(service, 'loginWithSSOToken').and.returnValue(
      throwError(() => new Error('SSO error'))
    );

    Object.defineProperty(window, 'location', {
      value: {
        search: '?sso_token=test-token'
      },
      writable: true
    });

    // Should not throw error
    expect(() => service['checkForSSOToken']()).not.toThrow();
  });

  it('should emit current user changes', () => {
    const userSpy = jasmine.createSpy('userSpy');
    service.currentUser.subscribe(userSpy);

    // Initial state
    expect(userSpy).toHaveBeenCalledWith(null);

    // Login
    service['currentUserSubject'].next(mockUser);
    expect(userSpy).toHaveBeenCalledWith(mockUser);

    // Logout
    service['currentUserSubject'].next(null);
    expect(userSpy).toHaveBeenCalledWith(null);
  });

  it('should handle malformed stored user data', () => {
    localStorage.setItem('currentUser', 'invalid-json');

    const newService = new AuthService(
      TestBed.inject(HttpClientTestingModule),
      router
    );

    newService.getCurrentUser().subscribe(user => {
      expect(user).toBeNull();
    });
  });

  it('should handle empty stored user data', () => {
    localStorage.setItem('currentUser', '');

    const newService = new AuthService(
      TestBed.inject(HttpClientTestingModule),
      router
    );

    newService.getCurrentUser().subscribe(user => {
      expect(user).toBeNull();
    });
  });

  afterEach(() => {
    httpMock.verify();
    localStorage.clear();
  });
});

