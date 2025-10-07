<?php

namespace CalDev\Calendar\Logging;

/**
 * Professional Logging Framework for CalDAV Calendar Application
 * 
 * Features:
 * - Multiple log levels (DEBUG, INFO, WARN, ERROR, CRITICAL)
 * - User action tracking with results
 * - Detailed request processing logging
 * - Automatic sensitive data sanitization
 * - File rotation and management
 * - Environment-aware configuration
 */
class Logger
{
    const DEBUG = 0;
    const INFO = 1;
    const WARN = 2;
    const ERROR = 3;
    const CRITICAL = 4;

    private static $instance = null;
    private $logLevel;
    private $logFile;
    private $maxFileSize;
    private $maxFiles;
    private $sensitiveFields;

    private function __construct()
    {
        $this->initializeConfiguration();
        $this->sensitiveFields = [
            'password', 'passwd', 'pwd', 'secret', 'token', 'key', 'auth',
            'authorization', 'cookie', 'session', 'csrf', 'api_key', 'private_key',
            'client_secret', 'access_token', 'refresh_token', 'bearer_token',
            'credit_card', 'ssn', 'social_security', 'phone', 'email'
        ];
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializeConfiguration(): void
    {
        // Set log level based on environment
        $environment = $_ENV['APP_ENV'] ?? 'production';
        $this->logLevel = $environment === 'development' ? self::DEBUG : self::INFO;
        
        // Configure log file
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $this->logFile = $logDir . '/app.log';
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB
        $this->maxFiles = 5;
    }

    /**
     * Log user actions and their results
     */
    public function logUserAction(string $action, array $context = [], bool $success = true): void
    {
        $level = $success ? self::INFO : self::WARN;
        $message = $success ? "User action completed: {$action}" : "User action failed: {$action}";
        
        $this->log($level, $message, $this->sanitizeContext($context));
    }

    /**
     * Log detailed request processing information
     */
    public function logRequestProcessing(string $process, array $context = []): void
    {
        $this->log(self::DEBUG, "Processing: {$process}", $this->sanitizeContext($context));
    }

    /**
     * Log CalDAV operations
     */
    public function logCalDAVOperation(string $operation, array $context = [], bool $success = true): void
    {
        $level = $success ? self::INFO : self::ERROR;
        $message = $success ? "CalDAV operation successful: {$operation}" : "CalDAV operation failed: {$operation}";
        
        $this->log($level, $message, $this->sanitizeContext($context));
    }

    /**
     * Log security events
     */
    public function logSecurityEvent(string $event, array $context = []): void
    {
        $this->log(self::WARN, "Security event: {$event}", $this->sanitizeContext($context));
    }

    /**
     * Log performance metrics
     */
    public function logPerformance(string $operation, float $duration, array $context = []): void
    {
        $this->log(self::INFO, "Performance: {$operation} completed in {$duration}ms", 
            $this->sanitizeContext(array_merge($context, ['duration' => $duration])));
    }

    /**
     * Standard logging methods
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::DEBUG, $message, $this->sanitizeContext($context));
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $this->sanitizeContext($context));
    }

    public function warn(string $message, array $context = []): void
    {
        $this->log(self::WARN, $message, $this->sanitizeContext($context));
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $this->sanitizeContext($context));
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $this->sanitizeContext($context));
    }

    /**
     * Core logging method
     */
    private function log(int $level, string $message, array $context = []): void
    {
        // Only log if level is enabled
        if ($level < $this->logLevel) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $levelName = $this->getLevelName($level);
        
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $levelName,
            'message' => $message,
            'context' => $context,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];

        $logLine = json_encode($logEntry) . PHP_EOL;
        
        // Rotate log file if needed
        $this->rotateLogFile();
        
        // Write to log file
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        // Also log to error_log for immediate visibility
        error_log("[{$timestamp}] {$levelName}: {$message} " . json_encode($context));
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
     * Rotate log file when it gets too large
     */
    private function rotateLogFile(): void
    {
        if (!file_exists($this->logFile)) {
            return;
        }
        
        if (filesize($this->logFile) > $this->maxFileSize) {
            // Rotate existing files
            for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
                $oldFile = $this->logFile . ".{$i}";
                $newFile = $this->logFile . "." . ($i + 1);
                
                if (file_exists($oldFile)) {
                    if ($i === $this->maxFiles - 1) {
                        unlink($oldFile); // Delete oldest file
                    } else {
                        rename($oldFile, $newFile);
                    }
                }
            }
            
            // Move current log to .1
            rename($this->logFile, $this->logFile . '.1');
        }
    }

    /**
     * Get level name from level constant
     */
    private function getLevelName(int $level): string
    {
        switch ($level) {
            case self::DEBUG:
                return 'DEBUG';
            case self::INFO:
                return 'INFO';
            case self::WARN:
                return 'WARN';
            case self::ERROR:
                return 'ERROR';
            case self::CRITICAL:
                return 'CRITICAL';
            default:
                return 'UNKNOWN';
        }
    }

    /**
     * Set log level dynamically
     */
    public function setLogLevel(int $level): void
    {
        $this->logLevel = $level;
    }

    /**
     * Get current log level
     */
    public function getLogLevel(): int
    {
        return $this->logLevel;
    }
}
