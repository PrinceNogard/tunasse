<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Tunasse\Storage\JsonStore;
use Tunasse\Finance\BudgetEngine;
use Tunasse\Finance\TravauxEngine;
use Tunasse\Finance\LoanCalculator;
use Tunasse\Utils\CsvExporter;

$store = new JsonStore();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Handle direct file downloads
if ($action === 'export_travaux_csv') {
    $state = $store->load();
    $csv = CsvExporter::generateTravauxCsv($state);
    $filename = sprintf('DuoBudget_Travaux_50000_%s.csv', date('Y-m-d'));
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $csv;
    exit;
}

if ($action === 'export_json') {
    $state = $store->load();
    $filename = sprintf('DuoBudget_Data_%s.json', date('Y-m-d'));
    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

header('Content-Type: application/json; charset=UTF-8');

$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    if ($raw && str_starts_with(trim($raw), '{')) {
        $input = json_decode($raw, true) ?? [];
    } else {
        $input = $_POST;
    }
} else {
    $input = $_GET;
}

if (empty($action) && isset($input['action'])) {
    $action = $input['action'];
}

$state = $store->load();

switch ($action) {
    case 'get_state':
        $budgets = BudgetEngine::calculate($state);
        $travaux = TravauxEngine::calculate($state);
        echo json_encode([
            'success' => true,
            'state' => $state,
            'budgets' => $budgets,
            'travaux' => $travaux
        ]);
        exit;

    case 'update_simulator':
        if (isset($input['totalGift'])) $state['simulator']['totalGift'] = (float)$input['totalGift'];
        if (isset($input['userShare'])) $state['simulator']['userShare'] = (float)$input['userShare'];
        if (isset($input['durationYears'])) $state['simulator']['durationYears'] = (int)$input['durationYears'];
        if (isset($input['interestRate'])) $state['simulator']['interestRate'] = (float)$input['interestRate'];
        if (isset($input['startMonth'])) $state['simulator']['startMonth'] = (string)$input['startMonth'];
        if (isset($input['monthsPaid'])) $state['simulator']['monthsPaid'] = (int)$input['monthsPaid'];
        if (isset($input['method'])) $state['simulator']['method'] = (string)$input['method'];
        if (isset($input['borrowerId'])) $state['simulator']['borrowerId'] = (string)$input['borrowerId'];
        if (isset($input['lenderId'])) $state['simulator']['lenderId'] = (string)$input['lenderId'];

        $store->save($state);
        echo json_encode([
            'success' => true,
            'budgets' => BudgetEngine::calculate($state),
            'travaux' => TravauxEngine::calculate($state)
        ]);
        exit;

    case 'toggle_reimbursed':
        $id = (string)($input['id'] ?? '');
        foreach ($state['travauxFund']['items'] as &$item) {
            if ($item['id'] === $id) {
                $item['reimbursed'] = !($item['reimbursed'] ?? false);
                break;
            }
        }
        unset($item);
        $store->save($state);
        echo json_encode([
            'success' => true,
            'travaux' => TravauxEngine::calculate($state)
        ]);
        exit;

    case 'save_travaux_item':
        $itemId = (string)($input['id'] ?? '');
        $newItem = [
            'id' => $itemId !== '' ? $itemId : ('trv-' . time() . '-' . rand(100, 999)),
            'date' => (string)($input['date'] ?? date('Y-m-d')),
            'reason' => trim((string)($input['reason'] ?? '')),
            'lotId' => (string)($input['lotId'] ?? 'lot-1'),
            'amountAxel' => (float)($input['amountAxel'] ?? 0.0),
            'amountAriane' => (float)($input['amountAriane'] ?? 0.0),
            'reimbursed' => !empty($input['reimbursed']),
            'notes' => trim((string)($input['notes'] ?? ''))
        ];

        $found = false;
        foreach ($state['travauxFund']['items'] as &$item) {
            if ($item['id'] === $newItem['id']) {
                $item = $newItem;
                $found = true;
                break;
            }
        }
        unset($item);

        if (!$found) {
            array_unshift($state['travauxFund']['items'], $newItem);
        }

        $store->save($state);
        echo json_encode([
            'success' => true,
            'travaux' => TravauxEngine::calculate($state)
        ]);
        exit;

    case 'delete_travaux_item':
        $id = (string)($input['id'] ?? '');
        $state['travauxFund']['items'] = array_values(array_filter(
            $state['travauxFund']['items'],
            fn($item) => $item['id'] !== $id
        ));
        $store->save($state);
        echo json_encode([
            'success' => true,
            'travaux' => TravauxEngine::calculate($state)
        ]);
        exit;

    case 'save_lot':
        $lotId = (string)($input['id'] ?? '');
        $newLot = [
            'id' => $lotId !== '' ? $lotId : ('lot-' . time()),
            'name' => trim((string)($input['name'] ?? 'Nouveau lot')),
            'allocatedBudget' => (float)($input['allocatedBudget'] ?? 0.0),
            'status' => (string)($input['status'] ?? 'ongoing'),
            'icon' => (string)($input['icon'] ?? '🔨'),
            'notes' => trim((string)($input['notes'] ?? ''))
        ];

        $found = false;
        foreach ($state['travauxFund']['lots'] as &$lot) {
            if ($lot['id'] === $newLot['id']) {
                $lot = $newLot;
                $found = true;
                break;
            }
        }
        unset($lot);

        if (!$found) {
            $state['travauxFund']['lots'][] = $newLot;
        }

        $store->save($state);
        echo json_encode([
            'success' => true,
            'travaux' => TravauxEngine::calculate($state)
        ]);
        exit;

    case 'delete_lot':
        $id = (string)($input['id'] ?? '');
        $state['travauxFund']['lots'] = array_values(array_filter(
            $state['travauxFund']['lots'],
            fn($lot) => $lot['id'] !== $id
        ));
        $store->save($state);
        echo json_encode([
            'success' => true,
            'travaux' => TravauxEngine::calculate($state)
        ]);
        exit;

    case 'save_expense':
        $newExp = [
            'id' => 'exp-' . time() . '-' . rand(100, 999),
            'date' => (string)($input['date'] ?? date('Y-m-d')),
            'desc' => trim((string)($input['desc'] ?? '')),
            'amount' => (float)($input['amount'] ?? 0.0),
            'account' => (string)($input['account'] ?? 'Revolut'),
            'category' => (string)($input['category'] ?? 'Courses & Alimentation'),
            'payer' => (string)($input['payer'] ?? 'Carte Revolut Commune')
        ];
        array_unshift($state['expenses'], $newExp);
        $store->save($state);
        echo json_encode([
            'success' => true,
            'budgets' => BudgetEngine::calculate($state)
        ]);
        exit;

    case 'delete_expense':
        $id = (string)($input['id'] ?? '');
        $state['expenses'] = array_values(array_filter(
            $state['expenses'],
            fn($exp) => $exp['id'] !== $id
        ));
        $store->save($state);
        echo json_encode([
            'success' => true,
            'budgets' => BudgetEngine::calculate($state)
        ]);
        exit;

    case 'save_lcl_item':
        $newItem = [
            'id' => 'lcl-' . time(),
            'label' => trim((string)($input['label'] ?? 'Charge fixe')),
            'amount' => (float)($input['amount'] ?? 0.0),
            'type' => (string)($input['type'] ?? 'monthly'),
            'category' => (string)($input['category'] ?? 'credit'),
            'notes' => trim((string)($input['notes'] ?? ''))
        ];
        $state['lclItems'][] = $newItem;
        $store->save($state);
        echo json_encode([
            'success' => true,
            'budgets' => BudgetEngine::calculate($state)
        ]);
        exit;

    case 'delete_lcl_item':
        $id = (string)($input['id'] ?? '');
        $state['lclItems'] = array_values(array_filter(
            $state['lclItems'],
            fn($item) => $item['id'] !== $id
        ));
        $store->save($state);
        echo json_encode([
            'success' => true,
            'budgets' => BudgetEngine::calculate($state)
        ]);
        exit;

    case 'update_profile':
        $pId = (string)($input['id'] ?? '');
        foreach ($state['profiles'] as &$p) {
            if ($p['id'] === $pId) {
                if (isset($input['name'])) $p['name'] = trim((string)$input['name']);
                if (isset($input['income'])) $p['income'] = (float)$input['income'];
                break;
            }
        }
        unset($p);
        $store->save($state);
        echo json_encode([
            'success' => true,
            'budgets' => BudgetEngine::calculate($state)
        ]);
        exit;

    case 'update_split_rule':
        $state['splitRule'] = (string)($input['splitRule'] ?? '50');
        $store->save($state);
        echo json_encode([
            'success' => true,
            'budgets' => BudgetEngine::calculate($state)
        ]);
        exit;

    case 'reset_all_data':
        $state = $store->reset();
        echo json_encode([
            'success' => true,
            'state' => $state,
            'budgets' => BudgetEngine::calculate($state),
            'travaux' => TravauxEngine::calculate($state)
        ]);
        exit;

    case 'import_json':
        if (!empty($_FILES['json_file']['tmp_name'])) {
            $content = file_get_contents($_FILES['json_file']['tmp_name']);
            $decoded = json_decode((string)$content, true);
            if (is_array($decoded) && isset($decoded['profiles'], $decoded['simulator'])) {
                $store->save($decoded);
                header('Location: index.php?imported=1');
                exit;
            }
        }
        echo json_encode(['success' => false, 'error' => 'Fichier JSON invalide']);
        exit;

    default:
        echo json_encode([
            'success' => false,
            'error' => 'Action inconnue'
        ]);
        exit;
}
