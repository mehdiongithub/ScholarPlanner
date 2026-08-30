<?php

namespace App\Services;

class UrlIdService {
    private static function getKey(): string {
        $appKey = $_ENV['APP_KEY'] ?? getenv('APP_KEY') ?: 'scholar-secret-salt';
        return hash('sha256', $appKey, true); // 32 bytes
    }

    /**
     * Encode numeric ID to a secure URL-safe encrypted token.
     */
    public static function encode(int $id): string {
        $key = self::getKey();
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        $ciphertext = openssl_encrypt((string)$id, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        $combined = $iv . $ciphertext;
        
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($combined));
    }

    /**
     * Decode a secure encrypted token back to numeric ID. Returns null on failure.
     */
    public static function decode(?string $token): ?int {
        if ($token === null || trim($token) === '') {
            return null;
        }
        
        $data = str_replace(['-', '_'], ['+', '/'], $token);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        
        $decoded = base64_decode($data);
        if ($decoded === false) {
            return null;
        }
        
        $key = self::getKey();
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        
        if (strlen($decoded) <= $ivLength) {
            return null;
        }
        
        $iv = substr($decoded, 0, $ivLength);
        $ciphertext = substr($decoded, $ivLength);
        
        $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if ($decrypted === false) {
            return null;
        }
        
        if (!is_numeric($decrypted)) {
            return null;
        }
        
        return (int)$decrypted;
    }
}
