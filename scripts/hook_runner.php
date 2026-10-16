<?php
declare(strict_types=1);

/**
 * Antigravity Hook Runner (< 20ms)
 * Reads hook JSON context from stdin and outputs valid JSON {} on stdout.
 */

$start = microtime(true);
$rawInput = file_get_contents('php://stdin');
$context = json_decode($rawInput ?: '{}', true) ?? [];

$targetFile = null;
if (isset($context['toolCall']['args']['TargetFile'])) {
    $targetFile = $context['toolCall']['args']['TargetFile'];
}

$baseDir = realpath(__DIR__ . '/..');
$checker = $baseDir . '/scripts/check_anti_regression.php';

$output = [];
$status = 0;
$cmd = 'php ' . escapeshellarg($checker) . ($targetFile ? ' ' . escapeshellarg($targetFile) : '');
exec($cmd, $output, $status);

$durationMs = round((microtime(true) - $start) * 1000, 2);

if ($status !== 0) {
    fwrite(STDERR, implode("\n", $output) . "\n");
    echo json_encode([
        'error' => 'Anti-regression check failed',
        'durationMs' => $durationMs
    ]);
    exit(1);
}

// Success contract: return JSON object
echo json_encode(new stdClass());
exit(0);
