import { Injectable } from '@angular/core';
import { environment } from '../../environments/environment';

export enum LogLevel {
  TRACE = 0,
  DEBUG = 1,
  INFO = 2,
  WARN = 3,
  ERROR = 4,
  SILENT = 5
}

export interface LogContext {
  [key: string]: any;
}

/**
 * Professional Logging Service for Angular
 * 
 * Provides structured logging with proper levels, security, and environment awareness
 * 
 * Log Levels:
 * - TRACE: Very detailed application flow (development only)
 * - DEBUG: Detailed processing information, variable values
 * - INFO: User actions, successful operations, general flow
 * - WARN: Warning conditions, recoverable errors
 * - ERROR: Error conditions, exceptions, failed operations
 */
@Injectable({
  providedIn: 'root'
})
export class LoggingService {
  private currentLogLevel: LogLevel = LogLevel.INFO;
  private isDevelopment = !environment.production;
  private sensitiveFields = [
    'password', 'passwd', 'pwd', 'secret', 'token', 'key', 'auth',
    'authorization', 'cookie', 'session', 'csrf', 'api_key', 'private_key'
  ];

  constructor() {
    this.initializeLogger();
  }

  private getLogLevel(): LogLevel {
    switch (environment.logLevel) {
      case 'trace': return LogLevel.TRACE;
      case 'debug': return LogLevel.DEBUG;
      case 'info': return LogLevel.INFO;
      case 'warn': return LogLevel.WARN;
      case 'error': return LogLevel.ERROR;
      case 'silent': return LogLevel.SILENT;
      default: return this.isDevelopment ? LogLevel.DEBUG : LogLevel.INFO;
    }
  }

  private initializeLogger(): void {
    this.currentLogLevel = this.getLogLevel();
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
    const level = success ? LogLevel.INFO : LogLevel.WARN;
    const message = success ? `User action completed: ${action}` : `User action failed: ${action}`;
    
    this.log(level, message, context);
  }

  /**
   * Log application processing details
   */
  logProcessing(process: string, context: LogContext = {}): void {
    this.debug(`Processing: ${process}`, context);
  }

  /**
   * Log API operations
   */
  logApiOperation(operation: string, context: LogContext = {}, success: boolean = true): void {
    const level = success ? LogLevel.INFO : LogLevel.ERROR;
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

  // Standard logging methods
  trace(message: string, context?: LogContext): void {
    this.log(LogLevel.TRACE, message, context);
  }

  debug(message: string, context?: LogContext): void {
    this.log(LogLevel.DEBUG, message, context);
  }

  info(message: string, context?: LogContext): void {
    this.log(LogLevel.INFO, message, context);
  }

  warn(message: string, context?: LogContext): void {
    this.log(LogLevel.WARN, message, context);
  }

  error(message: string, context?: LogContext): void {
    this.log(LogLevel.ERROR, message, context);
  }

  private log(level: LogLevel, message: string, context?: LogContext): void {
    // Only log if the level is enabled
    if (level < this.currentLogLevel) {
      return;
    }

    const timestamp = new Date().toISOString();
    const sanitizedContext = context ? this.sanitizeContext(context) : undefined;
    const logMessage = `[${timestamp}] ${message}`;
    
    switch (level) {
      case LogLevel.TRACE:
        console.trace(logMessage, sanitizedContext);
        break;
      case LogLevel.DEBUG:
        console.debug(logMessage, sanitizedContext);
        break;
      case LogLevel.INFO:
        console.info(logMessage, sanitizedContext);
        break;
      case LogLevel.WARN:
        console.warn(logMessage, sanitizedContext);
        break;
      case LogLevel.ERROR:
        console.error(logMessage, sanitizedContext);
        break;
    }
  }

  /**
   * Set log level dynamically
   */
  setLevel(level: LogLevel): void {
    this.currentLogLevel = level;
  }

  /**
   * Get current log level
   */
  getLevel(): LogLevel {
    return this.currentLogLevel;
  }
}
