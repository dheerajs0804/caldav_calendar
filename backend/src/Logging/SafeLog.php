<?php

namespace CalDev\Calendar\Logging;

/**
 * Safe Logging Helper
 * 
 * Provides safe access to logging with automatic fallback handling
 * 
 * Usage:
 * SafeLog::info('User logged in', ['user_id' => 123]);
 * SafeLog::userAction('calendar_created', ['calendar_name' => 'Work'], true);
 */
class SafeLog
{
    private static $logger = null;

    private static function getLogger(): LoggerInterface
    {
        if (self::$logger === null) {
            self::$logger = LoggerFactory::getLogger();
        }
        return self::$logger;
    }

    /**
     * Log user actions and their results
     */
    public static function userAction(string $action, array $context = [], bool $success = true): void
    {
        try {
            self::getLogger()->logUserAction($action, $context, $success);
        } catch (\Exception $e) {
            error_log('Logging error: ' . $e->getMessage());
        }
    }

    /**
     * Log detailed request processing
     */
    public static function processing(string $process, array $context = []): void
    {
        try {
            self::getLogger()->logRequestProcessing($process, $context);
        } catch (\Exception $e) {
            error_log('Logging error: ' . $e->getMessage());
        }
    }

    /**
     * Log CalDAV operations
     */
    public static function caldav(string $operation, array $context = [], bool $success = true): void
    {
        try {
            self::getLogger()->logCalDAVOperation($operation, $context, $success);
        } catch (\Exception $e) {
            error_log('Logging error: ' . $e->getMessage());
        }
    }

    /**
     * Log security events
     */
    public static function security(string $event, array $context = []): void
    {
        try {
            self::getLogger()->logSecurityEvent($event, $context);
        } catch (\Exception $e) {
            error_log('Logging error: ' . $e->getMessage());
        }
    }

    /**
     * Log performance metrics
     */
    public static function performance(string $operation, float $duration, array $context = []): void
    {
        try {
            self::getLogger()->logPerformance($operation, $duration, $context);
        } catch (\Exception $e) {
            error_log('Logging error: ' . $e->getMessage());
        }
    }

    /**
     * Standard logging methods
     */
    public static function debug(string $message, array $context = []): void
    {
        try {
            self::getLogger()->debug($message, $context);
        } catch (\Exception $e) {
            error_log('Logging error: ' . $e->getMessage());
        }
    }

    public static function info(string $message, array $context = []): void
    {
        try {
            self::getLogger()->info($message, $context);
        } catch (\Exception $e) {
            error_log('Logging error: ' . $e->getMessage());
        }
    }

    public static function warn(string $message, array $context = []): void
    {
        try {
            self::getLogger()->warning($message, $context);
        } catch (\Exception $e) {
            error_log('Logging error: ' . $e->getMessage());
        }
    }

    public static function error(string $message, array $context = []): void
    {
        try {
            self::getLogger()->error($message, $context);
        } catch (\Exception $e) {
            error_log('Logging error: ' . $e->getMessage());
        }
    }

    public static function critical(string $message, array $context = []): void
    {
        try {
            self::getLogger()->critical($message, $context);
        } catch (\Exception $e) {
            error_log('Logging error: ' . $e->getMessage());
        }
    }
}
