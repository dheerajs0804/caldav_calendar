<?php

namespace CalDev\Calendar\Logging;

/**
 * Logger Factory
 * 
 * Safely creates logger instances with fallback handling
 */
class LoggerFactory
{
    private static $instance = null;
    private static $logger = null;

    public static function getLogger(): LoggerInterface
    {
        if (self::$logger === null) {
            self::$logger = self::createLogger();
        }
        return self::$logger;
    }

    private static function createLogger(): LoggerInterface
    {
        $environment = $_ENV['APP_ENV'] ?? 'production';
        
        try {
            // Try to create Monolog logger
            if (class_exists('Monolog\Logger')) {
                return new MonologLogger($environment);
            }
        } catch (\Exception $e) {
            // Fallback to simple logger if Monolog fails
            error_log('Monolog initialization failed: ' . $e->getMessage());
        }
        
        // Fallback to simple logger
        return new SimpleLogger($environment);
    }
}

/**
 * Simple fallback logger when Monolog is not available
 */
class SimpleLogger implements LoggerInterface
{
    private string $environment;
    private array $sensitiveFields;

    public function __construct(string $environment = 'production')
    {
        $this->environment = $environment;
        $this->sensitiveFields = [
            'password', 'passwd', 'pwd', 'secret', 'token', 'key', 'auth',
            'authorization', 'cookie', 'session', 'csrf', 'api_key', 'private_key'
        ];
    }

    private function log(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $sanitizedContext = $this->sanitizeContext($context);
        
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'context' => $sanitizedContext,
            'logger' => 'simple-fallback'
        ];
        
        $logLine = json_encode($logEntry) . PHP_EOL;
        
        // Write to log file
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logDir . '/app.log', $logLine, FILE_APPEND | LOCK_EX);
        
        // Also log to error_log for immediate visibility
        error_log("[{$timestamp}] {$level}: {$message} " . json_encode($sanitizedContext));
    }

    private function sanitizeContext(array $context): array
    {
        $sanitized = [];
        
        foreach ($context as $key => $value) {
            $lowerKey = strtolower($key);
            
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

    public function logUserAction(string $action, array $context = [], bool $success = true): void
    {
        $level = $success ? 'INFO' : 'WARNING';
        $message = $success ? "User action completed: {$action}" : "User action failed: {$action}";
        $this->log($level, $message, $context);
    }

    public function logRequestProcessing(string $process, array $context = []): void
    {
        $this->log('DEBUG', "Processing: {$process}", $context);
    }

    public function logCalDAVOperation(string $operation, array $context = [], bool $success = true): void
    {
        $level = $success ? 'INFO' : 'ERROR';
        $message = $success ? "CalDAV operation successful: {$operation}" : "CalDAV operation failed: {$operation}";
        $this->log($level, $message, $context);
    }

    public function logSecurityEvent(string $event, array $context = []): void
    {
        $this->log('WARNING', "Security event: {$event}", $context);
    }

    public function logPerformance(string $operation, float $duration, array $context = []): void
    {
        $this->log('INFO', "Performance: {$operation} completed in {$duration}ms", 
            array_merge($context, ['duration' => $duration]));
    }

    // PSR-3 LoggerInterface implementation
    public function emergency($message, array $context = []): void
    {
        $this->log('EMERGENCY', $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log('ALERT', $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log('NOTICE', $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        if ($this->environment === 'development') {
            $this->log('DEBUG', $message, $context);
        }
    }

    public function log($level, $message, array $context = []): void
    {
        $this->writeLog($level, $message, $context);
    }

    private function writeLog($level, $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $sanitizedContext = $this->sanitizeContext($context);
        
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'context' => $sanitizedContext,
            'logger' => 'simple-fallback'
        ];
        
        $logLine = json_encode($logEntry) . PHP_EOL;
        
        // Write to log file
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logDir . '/app.log', $logLine, FILE_APPEND | LOCK_EX);
        
        // Also log to error_log for immediate visibility
        error_log("[{$timestamp}] {$level}: {$message} " . json_encode($sanitizedContext));
    }
}
