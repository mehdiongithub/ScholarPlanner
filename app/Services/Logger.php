<?php

namespace App\Services;

class Logger {
    private static ?string $logFile = null;

    /**
     * Initialize logger directories and check permissions
     */
    public static function init(): void {
        self::$logFile = ROOT_PATH . '/storage/logs/app.log';
        
        $dir = dirname(self::$logFile);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Log message with variable level
     */
    public static function log(string $level, string $message, array $context = []): void {
        if (self::$logFile === null) {
            self::init();
        }

        // Redact secrets
        $context = self::redactSensitiveData($context);
        $message = self::redactSensitiveString($message);

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logLine = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;

        file_put_contents(self::$logFile, $logLine, FILE_APPEND);
    }

    public static function info(string $message, array $context = []): void {
        self::log('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void {
        self::log('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void {
        self::log('ERROR', $message, $context);
    }

    /**
     * Recursively remove sensitive credentials from log arrays
     */
    private static function redactSensitiveData(array $data): array {
        $sensitiveKeys = [
            'password', 'pass', 'secret', 'token', 'key', 'auth', 
            'easypaisa', 'jazzcash', 'credential', 'api_url'
        ];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::redactSensitiveData($value);
            } else {
                foreach ($sensitiveKeys as $sensitiveKey) {
                    if (stripos($key, $sensitiveKey) !== false) {
                        $data[$key] = '[REDACTED]';
                        break;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Regex replacement for secret matches in direct message texts
     */
    public static function redactSensitiveString(string $string): string {
        $patterns = [
            '/(password|pass|secret|token|key|auth|easypaisa|jazzcash)=[^&\s\n]+/i' => '$1=[REDACTED]'
        ];
        return preg_replace(array_keys($patterns), array_values($patterns), $string);
    }
}
