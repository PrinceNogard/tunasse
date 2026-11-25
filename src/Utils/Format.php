<?php
declare(strict_types=1);

namespace Tunasse\Utils;

use DateTimeImmutable;

class Format
{
    /**
     * Format number to French currency string (e.g. "1 382,00 €")
     */
    public static function currency(?float $val): string
    {
        return number_format($val ?? 0.0, 2, ',', ' ') . ' €';
    }

    /**
     * Format ISO date YYYY-MM-DD to French date DD/MM/YYYY
     */
    public static function dateFR(?string $isoDate): string
    {
        if (empty($isoDate)) {
            return '';
        }

        $parts = explode('-', $isoDate);
        if (count($parts) === 3) {
            return sprintf('%02d/%02d/%04d', (int)$parts[2], (int)$parts[1], (int)$parts[0]);
        }

        return $isoDate;
    }

    /**
     * Alias for dateFR
     */
    public static function date(?string $isoDate): string
    {
        return self::dateFR($isoDate);
    }

    /**
     * Compute end date string from start month (YYYY-MM) and duration in months
     */
    public static function computeEndDate(string $startMonth, int $totalMonths): string
    {
        if (empty($startMonth)) {
            $startMonth = '2026-03';
        }

        $parts = explode('-', $startMonth);
        $startYear = isset($parts[0]) ? (int)$parts[0] : 2026;
        $startM = isset($parts[1]) ? (int)$parts[1] : 3;

        $endYear = $startYear + (int)floor(($startM - 1 + $totalMonths) / 12);
        $endMonthIndex = ($startM - 1 + $totalMonths) % 12;

        $monthsFR = [
            'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
            'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
        ];

        return sprintf('%s %d', $monthsFR[$endMonthIndex], $endYear);
    }

    /**
     * Compute remaining time text (e.g. "14 ans et 4 mois restants")
     */
    public static function computeRemainingTimeText(int $remainingMonths): string
    {
        if ($remainingMonths <= 0) {
            return '🎉 Remboursement entièrement terminé !';
        }

        $years = (int)floor($remainingMonths / 12);
        $months = $remainingMonths % 12;

        $parts = [];
        if ($years > 0) {
            $parts[] = sprintf('%d an%s', $years, $years > 1 ? 's' : '');
        }
        if ($months > 0) {
            $parts[] = sprintf('%d mois', $months);
        }

        return sprintf('%s restant%s (%d mensualités)', implode(' et ', $parts), $remainingMonths > 1 ? 's' : '', $remainingMonths);
    }

    /**
     * Escape string for CSV output
     */
    public static function sanitizeCsvCell(string $str): string
    {
        return '"' . str_replace('"', '""', $str) . '"';
    }

    /**
     * Resolve the Central Hub URL (environment, host detection, or fallback)
     */
    public static function hubUrl(): string
    {
        $env = getenv('HUB_URL') ?: ($_SERVER['HUB_URL'] ?? ($_ENV['HUB_URL'] ?? null));
        if (!empty($env) && is_string($env)) {
            return trim($env);
        }

        $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? '');
        if (!empty($host) && is_string($host)) {
            $hostname = strtolower(explode(':', explode(',', $host)[0])[0]);
            if ($hostname !== 'localhost' && $hostname !== '127.0.0.1' && $hostname !== '::1') {
                $proto = (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && in_array(strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']), ['https', 'http'], true))
                    ? strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'])
                    : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
                if (str_ends_with($hostname, '.sslip.io')) {
                    if (preg_match('/(?:^|\.)(\d{1,3}(?:\.\d{1,3}){3}\.sslip\.io)$/', $hostname, $m)) {
                        return "{$proto}://{$m[1]}/";
                    }
                    return "{$proto}://{$hostname}/";
                }
                return "{$proto}://{$hostname}/";
            }
        }

        return 'https://62.210.136.111.sslip.io/';
    }
}
