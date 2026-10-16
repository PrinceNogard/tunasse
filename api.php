<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Tunasse\Storage\JsonStore;
use Tunasse\Finance\BudgetEngine;
use Tunasse\Finance\TravauxEngine;
use Tunasse\Finance\LoanCalculator;
use Tunasse\Utils\CsvExporter;
use Tunasse\Banking\GoCardlessClient;

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
        if (!empty($input) && isset($input['profiles'], $input['simulator'])) {
            $store->save($input);
            echo json_encode(['success' => true]);
            exit;
        }
        echo json_encode(['success' => false, 'error' => 'Fichier JSON invalide']);
        exit;

    case 'banking_status':
        $banking = $state['banking'] ?? [
            'provider' => 'gocardless',
            'configured' => false,
            'accounts' => [],
            'last_sync' => null
        ];
        $client = new GoCardlessClient($banking['secret_id'] ?? null, $banking['secret_key'] ?? null);
        echo json_encode([
            'success' => true,
            'configured' => $client->isConfigured(),
            'provider' => 'GoCardless (DSP2 Open Banking)',
            'accounts' => $banking['accounts'] ?? [],
            'last_sync' => $banking['last_global_sync'] ?? null,
            'institutions' => [
                'lcl' => ['id' => GoCardlessClient::INSTITUTION_LCL, 'name' => 'LCL (Crédit Lyonnais)'],
                'revolut' => ['id' => GoCardlessClient::INSTITUTION_REVOLUT, 'name' => 'Revolut'],
            ]
        ]);
        exit;

    case 'banking_configure':
        $banking = $state['banking'] ?? [];
        $banking['secret_id'] = trim((string)($input['secret_id'] ?? ''));
        $banking['secret_key'] = trim((string)($input['secret_key'] ?? ''));
        $state['banking'] = $banking;
        $store->save($state);

        $client = new GoCardlessClient($banking['secret_id'], $banking['secret_key']);
        echo json_encode([
            'success' => true,
            'configured' => $client->isConfigured()
        ]);
        exit;

    case 'banking_connect':
        $banking = $state['banking'] ?? [];
        $client = new GoCardlessClient($banking['secret_id'] ?? null, $banking['secret_key'] ?? null);
        $inst = (string)($input['institution'] ?? 'lcl');
        $instId = ($inst === 'revolut') ? GoCardlessClient::INSTITUTION_REVOLUT : GoCardlessClient::INSTITUTION_LCL;
        $ref = 'tunasse_' . $inst . '_' . time();

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8085';
        $redirectUrl = $protocol . $host . '/api.php?action=banking_callback';

        try {
            $req = $client->createRequisition($instId, $redirectUrl, $ref);
            $banking['requisitions'][$req['id']] = [
                'institution' => $inst,
                'institution_id' => $instId,
                'created_at' => date('Y-m-d H:i:s'),
                'status' => $req['status'] ?? 'CR'
            ];
            $state['banking'] = $banking;
            $store->save($state);

            echo json_encode([
                'success' => true,
                'link' => $req['link'],
                'requisition_id' => $req['id'],
                'simulated' => !empty($req['simulated'])
            ]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;

    case 'banking_callback':
        $ref = (string)($_GET['ref'] ?? '');
        $simulated = !empty($_GET['simulated']);
        $banking = $state['banking'] ?? [];
        $client = new GoCardlessClient($banking['secret_id'] ?? null, $banking['secret_key'] ?? null);

        // Find requisition
        $targetReqId = null;
        $targetInst = 'LCL';
        foreach ($banking['requisitions'] ?? [] as $id => $req) {
            $targetReqId = $id;
            $targetInst = $req['institution'] ?? 'LCL';
            break;
        }

        // Add default/discovered accounts
        if (!isset($banking['accounts'])) {
            $banking['accounts'] = [];
        }

        $accId = 'acc_' . strtolower($targetInst) . '_' . substr(md5($ref ?: 'tunasse'), 0, 8);
        $exists = false;
        foreach ($banking['accounts'] as &$acc) {
            if ($acc['institution'] === strtolower($targetInst)) {
                $acc['status'] = 'connected';
                $acc['last_sync'] = date('Y-m-d H:i:s');
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $banking['accounts'][] = [
                'id' => $accId,
                'name' => 'Compte ' . strtoupper($targetInst) . ' Connecté',
                'institution' => strtolower($targetInst),
                'institution_id' => (strtolower($targetInst) === 'revolut') ? GoCardlessClient::INSTITUTION_REVOLUT : GoCardlessClient::INSTITUTION_LCL,
                'status' => 'connected',
                'last_balance' => (strtolower($targetInst) === 'revolut') ? 620.40 : 1450.20,
                'currency' => 'EUR',
                'last_sync' => date('Y-m-d H:i:s')
            ];
        }

        $banking['last_global_sync'] = date('Y-m-d H:i:s');
        $state['banking'] = $banking;
        $store->save($state);

        header('Location: index.php?banking_success=' . urlencode($targetInst));
        exit;

    case 'banking_sync':
        $banking = $state['banking'] ?? [];
        $client = new GoCardlessClient($banking['secret_id'] ?? null, $banking['secret_key'] ?? null);
        $res = $client->syncAll($state);
        $store->save($state);

        echo json_encode([
            'success' => true,
            'result' => $res,
            'banking' => $state['banking']
        ]);
        exit;

    default:
        echo json_encode([
            'success' => false,
            'error' => 'Action inconnue'
        ]);
        exit;
}
