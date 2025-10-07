import * as log from 'loglevel';

export const LogLevel = {
  TRACE: 0,
  DEBUG: 1,
  INFO: 2,
  WARN: 3,
  ERROR: 4,
  SILENT: 5
};

/**
 * Professional Logging Service for React
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
class LoggingService {
  constructor() {
    this.logger = log.getLogger('caldav-calendar');
    this.isDevelopment = process.env.NODE_ENV === 'development';
    this.sensitiveFields = [
      'password', 'passwd', 'pwd', 'secret', 'token', 'key', 'auth',
      'authorization', 'cookie', 'session', 'csrf', 'api_key', 'private_key'
    ];
    
    this.initializeLogger();
  }

  initializeLogger() {
    // Set log level based on environment
    if (this.isDevelopment) {
      this.logger.setLevel(LogLevel.DEBUG);
    } else {
      this.logger.setLevel(LogLevel.INFO);
    }

    // Add custom formatter for better log output
    const originalFactory = this.logger.methodFactory;
    this.logger.methodFactory = (methodName, level, loggerName) => {
      const rawMethod = originalFactory(methodName, level, loggerName);
      return (...args) => {
        const timestamp = new Date().toISOString();
        const [message, context] = args;
        
        if (context && typeof context === 'object') {
          const sanitizedContext = this.sanitizeContext(context);
          rawMethod(`[${timestamp}] ${message}`, sanitizedContext);
        } else {
          rawMethod(`[${timestamp}] ${message}`, ...args.slice(1));
        }
      };
    };
    
    // Rebuild logger with new factory
    this.logger.setLevel(this.logger.getLevel());
  }

  /**
   * Sanitize sensitive data from context
   */
  sanitizeContext(context) {
    const sanitized = {};
    
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
  logUserAction(action, context = {}, success = true) {
    const level = success ? LogLevel.INFO : LogLevel.WARN;
    const message = success ? `User action completed: ${action}` : `User action failed: ${action}`;
    
    this.log(level, message, context);
  }

  /**
   * Log application processing details
   */
  logProcessing(process, context = {}) {
    this.debug(`Processing: ${process}`, context);
  }

  /**
   * Log API operations
   */
  logApiOperation(operation, context = {}, success = true) {
    const level = success ? LogLevel.INFO : LogLevel.ERROR;
    const message = success ? `API operation successful: ${operation}` : `API operation failed: ${operation}`;
    
    this.log(level, message, context);
  }

  /**
   * Log security events
   */
  logSecurityEvent(event, context = {}) {
    this.warn(`Security event: ${event}`, context);
  }

  /**
   * Log performance metrics
   */
  logPerformance(operation, duration, context = {}) {
    this.info(`Performance: ${operation} completed in ${duration}ms`, {
      ...context,
      duration,
      operation
    });
  }

  // Standard logging methods
  trace(message, context) {
    this.log(LogLevel.TRACE, message, context);
  }

  debug(message, context) {
    this.log(LogLevel.DEBUG, message, context);
  }

  info(message, context) {
    this.log(LogLevel.INFO, message, context);
  }

  warn(message, context) {
    this.log(LogLevel.WARN, message, context);
  }

  error(message, context) {
    this.log(LogLevel.ERROR, message, context);
  }

  log(level, message, context) {
    const sanitizedContext = context ? this.sanitizeContext(context) : undefined;
    
    switch (level) {
      case LogLevel.TRACE:
        this.logger.trace(message, sanitizedContext);
        break;
      case LogLevel.DEBUG:
        this.logger.debug(message, sanitizedContext);
        break;
      case LogLevel.INFO:
        this.logger.info(message, sanitizedContext);
        break;
      case LogLevel.WARN:
        this.logger.warn(message, sanitizedContext);
        break;
      case LogLevel.ERROR:
        this.logger.error(message, sanitizedContext);
        break;
    }
  }

  /**
   * Set log level dynamically
   */
  setLevel(level) {
    this.logger.setLevel(level);
  }

  /**
   * Get current log level
   */
  getLevel() {
    return this.logger.getLevel();
  }
}

// Create singleton instance
const loggingService = new LoggingService();

export default loggingService;
