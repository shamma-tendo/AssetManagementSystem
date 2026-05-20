<?php

namespace App\Helpers;

class Sanitizer
{
    /**
     * Sanitize string input by removing HTML tags and trimming whitespace.
     */
    public static function string(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return trim(strip_tags($value));
    }

    /**
     * Sanitize HTML input by allowing only safe tags.
     */
    public static function html(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $allowedTags = '<p><br><strong><em><u><ul><ol><li><h1><h2><h3><h4><h5><h6>';
        return strip_tags(trim($value), $allowedTags);
    }

    /**
     * Sanitize email address.
     */
    public static function email(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return filter_var(trim($value), FILTER_SANITIZE_EMAIL);
    }

    /**
     * Sanitize integer input.
     */
    public static function int($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize float input.
     */
    public static function float($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Sanitize array of strings.
     */
    public static function array(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        return array_map(fn($v) => self::string($v), $values);
    }
}
