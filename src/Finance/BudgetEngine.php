<?php
declare(strict_types=1);

namespace Tunasse\Finance;

class BudgetEngine
{
    /**
     * Calculate categorized subtotals for LCL account items
     */
    public static function calculateLclSubtotals(array $items = []): array
    {
        $creditSubtotal = 0.0;
        $taxeSubtotal = 0.0;
        $maintSubtotal = 0.0;

        foreach ($items as $item) {
            $amt = (float)($item['amount'] ?? 0.0);
            $cat = (string)($item['category'] ?? 'credit');

            if ($cat === 'taxe') {
                $taxeSubtotal += $amt;
            } elseif ($cat === 'maintenance') {
                $maintSubtotal += $amt;
            } else {
                $creditSubtotal += $amt;
            }
        }

        $lclTotal = $creditSubtotal + $taxeSubtotal + $maintSubtotal;
        return [
            'creditSubtotal' => $creditSubtotal,
            'taxeSubtotal' => $taxeSubtotal,
            'maintSubtotal' => $maintSubtotal,
            'lclTotal' => $lclTotal,
            'lclAnnual' => $lclTotal * 12.0
        ];
    }

    /**
     * Calculate split ratios between two partners
     */
    public static function calculateSplitRatios(array $profiles = [], string $splitRule = '50'): array
    {
        $p1 = $profiles[0] ?? ['income' => 2500.0];
        $p2 = $profiles[1] ?? ['income' => 2500.0];

        if ($splitRule === 'income') {
            $totalIncome = (float)($p1['income'] ?? 0.0) + (float)($p2['income'] ?? 0.0);
            if ($totalIncome > 0.0) {
                return [
                    'p1Ratio' => (float)($p1['income'] ?? 0.0) / $totalIncome,
                    'p2Ratio' => (float)($p2['income'] ?? 0.0) / $totalIncome
                ];
            }
        }

        return ['p1Ratio' => 0.5, 'p2Ratio' => 0.5];
    }

    /**
     * Calculate distribution applying offset or direct method
     */
    public static function calculateOffsetDistribution(
        float $p1Base,
        float $p2Base,
        float $monthlyPayment,
        bool $isP1Borrower,
        string $method
    ): array {
        if ($method === 'offset') {
            $paymentDelta = $isP1Borrower ? $monthlyPayment : -$monthlyPayment;
            $p1Total = $p1Base + $paymentDelta;
            $p2Total = $p2Base - $paymentDelta;
            return [
                'p1Total' => $p1Total,
                'p2Total' => $p2Total,
                'diffAmount' => abs($p1Total - $p2Total)
            ];
        }

        return [
            'p1Total' => $p1Base + ($isP1Borrower ? $monthlyPayment : 0.0),
            'p2Total' => $p2Base + (!$isP1Borrower ? $monthlyPayment : 0.0),
            'diffAmount' => $monthlyPayment
        ];
    }

    /**
     * Master budget calculation
     */
    public static function calculate(array $appState = []): array
    {
        $lclMetrics = self::calculateLclSubtotals($appState['lclItems'] ?? []);

        $revolutEnvelopes = $appState['revolutEnvelopes'] ?? [];
        $revolutBudgetTotal = 0.0;
        foreach ($revolutEnvelopes as $env) {
            $revolutBudgetTotal += (float)($env['budget'] ?? 0.0);
        }
        $revolutAnnual = $revolutBudgetTotal * 12.0;

        $currentMonthPrefix = date('Y-m');
        $revolutSpentThisMonth = 0.0;
        foreach ($appState['expenses'] ?? [] as $exp) {
            if (($exp['account'] ?? '') === 'Revolut' && str_starts_with((string)($exp['date'] ?? ''), $currentMonthPrefix)) {
                $revolutSpentThisMonth += (float)($exp['amount'] ?? 0.0);
            }
        }

        $globalMonthlyBudget = $lclMetrics['lclTotal'] + $revolutBudgetTotal;
        $globalAnnualBudget = $globalMonthlyBudget * 12.0;

        $loan = LoanCalculator::calculate($appState['simulator'] ?? []);
        $ratios = self::calculateSplitRatios($appState['profiles'] ?? [], (string)($appState['splitRule'] ?? '50'));

        $profiles = $appState['profiles'] ?? [];
        $p1 = $profiles[0] ?? ['id' => 'p1', 'name' => 'Axel'];
        $p2 = $profiles[1] ?? ['id' => 'p2', 'name' => 'Ariane'];

        $borrowerId = (string)($appState['simulator']['borrowerId'] ?? 'p1');
        $isP1Borrower = ($borrowerId === ($p1['id'] ?? 'p1'));

        $p1LclBase = $lclMetrics['lclTotal'] * $ratios['p1Ratio'];
        $p2LclBase = $lclMetrics['lclTotal'] * $ratios['p2Ratio'];
        $p1RevBase = $revolutBudgetTotal * $ratios['p1Ratio'];
        $p2RevBase = $revolutBudgetTotal * $ratios['p2Ratio'];

        $lclOffset = self::calculateOffsetDistribution(
            $p1LclBase,
            $p2LclBase,
            $loan['monthlyPayment'],
            $isP1Borrower,
            $loan['method']
        );

        $p1Lcl = ($loan['method'] === 'offset') ? $lclOffset['p1Total'] : $p1LclBase;
        $p2Lcl = ($loan['method'] === 'offset') ? $lclOffset['p2Total'] : $p2LclBase;

        $p1Total = $p1Lcl + $p1RevBase + ($loan['method'] === 'direct' && $isP1Borrower ? $loan['monthlyPayment'] : 0.0);
        $p2Total = $p2Lcl + $p2RevBase + ($loan['method'] === 'direct' && !$isP1Borrower ? $loan['monthlyPayment'] : 0.0);
        $diffAmount = ($loan['method'] === 'offset') ? $lclOffset['diffAmount'] : $loan['monthlyPayment'];

        return [
            'creditSubtotal' => $lclMetrics['creditSubtotal'],
            'taxeSubtotal' => $lclMetrics['taxeSubtotal'],
            'maintSubtotal' => $lclMetrics['maintSubtotal'],
            'lclTotal' => $lclMetrics['lclTotal'],
            'lclAnnual' => $lclMetrics['lclAnnual'],
            'revolutBudgetTotal' => $revolutBudgetTotal,
            'revolutAnnual' => $revolutAnnual,
            'revolutSpentThisMonth' => $revolutSpentThisMonth,
            'globalMonthlyBudget' => $globalMonthlyBudget,
            'globalAnnualBudget' => $globalAnnualBudget,
            'totalMonthly' => $globalMonthlyBudget,
            'totalAnnual' => $globalAnnualBudget,
            'revolutTotal' => $revolutBudgetTotal,
            'revolutSpent' => $revolutSpentThisMonth,
            'p1Contrib' => [
                'total' => $p1Total,
                'lcl' => $p1Lcl,
                'rev' => $p1RevBase,
                'loan' => $loan['monthlyPayment']
            ],
            'p2Contrib' => [
                'total' => $p2Total,
                'lcl' => $p2Lcl,
                'rev' => $p2RevBase,
                'loan' => $loan['monthlyPayment']
            ],
            'p1Pct' => $globalMonthlyBudget > 0.0 ? round(($p1Total / $globalMonthlyBudget) * 100.0) : 50.0,
            'p2Pct' => $globalMonthlyBudget > 0.0 ? round(($p2Total / $globalMonthlyBudget) * 100.0) : 50.0,
            'loan' => $loan,
            'p1Ratio' => $ratios['p1Ratio'],
            'p2Ratio' => $ratios['p2Ratio'],
            'split' => [
                'p1' => $p1,
                'p2' => $p2,
                'isP1Borrower' => $isP1Borrower,
                'p1Total' => $p1Total,
                'p2Total' => $p2Total,
                'p1Lcl' => $p1Lcl,
                'p2Lcl' => $p2Lcl,
                'p1Rev' => $p1RevBase,
                'p2Rev' => $p2RevBase,
                'diffAmount' => $diffAmount
            ]
        ];
    }
}
