<?php

namespace CalDev\Calendar\Logging;

/**
 * Global Logging Helper
 * 
 * Provides easy access to the logging system throughout the application
 * 
 * Usage:
 * Log::info('User logged in', ['user_id' => 123]);
 * Log::debug('Processing request', ['method' => 'POST']);
 * Log::userAction('calendar_created', ['calendar_name' => 'Work'], true);
 */
class Log
{
    private static $logger = null;

    private static function getLogger(): Logger
    {
        if (self::$logger === null) {
            self::$logger = Logger::getInstance();
        }
        return self::$logger;
    }

    /**
     * Log user actions and their results
     */
    public static function userAction(string $action, array $context = [], bool $success = true): void
    {
        self::getLogger()->logUserAction($action, $context, $success);
    }

    /**
     * Log detailed request processing
     */
    public static function processing(string $process, array $context = []): void
    {
        self::getLogger()->logRequestProcessing($process, $context);
    }

    /**
     * Log CalDAV operations
     */
    public static function caldav(string $operation, array $context = [], bool $success = true): void
    {
        self::getLogger()->logCalDAVOperation($operation, $context, $success);
    }

    /**
     * Log security events
     */
    public static function security(string $event, array $context = []): void
    {
        self::getLogger()->logSecurityEvent($event, $context);
    }

    /**
     * Log performance metrics
     */
    public static function performance(string $operation, float $duration, array $context = []): void
    {
        self::getLogger()->logPerformance($operation, $duration, $context);
    }

    /**
     * Standard logging methods
     */
    public static function debug(string $message, array $context = []): void
    {
        self::getLogger()->debug($message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::getLogger()->info($message, $context);
    }

    public static function warn(string $message, array $context = []): void
    {
        self::getLogger()->warn($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::getLogger()->error($message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::getLogger()->critical($message, $context);
    }
}
