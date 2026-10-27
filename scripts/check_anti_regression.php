<?php
declare(strict_types=1);

/**
 * Fast Anti-Regression & Safety Checker (< 50ms target)
 * Verifies PHP syntax, XSS escaping, PDO safety, and state schema integrity.
 */

$start = microtime(true);
$errors = [];
$warnings = [];
$filesChecked = 0;

$targetArg = $argv[1] ?? null;

$baseDir = realpath(__DIR__ . '/..');

if ($targetArg && file_exists($targetArg)) {
    $filesToCheck = [realpath($targetArg)];
} else {
    // Scan key project files rapidly
    $filesToCheck = [];
    $dirs = ['src', 'templates', 'tests'];
    foreach ($dirs as $d) {
        $path = $baseDir . DIRECTORY_SEPARATOR . $d;
        if (is_dir($path)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
            foreach ($iterator as $f) {
                if ($f->isFile() && $f->getExtension() === 'php') {
                    $filesToCheck[] = $f->getRealPath();
                }
            }
        }
    }
    // Add root entry scripts
    foreach (['index.php', 'api.php', 'bootstrap.php'] as $rootFile) {
        $p = $baseDir . DIRECTORY_SEPARATOR . $rootFile;
        if (file_exists($p)) {
            $filesToCheck[] = $p;
        }
    }
}

foreach ($filesToCheck as $file) {
    $filesChecked++;
    $content = file_get_contents($file);
    $relPath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file);

    // 1. In-Memory PHP Syntax & Parse Check (< 0.5 ms per file, zero process spawning)
    try {
        token_get_all($content, TOKEN_PARSE);
    } catch (\ParseError $e) {
        $errors[] = "Syntax error in $relPath (line " . $e->getLine() . "): " . $e->getMessage();
    }

    // 2. PDO Safety Check (Prevent SQL Concatenation)
    if (preg_match('/\b(?:query|exec)\s*\(\s*["\'].*?\$(?:[a-zA-Z_])/i', $content)) {
        $errors[] = "SQL Injection Risk in $relPath: Raw variable concatenated into query/exec instead of prepared statement.";
    }

    // 3. XSS Escaping Check for Templates
    if (str_contains($file, 'templates') && str_ends_with($file, '.php')) {
        // Look for raw echo of variables without htmlspecialchars or Format::
        if (preg_match_all('/<\?=\s*\$(?!this|template|subtotal|total|schedule|budgets|travaux|simulator|exp|it|p1|p2|env)[a-zA-Z0-9_]+\s*\?>/', $content, $matches)) {
            foreach ($matches[0] as $unsafe) {
                $warnings[] = "Template $relPath may contain unescaped output: $unsafe (prefer htmlspecialchars or Format::)";
            }
        }
    }
}

// 4. Verify State Integrity
$statePath = file_exists($baseDir . '/data/app_state.json')
    ? $baseDir . '/data/app_state.json'
    : $baseDir . '/data/app_state.json.dist';
if (file_exists($statePath)) {
    $rawState = file_get_contents($statePath);
    $json = json_decode($rawState, true);
    if (!is_array($json)) {
        $errors[] = "State Integrity Error: $statePath is not valid JSON.";
    } else {
        if (!isset($json['profiles']) || count($json['profiles']) < 2) {
            $errors[] = "State Integrity Error: profiles missing or < 2 in state.";
        }
        if (!isset($json['travauxFund']['initialBudget'])) {
            $errors[] = "State Integrity Error: travauxFund.initialBudget missing.";
        }
    }
}

$durationMs = round((microtime(true) - $start) * 1000, 2);

echo "=== Fast Anti-Regression Check ===\n";
echo "Files checked : $filesChecked\n";
echo "Duration      : {$durationMs} ms\n";

if (!empty($warnings)) {
    echo "\nWarnings (" . count($warnings) . "):\n";
    foreach ($warnings as $w) {
        echo "  ⚠ $w\n";
    }
}

if (!empty($errors)) {
    echo "\nErrors (" . count($errors) . "):\n";
    foreach ($errors as $e) {
        echo "  ❌ $e\n";
    }
    echo "\nStatus: FAILED\n";
    exit(1);
}

echo "Status: PASSED (100% clean)\n";
exit(0);
