<?php

namespace App\Services;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;

class PhoneNormalizationService
{
    private const FALLBACK_REGIONS = ['IN', 'AE', 'US', 'GB'];
    protected PhoneNumberUtil $util;

    public function __construct()
    {
        $this->util = PhoneNumberUtil::getInstance();
    }

    /**
     * Normalize any phone string to E.164.
     * Strategy 1: Has a '+' prefix — parse as international directly.
     * Strategy 2: Try regions in priority order. Returns null if unparseable.
     */
    public function normalize(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') return null;

        // Strategy 1: Has a '+' prefix — parse as international directly
        if (str_starts_with($raw, '+')) {
            try {
                $parsed = $this->util->parse($raw, null);
                if ($this->util->isValidNumber($parsed)) {
                    return $this->util->format($parsed, PhoneNumberFormat::E164);
                }
            } catch (NumberParseException $e) {
                // Fall through to regional fallback if international fails
            }
        }

        // Strategy 2: No '+' prefix — try each fallback region in order
        foreach (self::FALLBACK_REGIONS as $region) {
            try {
                $parsed = $this->util->parse($raw, $region);
                if ($this->util->isValidNumber($parsed)) {
                    return $this->util->format($parsed, PhoneNumberFormat::E164);
                }
            } catch (NumberParseException $e) {
                continue; // Move to the next region if parsing fails
            }
        }

        return null; // Invalid number
    }

    public function isValid(string $raw): bool
    {
        return $this->normalize($raw) !== null;
    }
}
