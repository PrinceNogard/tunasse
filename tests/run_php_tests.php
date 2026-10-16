<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Tunasse\Utils\Format;
use Tunasse\Utils\CsvExporter;
use Tunasse\Finance\LoanCalculator;
use Tunasse\Finance\BudgetEngine;
use Tunasse\Finance\TravauxEngine;
use Tunasse\Storage\JsonStore;

class MiniAssert
{
    public static int $passed = 0;
    public static int $failed = 0;

    public static function same($expected, $actual, string $message = ''): void
    {
        if ($expected === $actual) {
            self::$passed++;
            echo "  ✔ $message\n";
        } else {
            self::$failed++;
            echo "  ❌ FAIL: $message (Expected: " . var_export($expected, true) . ", Got: " . var_export($actual, true) . ")\n";
        }
    }

    public static function floatNear(float $expected, float $actual, float $delta = 0.01, string $message = ''): void
    {
        if (abs($expected - $actual) <= $delta) {
            self::$passed++;
            echo "  ✔ $message\n";
        } else {
            self::$failed++;
            echo "  ❌ FAIL: $message (Expected ~$expected, Got $actual)\n";
        }
    }

    public static function true(bool $cond, string $message = ''): void
    {
        if ($cond) {
            self::$passed++;
            echo "  ✔ $message\n";
        } else {
            self::$failed++;
            echo "  ❌ FAIL: $message (Expected true)\n";
        }
    }
}

echo "=== Tunasse PHP Automated Test Suite ===\n\n";

// 1. Utils & Formatting
echo "▶ Format & Utils Tests:\n";
MiniAssert::same('0,00 €', Format::currency(0.0), 'Format::currency zero');
MiniAssert::same('1 382,00 €', Format::currency(1382.0), 'Format::currency 1382');
MiniAssert::same('-69,44 €', Format::currency(-69.44), 'Format::currency negative');
MiniAssert::same('15/10/2026', Format::dateFR('2026-10-15'), 'Format::dateFR standard ISO');
MiniAssert::same('', Format::dateFR(null), 'Format::dateFR null');
MiniAssert::same('"Hello ""World"""', Format::sanitizeCsvCell('Hello "World"'), 'Format::sanitizeCsvCell quotes escaping');

// 2. Loan Calculations
echo "\n▶ Loan Calculator Tests:\n";
$annuityZero = LoanCalculator::computeMonthlyAnnuity(12500.0, 0.0, 180);
MiniAssert::floatNear(69.4444, $annuityZero, 0.01, 'Loan annuity at 0% rate');

$annuityInt = LoanCalculator::computeMonthlyAnnuity(12500.0, 3.5, 180);
MiniAssert::floatNear(89.37, $annuityInt, 0.02, 'Loan annuity at 3.5% rate');

$simMetrics = LoanCalculator::calculate([
    'totalGift' => 25000.00,
    'userShare' => 50,
    'durationYears' => 15,
    'interestRate' => 0.0,
    'monthsPaid' => 8,
    'startMonth' => '2026-03',
    'method' => 'offset'
]);
MiniAssert::floatNear(12500.00, $simMetrics['borrowedAmount'], 0.01, 'Simulator borrowed amount');
MiniAssert::same(180, $simMetrics['totalMonths'], 'Simulator total months');
MiniAssert::floatNear(69.44, $simMetrics['monthlyPayment'], 0.01, 'Simulator monthly payment');
MiniAssert::floatNear(555.56, $simMetrics['capitalPaidSoFar'], 0.02, 'Simulator capital paid so far');
MiniAssert::floatNear(11944.44, $simMetrics['remainingCapital'], 0.02, 'Simulator remaining capital');
MiniAssert::same(8, LoanCalculator::computeElapsedMonths('2026-03', '2026-10'), 'Elapsed months 2026-03 to 2026-10');
MiniAssert::same(12, LoanCalculator::computeElapsedMonths('2025-10', '2026-09'), 'Elapsed months 2025-10 to 2026-09');
MiniAssert::same(0, LoanCalculator::computeElapsedMonths('2027-01', '2026-10'), 'Future start month yields 0 elapsed');


// 3. Budget Engine Tests
echo "\n▶ Budget Engine Tests:\n";
$defaultState = JsonStore::getDefaultState();
$lclSubtotals = BudgetEngine::calculateLclSubtotals($defaultState['lclItems']);
MiniAssert::floatNear(1222.00, $lclSubtotals['creditSubtotal'], 0.01, 'LCL credit subtotal');
MiniAssert::floatNear(110.00, $lclSubtotals['taxeSubtotal'], 0.01, 'LCL taxe subtotal');
MiniAssert::floatNear(50.00, $lclSubtotals['maintSubtotal'], 0.01, 'LCL maintenance subtotal');
MiniAssert::floatNear(1382.00, $lclSubtotals['lclTotal'], 0.01, 'LCL total monthly');
MiniAssert::floatNear(16584.00, $lclSubtotals['lclAnnual'], 0.01, 'LCL annual total');

$split50 = BudgetEngine::calculateSplitRatios($defaultState['profiles'], '50');
MiniAssert::same(0.5, $split50['p1Ratio'], '50/50 split p1 ratio');
MiniAssert::same(0.5, $split50['p2Ratio'], '50/50 split p2 ratio');

$splitInc = BudgetEngine::calculateSplitRatios($defaultState['profiles'], 'income');
MiniAssert::floatNear(0.52, $splitInc['p1Ratio'], 0.01, 'Income split p1 ratio (2600 / 5000)');

$budgets = BudgetEngine::calculate($defaultState);
MiniAssert::floatNear(1382.00, $budgets['lclTotal'], 0.01, 'Master budget LCL total');
MiniAssert::floatNear(1155.00, $budgets['revolutBudgetTotal'], 0.01, 'Master budget Revolut total');
MiniAssert::floatNear(2537.00, $budgets['globalMonthlyBudget'], 0.01, 'Master global monthly budget');
MiniAssert::floatNear(30444.00, $budgets['globalAnnualBudget'], 0.01, 'Master global annual budget');
MiniAssert::floatNear(1337.94, $budgets['split']['p1Total'], 0.02, 'Axel net monthly total contribution');
MiniAssert::floatNear(1199.06, $budgets['split']['p2Total'], 0.02, 'Ariane net monthly total contribution');

// 4. Travaux 50 000 € Envelope Tests
echo "\n▶ Travaux 50 000 € Envelope Tests:\n";
$travaux = TravauxEngine::calculate($defaultState);
MiniAssert::floatNear(50000.00, $travaux['initialBudget'], 0.01, 'Travaux initial budget 50 000 €');
MiniAssert::floatNear(5302.00, $travaux['totalAxel'], 0.01, 'Axel total advanced travaux');
MiniAssert::floatNear(7812.85, $travaux['totalAriane'], 0.01, 'Ariane total advanced travaux');
MiniAssert::floatNear(13114.85, $travaux['totalConsumed'], 0.01, 'Total consumed 13 114,85 €');
MiniAssert::floatNear(36885.15, $travaux['remainingBudget'], 0.01, 'Remaining envelope budget 36 885,15 €');
MiniAssert::floatNear(2510.85, $travaux['advanceDelta'], 0.01, 'Ariane advance delta (+2 510,85 €)');
MiniAssert::floatNear(6557.43, $travaux['target50Share'], 0.01, '50/50 target per partner');
MiniAssert::floatNear(1255.43, $travaux['axelOwesAriane50'], 0.01, 'Axel owes Ariane for 50/50 par');

// 5. CSV Export Tests
echo "\n▶ CSV Export Tests:\n";
$csv = CsvExporter::generateTravauxCsv($defaultState);
MiniAssert::true(str_starts_with($csv, "\xEF\xBB\xBF"), 'CSV has UTF-8 BOM');
MiniAssert::true(str_contains($csv, 'Toiture Roman Avance'), 'CSV contains item reason');
MiniAssert::true(str_contains($csv, '13114,85'), 'CSV contains total consumed row');

// 6. JsonStore Tests
echo "\n▶ JsonStore Tests:\n";
$testStorePath = sys_get_temp_dir() . '/tunasse_test_store.json';
@unlink($testStorePath);
$store = new JsonStore($testStorePath);
$loaded = $store->load();
MiniAssert::same(2, count($loaded['profiles']), 'JsonStore loaded default profiles');
$loaded['splitRule'] = 'income';
$store->save($loaded);
$reloaded = $store->load();
MiniAssert::same('income', $reloaded['splitRule'], 'JsonStore persistence verified');
@unlink($testStorePath);

// 7. Automated Banking (GoCardless Client) Tests
echo "\n▶ Automated Banking (GoCardless DSP2) Tests:\n";
$bankClient = new \Tunasse\Banking\GoCardlessClient();
MiniAssert::true(!$bankClient->isConfigured(), 'Client correctly unconfigured by default');
$req = $bankClient->createRequisition(\Tunasse\Banking\GoCardlessClient::INSTITUTION_LCL, 'http://127.0.0.1/callback', 'test_ref');
MiniAssert::true(!empty($req['link']), 'Requisition generates valid authorization link');
MiniAssert::same(\Tunasse\Banking\GoCardlessClient::INSTITUTION_LCL, $req['institution_id'], 'Requisition targets LCL');
$balances = $bankClient->getAccountBalances('sim_acc_lcl_01');
MiniAssert::same('1450.20', $balances['balances'][0]['balanceAmount']['amount'], 'Balances correctly retrieved');
$testState = [
    'banking' => [
        'accounts' => [
            ['id' => 'sim_acc_lcl_01', 'name' => 'Compte LCL', 'institution_id' => 'LCL_FR_CL42']
        ]
    ]
];
$syncRes = $bankClient->syncAll($testState);
MiniAssert::same(1, $syncRes['synced_accounts'], 'SyncAll updates registered accounts');
MiniAssert::floatNear(1450.20, (float)$testState['banking']['accounts'][0]['last_balance'], 0.01, 'Account live balance updated');


echo "\n" . str_repeat('=', 40) . "\n";
echo "Tests Passed: " . MiniAssert::$passed . "\n";
echo "Tests Failed: " . MiniAssert::$failed . "\n";
echo str_repeat('=', 40) . "\n";

exit(MiniAssert::$failed > 0 ? 1 : 0);
