<?php

namespace CalDev\Calendar\Helpers;

use CalDev\Calendar\Services\LoggerService;

/**
 * Log Helper - Global logging access
 * 
 * Usage:
 * Log::info('User logged in', ['user_id' => 123]);
 * Log::debug('Processing request', ['method' => 'POST']);
 * Log::error('Database connection failed', ['error' => $e->getMessage()]);
 */
class Log
{
    private static ?LoggerService $logger = null;
    
    public static function getLogger(): LoggerService
    {
        if (self::$logger === null) {
            $config = include __DIR__ . '/../../config/logging.php';
            self::$logger = new LoggerService($config['environment']);
        }
        
        return self::$logger;
    }
    
    public static function emergency($message, array $context = []): void
    {
        self::getLogger()->emergency($message, $context);
    }
    
    public static function alert($message, array $context = []): void
    {
        self::getLogger()->alert($message, $context);
    }
    
    public static function critical($message, array $context = []): void
    {
        self::getLogger()->critical($message, $context);
    }
    
    public static function error($message, array $context = []): void
    {
        self::getLogger()->error($message, $context);
    }
    
    public static function warning($message, array $context = []): void
    {
        self::getLogger()->warning($message, $context);
    }
    
    public static function notice($message, array $context = []): void
    {
        self::getLogger()->notice($message, $context);
    }
    
    public static function info($message, array $context = []): void
    {
        self::getLogger()->info($message, $context);
    }
    
    public static function debug($message, array $context = []): void
    {
        self::getLogger()->debug($message, $context);
    }
    
    /**
     * Log user actions with success/failure status
     */
    public static function userAction(string $action, array $context = [], bool $success = true): void
    {
        self::getLogger()->logUserAction($action, $context, $success);
    }
    
    /**
     * Log application processing details
     */
    public static function processing(string $process, array $context = []): void
    {
        self::getLogger()->logProcessing($process, $context);
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
}
