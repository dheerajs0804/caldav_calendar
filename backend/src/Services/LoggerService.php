<?php

namespace CalDev\Calendar\Services;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Psr\Log\LoggerInterface;

/**
 * Professional Logging Service
 * 
 * Provides structured logging with proper levels, security, and rotation
 * 
 * Log Levels:
 * - DEBUG: Detailed application flow, variable values, processing steps
 * - INFO: User actions, successful operations, general application flow
 * - WARN: Warning conditions, recoverable errors, deprecated usage
 * - ERROR: Error conditions, exceptions, failed operations
 * - CRITICAL: Critical conditions, system failures, security issues
 */
class LoggerService implements LoggerInterface
{
    private Logger $logger;
    private string $environment;
    private array $sensitiveFields = [
        'password', 'passwd', 'pwd', 'secret', 'token', 'key', 'auth',
        'authorization', 'cookie', 'session', 'csrf', 'api_key', 'private_key'
    ];

    public function __construct(string $environment = 'production')
    {
        $this->environment = $environment;
        $this->initializeLogger();
    }

    private function initializeLogger(): void
    {
        $this->logger = new Logger('caldav-calendar');
        
        // Set log level based on environment
        $logLevel = $this->environment === 'development' ? Logger::DEBUG : Logger::INFO;
        
        // Main application log with rotation (daily)
        $mainHandler = new RotatingFileHandler(
            __DIR__ . '/../../logs/app.log',
            30, // Keep 30 days of logs
            $logLevel
        );
        
        // Error log with rotation (daily)
        $errorHandler = new RotatingFileHandler(
            __DIR__ . '/../../logs/error.log',
            30,
            Logger::ERROR
        );
        
        // Development console output
        if ($this->environment === 'development') {
            $consoleHandler = new StreamHandler('php://stderr', Logger::DEBUG);
            $consoleHandler->setFormatter(new LineFormatter(
                "[%datetime%] %level_name%: %message% %context% %extra%\n",
                'Y-m-d H:i:s'
            ));
            $this->logger->pushHandler($consoleHandler);
        }
        
        // Set formatters
        $formatter = new LineFormatter(
            "[%datetime%] %level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s'
        );
        
        $mainHandler->setFormatter($formatter);
        $errorHandler->setFormatter($formatter);
        
        $this->logger->pushHandler($mainHandler);
        $this->logger->pushHandler($errorHandler);
        
        // Add processors for additional context
        $this->logger->pushProcessor(new IntrospectionProcessor());
        $this->logger->pushProcessor(new MemoryUsageProcessor());
    }

    /**
     * Sanitize sensitive data from context
     */
    private function sanitizeContext(array $context): array
    {
        $sanitized = [];
        
        foreach ($context as $key => $value) {
            $lowerKey = strtolower($key);
            
            // Check if key contains sensitive field names
            $isSensitive = false;
            foreach ($this->sensitiveFields as $sensitiveField) {
                if (strpos($lowerKey, $sensitiveField) !== false) {
                    $isSensitive = true;
                    break;
                }
            }
            
            if ($isSensitive) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeContext($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Log user actions and their results
     */
    public function logUserAction(string $action, array $context = [], bool $success = true): void
    {
        $level = $success ? Logger::INFO : Logger::WARN;
        $message = $success ? "User action completed: {$action}" : "User action failed: {$action}";
        
        $this->log($level, $message, $this->sanitizeContext($context));
    }

    /**
     * Log application processing details
     */
    public function logProcessing(string $process, array $context = []): void
    {
        $this->debug("Processing: {$process}", $this->sanitizeContext($context));
    }

    /**
     * Log CalDAV operations
     */
    public function logCalDAVOperation(string $operation, array $context = [], bool $success = true): void
    {
        $level = $success ? Logger::INFO : Logger::ERROR;
        $message = $success ? "CalDAV operation successful: {$operation}" : "CalDAV operation failed: {$operation}";
        
        $this->log($level, $message, $this->sanitizeContext($context));
    }

    /**
     * Log security events
     */
    public function logSecurityEvent(string $event, array $context = []): void
    {
        $this->warning("Security event: {$event}", $this->sanitizeContext($context));
    }

    // PSR-3 LoggerInterface implementation
    public function emergency($message, array $context = []): void
    {
        $this->logger->emergency($message, $this->sanitizeContext($context));
    }

    public function alert($message, array $context = []): void
    {
        $this->logger->alert($message, $this->sanitizeContext($context));
    }

    public function critical($message, array $context = []): void
    {
        $this->logger->critical($message, $this->sanitizeContext($context));
    }

    public function error($message, array $context = []): void
    {
        $this->logger->error($message, $this->sanitizeContext($context));
    }

    public function warning($message, array $context = []): void
    {
        $this->logger->warning($message, $this->sanitizeContext($context));
    }

    public function notice($message, array $context = []): void
    {
        $this->logger->notice($message, $this->sanitizeContext($context));
    }

    public function info($message, array $context = []): void
    {
        $this->logger->info($message, $this->sanitizeContext($context));
    }

    public function debug($message, array $context = []): void
    {
        $this->logger->debug($message, $this->sanitizeContext($context));
    }

    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, $this->sanitizeContext($context));
    }
}
