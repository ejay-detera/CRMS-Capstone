<?php

namespace App\Services;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Log;

class EncryptionService
{
    protected ?Encrypter $encrypter = null;

    public function __construct()
    {
        try {
            $key = env('FIELD_ENCRYPTION_KEY');
            if ($key) {
                $decodedKey = base64_decode($key);
                $this->encrypter = new Encrypter($decodedKey, 'aes-256-cbc');
            }
        } catch (\Exception $e) {
            Log::error('EncryptionService initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Encrypt a value.
     */
    public function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (!$this->encrypter) {
            Log::error('Encryption service not configured: FIELD_ENCRYPTION_KEY is missing or invalid.');
            return $value;
        }

        return $this->encrypter->encryptString($value);
    }

    /**
     * Decrypt a value.
     */
    public function decrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (!$this->encrypter) {
            Log::error('Encryption service not configured: FIELD_ENCRYPTION_KEY is missing or invalid.');
            return $value;
        }

        try {
            return $this->encrypter->decryptString($value);
        } catch (\Exception $e) {
            Log::error('Decryption failed: ' . $e->getMessage());
            return $value; // Return original on failure (null-safe / fallback)
        }
    }

    /**
     * Check if a value is encrypted.
     */
    public function isEncrypted(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (!$this->encrypter) {
            return false;
        }

        try {
            $this->encrypter->decryptString($value);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
