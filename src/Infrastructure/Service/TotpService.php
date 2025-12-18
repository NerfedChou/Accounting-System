<?php

declare(strict_types=1);

namespace Infrastructure\Service;

use InvalidArgumentException;

/**
 * TOTP (Time-Based One-Time Password) implementation based on RFC 6238.
 * Zero external dependencies.
 */
final class TotpService
{
    private const DIGITS = 6;
    private const PERIOD = 30;
    private const ALGO = 'sha1';

    /**
     * Generate a new random base32 equivalent secret.
     */
    public function generateSecret(int $length = 16): string
    {
        $validChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        
        for ($i = 0; $i < $length; $i++) {
            $secret .= $validChars[random_int(0, 31)];
        }

        return $secret;
    }

    /**
     * Verify a code against a secret.
     * Checks current time and adjacent windows (prev/next) to account for slight drift.
     */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        if (strlen($code) !== self::DIGITS) {
            return false;
        }

        $currentTimeSlice = floor(time() / self::PERIOD);

        for ($i = -$window; $i <= $window; $i++) {
            $calculatedCode = $this->calculateCode($secret, (int)($currentTimeSlice + $i));
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate code for a specific time slice.
     */
    private function calculateCode(string $secret, int $timeSlice): string
    {
        // Pack time constant to binary string (counter)
        $timePacked = pack('N*', 0) . pack('N*', $timeSlice);

        // Decode Base32 Secret
        $secretKey = $this->base32Decode($secret);

        // HMAC-SHA1
        $hash = hash_hmac(self::ALGO, $timePacked, $secretKey, true);

        // Dynamic truncation
        $offset = ord(substr($hash, -1)) & 0xF;
        $binary = (ord(substr($hash, $offset, 1)) & 0x7F) << 24
            | (ord(substr($hash, $offset + 1, 1)) & 0xFF) << 16
            | (ord(substr($hash, $offset + 2, 1)) & 0xFF) << 8
            | (ord(substr($hash, $offset + 3, 1)) & 0xFF);

        $otp = $binary % (10 ** self::DIGITS);

        return str_pad((string)$otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Standard Base32 Decode
     */
    private function base32Decode(string $base32): string
    {
        $base32 = strtoupper($base32);
        if (!preg_match('/^[A-Z2-7]+$/', $base32)) {
            throw new InvalidArgumentException('Invalid Base32 characters');
        }

        $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        $buffer = 0;
        $bufferSize = 0;

        for ($i = 0; $i < strlen($base32); $i++) {
            $buffer = ($buffer << 5) | strpos($map, $base32[$i]);
            $bufferSize += 5;

            if ($bufferSize >= 8) {
                $bufferSize -= 8;
                $binary .= chr(($buffer >> $bufferSize) & 0xFF);
            }
        }

        return $binary;
    }

    /**
     * Generate Provisioning URI for QR Codes
     */
    public function getProvisioningUri(string $user, string $secret, string $issuer = 'AccountingSystem'): string
    {
        $user = rawurlencode($user);
        $issuer = rawurlencode($issuer);
        return "otpauth://totp/{$issuer}:{$user}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";
    }
}
