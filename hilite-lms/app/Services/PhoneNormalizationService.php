<?php

namespace App\Services;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;

class PhoneNormalizationService
{
    protected PhoneNumberUtil $util;

    public function __construct()
    {
        $this->util = PhoneNumberUtil::getInstance();
    }

    /**
     * Normalize any phone string to E.164.
     * Tries IN first, then AE. Returns null if unparseable.
     */
    public function normalize(string $raw): ?string
    {
        $raw = trim($raw);
        foreach (['IN', 'AE'] as $region) {
            try {
                $parsed = $this->util->parse($raw, $region);
                if ($this->util->isValidNumber($parsed)) {
                    return $this->util->format($parsed, PhoneNumberFormat::E164);
                }
            } catch (NumberParseException $e) {
                continue;
            }
        }
        return null;
    }

    public function isValid(string $raw): bool
    {
        return $this->normalize($raw) !== null;
    }
}
