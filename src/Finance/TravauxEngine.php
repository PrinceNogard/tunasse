<?php
declare(strict_types=1);

namespace Tunasse\Finance;

class TravauxEngine
{
    /**
     * Resolve lot for a travaux item by lotId or keyword
     */
    public static function resolveItemLot(array $item, array $lots = []): ?array
    {
        if (empty($lots)) {
            return null;
        }

        if (!empty($item['lotId'])) {
            foreach ($lots as $lot) {
                if (($lot['id'] ?? '') === $item['lotId']) {
                    return $lot;
                }
            }
        }

        $reason = mb_strtolower((string)($item['reason'] ?? ''));
        if (str_contains($reason, 'bouche') || str_contains($reason, 'repas') || str_contains($reason, 'logistique')) {
            foreach ($lots as $lot) {
                if (($lot['id'] ?? '') === 'lot-2') return $lot;
            }
        }
        if (str_contains($reason, 'toiture')) {
            foreach ($lots as $lot) {
                if (($lot['id'] ?? '') === 'lot-1') return $lot;
            }
        }
        if (str_contains($reason, 'vmc') || str_contains($reason, 'ventilation')) {
            foreach ($lots as $lot) {
                if (($lot['id'] ?? '') === 'lot-3') return $lot;
            }
        }

        return $lots[0] ?? null;
    }

    /**
     * Calculate lot statistics
     */
    public static function calculateLotStatistics(array $rawLots = [], array $items = []): array
    {
        $lotStats = [];
        foreach ($rawLots as $lot) {
            $lotStats[] = array_merge($lot, [
                'allocatedBudget' => (float)($lot['allocatedBudget'] ?? 0.0),
                'totalSpent' => 0.0,
                'spentAxel' => 0.0,
                'spentAriane' => 0.0,
                'remaining' => 0.0,
                'percentConsumed' => 0.0,
                'isOverBudget' => false,
                'itemsCount' => 0
            ]);
        }

        foreach ($items as $item) {
            $ax = (float)($item['amountAxel'] ?? 0.0);
            $ar = (float)($item['amountAriane'] ?? 0.0);
            $itemTotal = $ax + $ar;

            $resolved = self::resolveItemLot($item, $lotStats);
            if ($resolved) {
                foreach ($lotStats as &$l) {
                    if ($l['id'] === $resolved['id']) {
                        $l['totalSpent'] += $itemTotal;
                        $l['spentAxel'] += $ax;
                        $l['spentAriane'] += $ar;
                        $l['itemsCount'] += 1;
                        break;
                    }
                }
                unset($l);
            }
        }

        $totalAllocated = 0.0;
        foreach ($lotStats as &$l) {
            $totalAllocated += $l['allocatedBudget'];
            $l['remaining'] = $l['allocatedBudget'] - $l['totalSpent'];
            $l['percentConsumed'] = $l['allocatedBudget'] > 0.0 ? ($l['totalSpent'] / $l['allocatedBudget']) * 100.0 : 0.0;
            $l['isOverBudget'] = $l['totalSpent'] > $l['allocatedBudget'];
            $l['budget'] = $l['allocatedBudget'];
            $l['spent'] = $l['totalSpent'];
            $l['axel'] = $l['spentAxel'];
            $l['ariane'] = $l['spentAriane'];
            $l['pct'] = round($l['percentConsumed'], 1);
        }
        unset($l);

        return [
            'lotStats' => $lotStats,
            'totalAllocated' => $totalAllocated
        ];
    }

    /**
     * Compute equalization and partner settlement metrics
     */
    public static function calculateEqualization(
        float $totalAxel,
        float $totalAriane,
        float $totalConsumed,
        array $profiles = [],
        string $splitRule = '50'
    ): array {
        $target50Share = round(($totalConsumed / 2.0), 2);
        $axelOwesAriane50 = round(($target50Share - $totalAxel), 2);

        $ratios = BudgetEngine::calculateSplitRatios($profiles, $splitRule);
        $targetP1Share = round(($totalConsumed * $ratios['p1Ratio']), 2);
        $axelOwesArianeProrata = round(($targetP1Share - $totalAxel), 2);

        return [
            'advanceDelta' => round(($totalAriane - $totalAxel), 2),
            'target50Share' => $target50Share,
            'axelOwesAriane50' => $axelOwesAriane50,
            'p1Ratio' => $ratios['p1Ratio'],
            'p2Ratio' => $ratios['p2Ratio'],
            'targetP1Share' => $targetP1Share,
            'axelOwesArianeProrata' => $axelOwesArianeProrata
        ];
    }

    /**
     * Master Travaux calculation
     */
    public static function calculate(array $appState = []): array
    {
        $fund = $appState['travauxFund'] ?? [];
        $profiles = $appState['profiles'] ?? [];
        $splitRule = (string)($appState['splitRule'] ?? '50');

        $initialBudget = (float)($fund['initialBudget'] ?? 50000.00);
        $rawLots = $fund['lots'] ?? [];
        $items = $fund['items'] ?? [];

        $totalAxel = 0.0;
        $totalAriane = 0.0;
        $reimbursedAxel = 0.0;
        $reimbursedAriane = 0.0;
        $unreimbursedAxel = 0.0;
        $unreimbursedAriane = 0.0;

        foreach ($items as $item) {
            $ax = (float)($item['amountAxel'] ?? 0.0);
            $ar = (float)($item['amountAriane'] ?? 0.0);
            $totalAxel += $ax;
            $totalAriane += $ar;

            if (!empty($item['reimbursed'])) {
                $reimbursedAxel += $ax;
                $reimbursedAriane += $ar;
            } else {
                $unreimbursedAxel += $ax;
                $unreimbursedAriane += $ar;
            }
        }

        $lotResult = self::calculateLotStatistics($rawLots, $items);
        $totalConsumed = $totalAxel + $totalAriane;
        $remainingBudget = $initialBudget - $totalConsumed;
        $percentageConsumed = $initialBudget > 0.0 ? ($totalConsumed / $initialBudget) * 100.0 : 0.0;
        $percentageRemaining = max(0.0, 100.0 - $percentageConsumed);

        $axelShareOfConsumed = $totalConsumed > 0.0 ? ($totalAxel / $totalConsumed) * 100.0 : 50.0;
        $arianeShareOfConsumed = $totalConsumed > 0.0 ? ($totalAriane / $totalConsumed) * 100.0 : 50.0;

        $axelBudgetPct = $initialBudget > 0.0 ? ($totalAxel / $initialBudget) * 100.0 : 0.0;
        $arianeBudgetPct = $initialBudget > 0.0 ? ($totalAriane / $initialBudget) * 100.0 : 0.0;
        $remainingBudgetPct = $initialBudget > 0.0 ? ($remainingBudget / $initialBudget) * 100.0 : 0.0;

        $eq = self::calculateEqualization($totalAxel, $totalAriane, $totalConsumed, $profiles, $splitRule);

        $whoAdvancedMore = ($totalAriane >= $totalAxel) ? 'Ariane' : 'Axel';
        $verdict = ($eq['axelOwesAriane50'] >= 0)
            ? sprintf('Axel doit virer %.2f € à Ariane pour atteindre une égalité parfaite (%.2f € chacun).', $eq['axelOwesAriane50'], $eq['target50Share'])
            : sprintf('Ariane doit virer %.2f € à Axel pour atteindre une égalité parfaite (%.2f € chacun).', abs($eq['axelOwesAriane50']), $eq['target50Share']);

        $lotStats = $lotResult['lotStats'];
        foreach ($lotStats as &$l) {
            $l['envelopePct'] = $initialBudget > 0.0 ? round(($l['allocatedBudget'] / $initialBudget) * 100.0, 1) : 0.0;
            $l['spentEnvelopePct'] = $initialBudget > 0.0 ? round(($l['totalSpent'] / $initialBudget) * 100.0, 1) : 0.0;
            $l['remainingEnvelopePct'] = $initialBudget > 0.0 ? round(($l['remaining'] / $initialBudget) * 100.0, 1) : 0.0;
        }
        unset($l);

        $pendingPlannedBudget = max(0.0, round($lotResult['totalAllocated'] - $totalConsumed, 2));
        $pendingPlannedPct = $initialBudget > 0.0 ? round(($pendingPlannedBudget / $initialBudget) * 100.0, 1) : 0.0;
        $marginBudget = max(0.0, round($initialBudget - $lotResult['totalAllocated'], 2));
        $marginPct = $initialBudget > 0.0 ? round(($marginBudget / $initialBudget) * 100.0, 1) : 0.0;
        $isOverAllocated = $lotResult['totalAllocated'] > $initialBudget;
        $overAllocatedAmount = $isOverAllocated ? round($lotResult['totalAllocated'] - $initialBudget, 2) : 0.0;

        return array_merge([
            'initialBudget' => $initialBudget,
            'totalBudget' => $initialBudget,
            'lots' => $lotStats,
            'items' => $items,
            'totalAllocatedLots' => $lotResult['totalAllocated'],
            'unallocatedBudget' => round($initialBudget - $lotResult['totalAllocated'], 2),
            'lotsRemaining' => round($lotResult['totalAllocated'] - $totalConsumed, 2),
            'lotsPercentageConsumed' => $lotResult['totalAllocated'] > 0.0 ? round(($totalConsumed / $lotResult['totalAllocated']) * 100.0, 2) : 0.0,
            'lotsSpentPct' => $lotResult['totalAllocated'] > 0.0 ? round(($totalConsumed / $lotResult['totalAllocated']) * 100.0, 1) : 0.0,
            'lotsRemainingPct' => max(0.0, round(100.0 - ($lotResult['totalAllocated'] > 0.0 ? ($totalConsumed / $lotResult['totalAllocated']) * 100.0 : 0.0), 1)),
            'pendingPlannedBudget' => $pendingPlannedBudget,
            'pendingPlannedPct' => $pendingPlannedPct,
            'marginBudget' => $marginBudget,
            'marginPct' => $marginPct,
            'isOverAllocated' => $isOverAllocated,
            'overAllocatedAmount' => $overAllocatedAmount,
            'totalAxel' => $totalAxel,
            'totalAriane' => $totalAriane,
            'totalConsumed' => $totalConsumed,
            'totalSpent' => $totalConsumed,
            'remainingBudget' => $remainingBudget,
            'percentageConsumed' => $percentageConsumed,
            'spentPct' => round($percentageConsumed, 2),
            'percentageRemaining' => $percentageRemaining,
            'remainingPct' => round($percentageRemaining, 2),
            'axelShareOfConsumed' => $axelShareOfConsumed,
            'arianeShareOfConsumed' => $arianeShareOfConsumed,
            'axelPctOfSpent' => round($axelShareOfConsumed, 1),
            'arianePctOfSpent' => round($arianeShareOfConsumed, 1),
            'axelBudgetPct' => $axelBudgetPct,
            'arianeBudgetPct' => $arianeBudgetPct,
            'axelPctOfBudget' => round($axelBudgetPct, 1),
            'arianePctOfBudget' => round($arianeBudgetPct, 1),
            'remainingBudgetPct' => $remainingBudgetPct,
            'reimbursedAxel' => $reimbursedAxel,
            'reimbursedAriane' => $reimbursedAriane,
            'unreimbursedAxel' => $unreimbursedAxel,
            'unreimbursedAriane' => $unreimbursedAriane,
            'reimburseAxel' => $unreimbursedAxel,
            'reimburseAriane' => $unreimbursedAriane,
            'totalUnreimbursed' => $unreimbursedAxel + $unreimbursedAriane,
            'delta' => $eq['advanceDelta'],
            'whoAdvancedMore' => $whoAdvancedMore,
            'halfTheoretical' => $eq['target50Share'],
            'axelDiff50' => -$eq['axelOwesAriane50'],
            'arianeDiff50' => $eq['axelOwesAriane50'],
            'verdict' => $verdict
        ], $eq);
    }
}
