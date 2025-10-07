import { Injectable } from '@angular/core';
import { environment } from '../../environments/environment';
import * as log from 'loglevel';

export interface LogContext {
  [key: string]: any;
}

/**
 * Professional Loglevel-based Logging Service for Angular
 * 
 * Features:
 * - Loglevel framework integration
 * - Multiple log levels (TRACE, DEBUG, INFO, WARN, ERROR)
 * - User action tracking with results
 * - Detailed request processing logging
 * - Automatic sensitive data sanitization
 * - Environment-aware configuration
 * - Performance tracking
 * - Security event logging
 * - Structured JSON logging
 */
@Injectable({
  providedIn: 'root'
})
export class LoglevelLoggingService {
  private logger!: log.Logger;
  private isDevelopment = !environment.production;
  private sensitiveFields = [
    'password', 'passwd', 'pwd', 'secret', 'token', 'key', 'auth',
    'authorization', 'cookie', 'session', 'csrf', 'api_key', 'private_key',
    'client_secret', 'access_token', 'refresh_token', 'bearer_token',
    'credit_card', 'ssn', 'social_security', 'phone', 'email'
  ];

  constructor() {
    this.initializeLogger();
  }

  private initializeLogger(): void {
    // Create logger instance
    this.logger = log.getLogger('caldav-calendar');
    
    // Set log level based on environment
    const logLevel = this.getLogLevel();
    this.logger.setLevel(logLevel);
    
    // Add custom formatter for structured logging
    this.setupCustomFormatter();
    
    // Log initialization
    this.info('Loglevel logging service initialized', {
      environment: this.isDevelopment ? 'development' : 'production',
      logLevel: this.getLogLevelName(logLevel),
      framework: 'loglevel'
    });
  }

  private getLogLevel(): log.LogLevelDesc {
    switch (environment.logLevel) {
      case 'trace': return log.levels.TRACE;
      case 'debug': return log.levels.DEBUG;
      case 'info': return log.levels.INFO;
      case 'warn': return log.levels.WARN;
      case 'error': return log.levels.ERROR;
      case 'silent': return log.levels.SILENT;
      default: return this.isDevelopment ? log.levels.DEBUG : log.levels.INFO;
    }
  }

  private getLogLevelName(level: log.LogLevelDesc): string {
    if (typeof level === 'number') {
      switch (level) {
        case log.levels.TRACE: return 'TRACE';
        case log.levels.DEBUG: return 'DEBUG';
        case log.levels.INFO: return 'INFO';
        case log.levels.WARN: return 'WARN';
        case log.levels.ERROR: return 'ERROR';
        case log.levels.SILENT: return 'SILENT';
        default: return 'UNKNOWN';
      }
    } else {
      return level.toUpperCase();
    }
  }

  private setupCustomFormatter(): void {
    // Store original method factory
    const originalFactory = this.logger.methodFactory;
    
    // Create custom method factory with proper TypeScript types
    this.logger.methodFactory = (methodName: log.LogLevelNames, level: log.LogLevelNumbers, loggerName: string | symbol) => {
      const rawMethod = originalFactory(methodName, level, loggerName);
      
      return (...args: any[]) => {
        const timestamp = new Date().toISOString();
        const [message, context] = args;
        
        // Create structured log entry
        const logEntry = {
          timestamp,
          level: methodName.toUpperCase(),
          message,
          context: context ? this.sanitizeContext(context) : undefined,
          logger: loggerName.toString(),
          environment: this.isDevelopment ? 'development' : 'production'
        };
        
        // Log structured entry
        rawMethod(JSON.stringify(logEntry));
      };
    };
    
    // Rebuild logger with new factory
    this.logger.setLevel(this.logger.getLevel());
  }

  /**
   * Sanitize sensitive data from context
   */
  private sanitizeContext(context: LogContext): LogContext {
    const sanitized: LogContext = {};
    
    for (const [key, value] of Object.entries(context)) {
      const lowerKey = key.toLowerCase();
      
      // Check if key contains sensitive field names
      const isSensitive = this.sensitiveFields.some(field => 
        lowerKey.includes(field)
      );
      
      if (isSensitive) {
        sanitized[key] = '[REDACTED]';
      } else if (typeof value === 'object' && value !== null) {
        sanitized[key] = this.sanitizeContext(value);
      } else {
        sanitized[key] = value;
      }
    }
    
    return sanitized;
  }

  /**
   * Log user actions and their results
   */
  logUserAction(action: string, context: LogContext = {}, success: boolean = true): void {
    const level = success ? 'info' : 'warn';
    const message = success ? `User action completed: ${action}` : `User action failed: ${action}`;
    
    this.log(level, message, context);
  }

  /**
   * Log detailed request processing information
   */
  logRequestProcessing(process: string, context: LogContext = {}): void {
    this.debug(`Processing: ${process}`, context);
  }

  /**
   * Log API operations
   */
  logApiOperation(operation: string, context: LogContext = {}, success: boolean = true): void {
    const level = success ? 'info' : 'error';
    const message = success ? `API operation successful: ${operation}` : `API operation failed: ${operation}`;
    
    this.log(level, message, context);
  }

  /**
   * Log security events
   */
  logSecurityEvent(event: string, context: LogContext = {}): void {
    this.warn(`Security event: ${event}`, context);
  }

  /**
   * Log performance metrics
   */
  logPerformance(operation: string, duration: number, context: LogContext = {}): void {
    this.info(`Performance: ${operation} completed in ${duration}ms`, {
      ...context,
      duration,
      operation
    });
  }

  /**
   * Standard logging methods
   */
  trace(message: string, context?: LogContext): void {
    this.log('trace', message, context);
  }

  debug(message: string, context?: LogContext): void {
    this.log('debug', message, context);
  }

  info(message: string, context?: LogContext): void {
    this.log('info', message, context);
  }

  warn(message: string, context?: LogContext): void {
    this.log('warn', message, context);
  }

  error(message: string, context?: LogContext): void {
    this.log('error', message, context);
  }

  private log(level: string, message: string, context?: LogContext): void {
    const sanitizedContext = context ? this.sanitizeContext(context) : undefined;
    
    switch (level) {
      case 'trace':
        this.logger.trace(message, sanitizedContext);
        break;
      case 'debug':
        this.logger.debug(message, sanitizedContext);
        break;
      case 'info':
        this.logger.info(message, sanitizedContext);
        break;
      case 'warn':
        this.logger.warn(message, sanitizedContext);
        break;
      case 'error':
        this.logger.error(message, sanitizedContext);
        break;
    }
  }

  /**
   * Set log level dynamically
   */
  setLevel(level: log.LogLevelDesc): void {
    this.logger.setLevel(level);
  }

  /**
   * Get current log level
   */
  getLevel(): log.LogLevelDesc {
    return this.logger.getLevel();
  }

  /**
   * Enable all log levels (useful for debugging)
   */
  enableAll(): void {
    this.logger.setLevel(log.levels.TRACE);
  }

  /**
   * Disable all logging
   */
  disableAll(): void {
    this.logger.setLevel(log.levels.SILENT);
  }
}
