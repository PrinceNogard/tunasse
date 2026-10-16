<?php
declare(strict_types=1);

namespace Tunasse\Finance;

use Tunasse\Utils\Format;

class LoanCalculator
{
    /**
     * Compute monthly annuity according to loan amortization formula:
     * M = P * [r / (1 - (1+r)^-n)] or P / n if interest rate is 0.
     */
    public static function computeMonthlyAnnuity(float $principal, float $annualRate, int $totalMonths): float
    {
        if ($totalMonths <= 0 || $principal <= 0.0) {
            return 0.0;
        }

        if ($annualRate <= 0.0) {
            return $principal / $totalMonths;
        }

        $monthlyRate = ($annualRate / 100.0) / 12.0;
        return $principal * ($monthlyRate / (1.0 - pow(1.0 + $monthlyRate, -$totalMonths)));
    }

    /**
     * Calculate loan metrics from simulator array
     */
    public static function calculate(array $simulator = []): array
    {
        $totalGift = (float)($simulator['totalGift'] ?? 25000.00);
        $userShare = (float)($simulator['userShare'] ?? 50.0);
        $durationYears = (int)($simulator['durationYears'] ?? 15);
        $interestRate = (float)($simulator['interestRate'] ?? 0.0);
        $monthsPaidInput = (int)($simulator['monthsPaid'] ?? 0);
        $startMonth = (string)($simulator['startMonth'] ?? '2026-03');
        $method = (string)($simulator['method'] ?? 'offset');
        $borrowerId = (string)($simulator['borrowerId'] ?? 'p1');
        $lenderId = (string)($simulator['lenderId'] ?? 'p2');

        $borrowedAmount = $totalGift * ($userShare / 100.0);
        $totalMonths = max(1, $durationYears * 12);
        $monthlyPayment = self::computeMonthlyAnnuity($borrowedAmount, $interestRate, $totalMonths);
        $totalCost = $interestRate > 0.0 ? ($monthlyPayment * $totalMonths) : $borrowedAmount;
        $totalInterest = max(0.0, $totalCost - $borrowedAmount);

        $safePaidMonths = min(max(0, $monthsPaidInput), $totalMonths);
        $remainingMonths = max(0, $totalMonths - $safePaidMonths);
        $capitalPaidSoFar = $monthlyPayment * $safePaidMonths;
        $remainingCapital = max(0.0, $totalCost - $capitalPaidSoFar);

        return [
            'totalGift' => $totalGift,
            'userShare' => $userShare,
            'durationYears' => $durationYears,
            'interestRate' => $interestRate,
            'borrowerId' => $borrowerId,
            'lenderId' => $lenderId,
            'borrowedAmount' => $borrowedAmount,
            'totalMonths' => $totalMonths,
            'monthlyPayment' => $monthlyPayment,
            'totalCost' => $totalCost,
            'totalInterest' => $totalInterest,
            'startMonth' => $startMonth,
            'monthsPaid' => $safePaidMonths,
            'remainingMonths' => $remainingMonths,
            'capitalPaidSoFar' => $capitalPaidSoFar,
            'alreadyPaid' => $capitalPaidSoFar,
            'remainingCapital' => $remainingCapital,
            'durationMonths' => $totalMonths,
            'elapsedMonths' => $safePaidMonths,
            'paidPct' => $totalMonths > 0 ? round(($safePaidMonths / $totalMonths) * 100.0, 1) : 0.0,
            'endDateText' => Format::computeEndDate($startMonth, $totalMonths),
            'endDateFormatted' => Format::computeEndDate($startMonth, $totalMonths),
            'remainingTimeText' => Format::computeRemainingTimeText($remainingMonths),
            'remainingTimeFormatted' => Format::computeRemainingTimeText($remainingMonths),
            'method' => $method
        ];
    }

    /**
     * Generate an amortization schedule preview for display (up to maxRows)
     */
    public static function generateAmortizationSchedule(
        float $principal,
        float $monthlyPayment,
        int $totalMonths,
        float $annualRate,
        string $startMonth = '2026-03',
        int $monthsPaid = 8,
        int $maxRows = 12
    ): array {
        $schedule = [];
        $cumul = 0.0;
        $remaining = $principal;
        
        $startParts = explode('-', $startMonth);
        $startYear = isset($startParts[0]) ? (int)$startParts[0] : 2026;
        $startM = isset($startParts[1]) ? (int)$startParts[1] : 3;

        $rowsToGenerate = min($totalMonths, $maxRows);
        for ($i = 1; $i <= $rowsToGenerate; $i++) {
            $monthOffset = $i - 1;
            $currentMonthNum = (($startM - 1 + $monthOffset) % 12) + 1;
            $currentYearNum = $startYear + intdiv($startM - 1 + $monthOffset, 12);
            $dateFormatted = sprintf('%02d/%04d', $currentMonthNum, $currentYearNum);

            $cumul += $monthlyPayment;
            $remaining = max(0.0, $remaining - $monthlyPayment);

            $schedule[] = [
                'month' => $i,
                'dateFormatted' => $dateFormatted,
                'payment' => $monthlyPayment,
                'cumulPaid' => $cumul,
                'remainingCapital' => $remaining,
                'isPaid' => ($i <= $monthsPaid)
            ];
        }

        return $schedule;
    }
}
