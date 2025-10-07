import { Component, OnInit } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { LoglevelLoggingService } from './services/loglevel-logging.service';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet],
  template: `
    <router-outlet></router-outlet>
  `,
  styles: []
})
export class AppComponent implements OnInit {
  constructor(private loggingService: LoglevelLoggingService) {}

  ngOnInit(): void {
    // Test the Loglevel-based logging system on app initialization
    this.loggingService.info('Angular application started with Loglevel', {
      component: 'AppComponent',
      timestamp: new Date().toISOString(),
      environment: 'development',
      framework: 'loglevel'
    });

    // Test sensitive data sanitization
    this.loggingService.debug('Testing sensitive data sanitization with Loglevel', {
      username: 'test_user',
      password: 'secret123',
      api_key: 'sk-1234567890abcdef',
      token: 'bearer_token_here',
      normal_data: 'this_should_show'
    });

    // Test user action logging
    this.loggingService.logUserAction('app_initialized', {
      user_id: 123,
      session_id: 'session_123',
      app_version: '1.0.0',
      framework: 'loglevel'
    }, true);

    // Test API operation logging
    this.loggingService.logApiOperation('app_bootstrap', {
      component: 'AppComponent',
      bootstrap_time: performance.now()
    }, true);

    // Test performance logging
    this.loggingService.logPerformance('app_initialization', 150, {
      components_loaded: 1,
      services_initialized: 1
    });
  }
}

