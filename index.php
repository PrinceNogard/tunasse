<?php
declare(strict_types=1);

/**
 * DuoBudget • Entry Point (PHP 8.3 Native MVC Controller)
 * 
 * Server-side renders the full financial management application, pre-computes
 * all financial algorithms, and hydrates the client with atomic persistent state.
 */

require_once __DIR__ . '/bootstrap.php';

use Tunasse\Storage\JsonStore;
use Tunasse\Finance\BudgetEngine;
use Tunasse\Finance\TravauxEngine;
use Tunasse\Finance\LoanCalculator;

// 1. Load persistent application state from JSON store
$store = new JsonStore();
$state = $store->load();

// 2. Compute domain financial logic via PHP engines
$budgets = BudgetEngine::calculate($state);
$travaux = TravauxEngine::calculate($state);

$simulator = LoanCalculator::calculate($state['simulator'] ?? []);

// 3. Render HTML views via modular PHP templates
require __DIR__ . '/templates/head.php';
require __DIR__ . '/templates/header.php';
require __DIR__ . '/templates/summary_strip.php';

echo "\n    <!-- MAIN VIEW CONTAINER -->\n    <main class=\"main-content\">\n";

require __DIR__ . '/templates/tab_dashboard.php';
require __DIR__ . '/templates/tab_simulator.php';
require __DIR__ . '/templates/tab_lcl.php';
require __DIR__ . '/templates/tab_revolut.php';
require __DIR__ . '/templates/tab_travaux.php';
require __DIR__ . '/templates/tab_profiles.php';

require __DIR__ . '/templates/footer.php';
