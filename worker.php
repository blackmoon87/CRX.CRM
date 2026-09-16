<?php

declare(strict_types=1);

/**
 * Async Job Queue Worker for CRX
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Worker must be run from the command line.');
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    $prefixes = [
        'Spartan\\' => __DIR__ . '/framework/src/',
        'App\\'     => __DIR__ . '/src/',
    ];

    spl_autoload_register(function (string $class) use ($prefixes): void {
        foreach ($prefixes as $prefix => $baseDir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                continue;
            }
            $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    });

    require_once __DIR__ . '/framework/src/helpers.php';
}

use Spartan\Application;
use Spartan\JobQueue;

$config = require __DIR__ . '/config/config.php';
$config['base_path'] ??= __DIR__;
$app = new Application($config);

if ($app->db === null) {
    fwrite(STDERR, "[Worker] No database connection. Check .env DB credentials.\n");
    exit(1);
}

$queue     = new JobQueue($app->db);
$loopMode  = in_array('--loop', $argv ?? [], true);
$pollSecs  = 5;
$shouldStop = false;

if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGTERM, function () use (&$shouldStop) {
        echo "\n[Worker] SIGTERM received — finishing current job and shutting down...\n";
        $shouldStop = true;
    });
    pcntl_signal(SIGINT, function () use (&$shouldStop) {
        echo "\n[Worker] SIGINT received — finishing current job and shutting down...\n";
        $shouldStop = true;
    });
}

echo "[Worker] Started" . ($loopMode ? " in loop mode (polling every {$pollSecs}s)" : '') . ".\n";

do {
    if (function_exists('pcntl_signal_dispatch')) {
        pcntl_signal_dispatch();
    }

    if ($shouldStop) {
        break;
    }

    $processed = $queue->processPending();

    if ($processed > 0) {
        echo "[Worker] " . date('Y-m-d H:i:s') . " — processed {$processed} job(s).\n";
    }

    if ($loopMode && !$shouldStop) {
        sleep($pollSecs);
    }

} while ($loopMode && !$shouldStop);

echo "[Worker] Shut down gracefully.\n";
