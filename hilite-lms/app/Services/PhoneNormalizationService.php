<?php

namespace App\Services;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberType;

class PhoneNormalizationService
{
    protected PhoneNumberUtil $util;

    /**
     * Fallback regions tried in order when the input has no '+' prefix.
     * The order matters: most common markets first.
     */
    private const FALLBACK_REGIONS = ['IN', 'AE', 'US', 'GB'];

    public function __construct()
    {
        $this->util = PhoneNumberUtil::getInstance();
    }

    /**
     * Normalize any phone string to E.164 using Google's libphonenumber.
     *
     * Strategy:
     *  1. If the raw input starts with '+', parse it directly as international
     *     (no region hint needed — the country code is self-contained).
     *  2. If no '+' prefix, iterate through FALLBACK_REGIONS and return the
     *     first valid match (India → UAE → US → UK).
     *
     * Returns null if the number is genuinely unparseable or invalid.
     */
    public function normalize(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        // Strategy 1: Has a '+' prefix — parse as international directly
        if (str_starts_with($raw, '+')) {
            try {
                $parsed = $this->util->parse($raw, null);
                if ($this->util->isValidNumber($parsed)) {
                    return $this->util->format($parsed, PhoneNumberFormat::E164);
                }
            } catch (NumberParseException) {
                // Fall through to regional fallback
            }
        }

        // Strategy 2: No '+' prefix — try each fallback region
        foreach (self::FALLBACK_REGIONS as $region) {
            try {
                $parsed = $this->util->parse($raw, $region);
                if ($this->util->isValidNumber($parsed)) {
                    return $this->util->format($parsed, PhoneNumberFormat::E164);
                }
            } catch (NumberParseException) {
                continue;
            }
        }

        return null;
    }

    /**
     * Returns true if the raw phone string is parseable and valid.
     */
    public function isValid(string $raw): bool
    {
        return $this->normalize($raw) !== null;
    }

    /**
     * Returns the detected ISO 3166-1 alpha-2 country code (e.g. "IN", "AE")
     * for a raw phone string, or null if invalid.
     *
     * Used by the frontend to display the detected country flag/label.
     */
    public function detectRegion(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $regions = str_starts_with($raw, '+')
            ? [null]                  // International — region embedded in prefix
            : self::FALLBACK_REGIONS;

        foreach ($regions as $region) {
            try {
                $parsed = $this->util->parse($raw, $region);
                if ($this->util->isValidNumber($parsed)) {
                    return $this->util->getRegionCodeForNumber($parsed);
                }
            } catch (NumberParseException) {
                continue;
            }
        }

        return null;
    }

    /**
     * Returns the national-format display string (e.g. "098765 43210" for India,
     * "050 123 4567" for UAE). Useful for display — always store E.164 in the DB.
     */
    public function toNationalFormat(string $raw): ?string
    {
        $raw = trim($raw);
        $regions = str_starts_with($raw, '+')
            ? [null]
            : self::FALLBACK_REGIONS;

        foreach ($regions as $region) {
            try {
                $parsed = $this->util->parse($raw, $region);
                if ($this->util->isValidNumber($parsed)) {
                    return $this->util->format($parsed, PhoneNumberFormat::NATIONAL);
                }
            } catch (NumberParseException) {
                continue;
            }
        }

        return null;
    }

    /**
     * Returns the E.164 number with the country calling code extracted separately.
     * e.g. "+919876543210" → ['calling_code' => '+91', 'number' => '9876543210', 'region' => 'IN']
     */
    public function breakdown(string $raw): ?array
    {
        $e164 = $this->normalize($raw);
        if (!$e164) {
            return null;
        }

        try {
            $parsed = $this->util->parse($e164, null);
            $region = $this->util->getRegionCodeForNumber($parsed);
            $callingCode = '+' . $parsed->getCountryCode();
            $national = ltrim($this->util->format($parsed, PhoneNumberFormat::E164), $callingCode);
            return [
                'e164'         => $e164,
                'calling_code' => $callingCode,
                'national'     => $national,
                'region'       => $region,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Returns a masked version of the national-format string, e.g. "0987** **210".
     */
    public function toMaskedFormat(string $raw): ?string
    {
        $national = $this->toNationalFormat($raw) ?: $raw;
        $e164 = $this->normalize($raw) ?: $raw;
        
        // Count how many actual digits there are
        $digitCount = preg_match_all('/\d/', $national, $matches);
        if ($digitCount <= 6) return '***'; // Too short to mask nicely
        
        // We want to preserve the first 3 digits and the last 3 digits, replacing middle digits with '*'
        $digitsSeen = 0;
        $masked = '';
        for ($i = 0; $i < strlen($national); $i++) {
            $char = $national[$i];
            if (ctype_digit($char)) {
                $digitsSeen++;
                if ($digitsSeen <= 3 || $digitsSeen > $digitCount - 3) {
                    $masked .= $char;
                } else {
                    $masked .= '*';
                }
            } else {
                // Keep spaces, hyphens, etc as is
                $masked .= $char;
            }
        }
        
        return $masked;
    }
}
