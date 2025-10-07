<?php

namespace CalDev\Calendar\Logging;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\WebProcessor;
use Psr\Log\LoggerInterface;

/**
 * Professional Monolog-based Logging Service
 * 
 * Features:
 * - PSR-3 compliant logging
 * - Multiple log levels (DEBUG, INFO, WARN, ERROR, CRITICAL)
 * - User action tracking with results
 * - Detailed request processing logging
 * - Automatic sensitive data sanitization
 * - File rotation and management
 * - Environment-aware configuration
 * - JSON formatted logs for easy parsing
 */
class MonologLogger implements LoggerInterface
{
    private MonologLogger $logger;
    private string $environment;
    private array $sensitiveFields;

    public function __construct(string $environment = 'production')
    {
        $this->environment = $environment;
        $this->sensitiveFields = [
            'password', 'passwd', 'pwd', 'secret', 'token', 'key', 'auth',
            'authorization', 'cookie', 'session', 'csrf', 'api_key', 'private_key',
            'client_secret', 'access_token', 'refresh_token', 'bearer_token',
            'credit_card', 'ssn', 'social_security', 'phone', 'email'
        ];
        
        $this->initializeLogger();
    }

    private function initializeLogger(): void
    {
        // Create logger instance
        $this->logger = new MonologLogger('caldav-calendar');
        
        // Set log level based on environment
        $logLevel = $this->environment === 'development' ? MonologLogger::DEBUG : MonologLogger::INFO;
        
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
            MonologLogger::ERROR
        );
        
        // Security log with longer retention
        $securityHandler = new RotatingFileHandler(
            __DIR__ . '/../../logs/security.log',
            90, // Keep 90 days of security logs
            MonologLogger::WARNING
        );
        
        // Set JSON formatter for structured logging
        $jsonFormatter = new JsonFormatter();
        $mainHandler->setFormatter($jsonFormatter);
        $errorHandler->setFormatter($jsonFormatter);
        $securityHandler->setFormatter($jsonFormatter);
        
        // Add processors for additional context
        $this->logger->pushProcessor(new IntrospectionProcessor());
        $this->logger->pushProcessor(new MemoryUsageProcessor());
        $this->logger->pushProcessor(new WebProcessor());
        
        // Add handlers
        $this->logger->pushHandler($mainHandler);
        $this->logger->pushHandler($errorHandler);
        $this->logger->pushHandler($securityHandler);
        
        // Add custom processor for sensitive data sanitization
        $this->logger->pushProcessor([$this, 'sanitizeContext']);
    }

    /**
     * Sanitize sensitive data from context
     */
    public function sanitizeContext(array $record): array
    {
        if (isset($record['context']) && is_array($record['context'])) {
            $record['context'] = $this->sanitizeArray($record['context']);
        }
        
        if (isset($record['extra']) && is_array($record['extra'])) {
            $record['extra'] = $this->sanitizeArray($record['extra']);
        }
        
        return $record;
    }

    private function sanitizeArray(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
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
                $sanitized[$key] = $this->sanitizeArray($value);
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
        $level = $success ? MonologLogger::INFO : MonologLogger::WARNING;
        $message = $success ? "User action completed: {$action}" : "User action failed: {$action}";
        
        $this->log($level, $message, $context);
    }

    /**
     * Log detailed request processing information
     */
    public function logRequestProcessing(string $process, array $context = []): void
    {
        $this->debug("Processing: {$process}", $context);
    }

    /**
     * Log CalDAV operations
     */
    public function logCalDAVOperation(string $operation, array $context = [], bool $success = true): void
    {
        $level = $success ? MonologLogger::INFO : MonologLogger::ERROR;
        $message = $success ? "CalDAV operation successful: {$operation}" : "CalDAV operation failed: {$operation}";
        
        $this->log($level, $message, $context);
    }

    /**
     * Log security events
     */
    public function logSecurityEvent(string $event, array $context = []): void
    {
        $this->warning("Security event: {$event}", $context);
    }

    /**
     * Log performance metrics
     */
    public function logPerformance(string $operation, float $duration, array $context = []): void
    {
        $this->info("Performance: {$operation} completed in {$duration}ms", 
            array_merge($context, ['duration' => $duration, 'operation' => $operation]));
    }

    // PSR-3 LoggerInterface implementation
    public function emergency($message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}
