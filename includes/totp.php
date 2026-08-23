<?php
/**
 * RFC 6238 Time-Based One-Time Password (TOTP) Implementation in Pure PHP
 * No external dependencies required.
 */

class SimpleTOTP {
    private static $base32_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a random 16-character Base32 secret key
     */
    public static function generateSecret($length = 16) {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::$base32_chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Decode a Base32 string into raw binary
     */
    private static function base32Decode($base32) {
        $base32 = strtoupper($base32);
        $buffer = 0;
        $bufferSize = 0;
        $binary = '';

        for ($i = 0; $i < strlen($base32); $i++) {
            $char = $base32[$i];
            $val = strpos(self::$base32_chars, $char);
            if ($val === false) continue;

            $buffer = ($buffer << 5) | $val;
            $bufferSize += 5;

            if ($bufferSize >= 8) {
                $bufferSize -= 8;
                $binary .= chr(($buffer >> $bufferSize) & 0xFF);
            }
        }
        return $binary;
    }

    /**
     * Calculate 6-digit TOTP code for a given timestamp
     */
    public static function getCode($secret, $timeSlice = null) {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / 30);
        }

        $secretKey = self::base32Decode($secret);
        
        // Pack time into 8-byte big-endian binary string
        $time = chr(0) . chr(0) . chr(0) . chr(0) . pack('N*', $timeSlice);

        // Generate HMAC-SHA1
        $hmac = hash_hmac('sha1', $time, $secretKey, true);

        // Dynamic truncation
        $offset = ord(substr($hmac, -1)) & 0x0F;
        $hashpart = substr($hmac, $offset, 4);

        $value = unpack('N', $hashpart);
        $value = $value[1] & 0x7FFFFFFF;

        $modulo = pow(10, 6);
        return str_pad($value % $modulo, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a 6-digit TOTP code with time drift tolerance (+/- 1 time step = 30s)
     */
    public static function verifyCode($secret, $code, $discrepancy = 1) {
        $code = trim($code);
        if (strlen($code) !== 6 || !ctype_digit($code)) {
            return false;
        }

        $currentTimeSlice = floor(time() / 30);
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = self::getCode($secret, $currentTimeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate standard otpauth:// URL for Authenticator Apps
     */
    public static function getOtpAuthUrl($issuer, $accountName, $secret) {
        $issuerEsc = rawurlencode($issuer);
        $accountEsc = rawurlencode($accountName);
        return "otpauth://totp/{$issuerEsc}:{$accountEsc}?secret={$secret}&issuer={$issuerEsc}&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * Generate QR Code image URL using standard HTTPS QR API
     */
    public static function getQrCodeUrl($issuer, $accountName, $secret, $size = 200) {
        $otpUrl = self::getOtpAuthUrl($issuer, $accountName, $secret);
        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . rawurlencode($otpUrl);
    }

    /**
     * Generate 8 random emergency backup recovery codes
     */
    public static function generateBackupCodes($count = 8) {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4))); // 8 characters e.g. 4F8A1B2C
        }
        return $codes;
    }
}
