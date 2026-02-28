<?php

namespace App\Core;

class Logger
{
    private static $logPath = null;
    
    private static function getLogPath()
    {
        if (self::$logPath === null) {
            self::$logPath = __DIR__ . '/../storage/logs/';
            if (!is_dir(self::$logPath)) {
                mkdir(self::$logPath, 0755, true);
            }
        }
        return self::$logPath;
    }
    
    public static function log($level, $message, $context = [])
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        
        $logEntry = "[{$timestamp}] {$level}: {$message}";
        if ($contextStr) {
            $logEntry .= " Context: {$contextStr}";
        }
        $logEntry .= PHP_EOL;
        
        $filename = self::getLogPath() . date('Y-m-d') . '.log';
        file_put_contents($filename, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public static function emergency($message, $context = [])
    {
        self::log('EMERGENCY', $message, $context);
    }
    
    public static function alert($message, $context = [])
    {
        self::log('ALERT', $message, $context);
    }
    
    public static function critical($message, $context = [])
    {
        self::log('CRITICAL', $message, $context);
    }
    
    public static function error($message, $context = [])
    {
        self::log('ERROR', $message, $context);
    }
    
    public static function warning($message, $context = [])
    {
        self::log('WARNING', $message, $context);
    }
    
    public static function notice($message, $context = [])
    {
        self::log('NOTICE', $message, $context);
    }
    
    public static function info($message, $context = [])
    {
        self::log('INFO', $message, $context);
    }
    
    public static function debug($message, $context = [])
    {
        self::log('DEBUG', $message, $context);
    }
}