<?php
declare(strict_types=1);

/**
 * Script CLI de synchronisation bancaire automatique (LCL & Revolut)
 * Utilisable en tâche de fond (cron ou ordonnanceur) ou manuellement.
 */

require_once __DIR__ . '/../bootstrap.php';

use Tunasse\Storage\JsonStore;
use Tunasse\Banking\GoCardlessClient;

echo "=== Tunasse Automated Banking Sync (LCL & Revolut) ===\n";
$start = microtime(true);

$store = new JsonStore();
$state = $store->load();

$banking = $state['banking'] ?? [];
$secretId = $banking['secret_id'] ?? null;
$secretKey = $banking['secret_key'] ?? null;

$client = new GoCardlessClient($secretId, $secretKey);

if (!$client->isConfigured()) {
    echo "ℹ Note: Clés API GoCardless non renseignées. Exécution en mode démonstration/simulation.\n";
}

$result = $client->syncAll($state);
$store->save($state);

$duration = round((microtime(true) - $start) * 1000, 2);

echo "✔ Synchronisation terminée en {$duration} ms\n";
echo "Comptes synchronisés : " . ($result['synced_accounts'] ?? 0) . "\n";
echo "Dernier horodatage   : " . ($result['last_sync'] ?? 'N/A') . "\n";
foreach ($result['accounts'] ?? [] as $acc) {
    $bal = number_format((float)($acc['last_balance'] ?? 0), 2, ',', ' ');
    echo "  - [{$acc['institution_id']}] {$acc['name']} : {$bal} € ({$acc['status']})\n";
}
echo "=======================================================\n";
