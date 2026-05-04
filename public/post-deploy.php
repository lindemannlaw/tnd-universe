<?php
if (($_GET['token'] ?? '') !== 'tnd-pd-2026') {
    http_response_code(403);
    die('Forbidden');
}

// Verify the Vite manifest references files that actually exist on disk.
// Runs before any output so we can still send a 500 status if anything is missing.
$manifestPath = __DIR__ . '/build/manifest.json';
if (file_exists($manifestPath)) {
    $manifest = json_decode((string) file_get_contents($manifestPath), true);
    $missing = [];
    if (is_array($manifest)) {
        foreach ($manifest as $entry) {
            $file = $entry['file'] ?? null;
            if ($file && !file_exists(__DIR__ . '/build/' . $file)) {
                $missing[] = $file;
            }
        }
    }
    if ($missing) {
        http_response_code(500);
        header('Content-Type: text/plain');
        echo "DEPLOY BROKEN — manifest references missing files:\n";
        foreach ($missing as $f) {
            echo "  - $f\n";
        }
        exit(1);
    }
}

// Optional composer install (recommended for deploys that exclude vendor upload).
// Usage: ?token=...&composer=1
// Runs BEFORE <pre> is emitted so any failure path can still send a real HTTP 500
// instead of being trapped behind already-flushed headers.
$composerOutput = '';
$runComposer = (($_GET['composer'] ?? '0') === '1');
if ($runComposer) {
    $projectRoot = realpath(__DIR__ . '/..');

    // Ensure composer has writable HOME/COMPOSER_HOME in non-interactive web context.
    $composerHome = sys_get_temp_dir() . '/composer-home';
    if (!is_dir($composerHome)) {
        @mkdir($composerHome, 0775, true);
    }
    @putenv('HOME=' . $composerHome);
    @putenv('COMPOSER_HOME=' . $composerHome);
    @putenv('COMPOSER_ALLOW_SUPERUSER=1');
    $_SERVER['HOME'] = $composerHome;
    $_SERVER['COMPOSER_HOME'] = $composerHome;

    $composerBinary = null;
    foreach (['composer', '/usr/local/bin/composer', '/opt/bin/composer'] as $bin) {
        $checkCmd = sprintf('%s --version >/dev/null 2>&1', escapeshellarg($bin));
        exec($checkCmd, $out, $exitCode);
        if ($exitCode === 0) {
            $composerBinary = $bin;
            break;
        }
    }

    if ($composerBinary === null) {
        http_response_code(500);
        header('Content-Type: text/plain');
        echo "✗ Composer binary not found. Aborting.\n";
        exit(1);
    }

    // --working-dir is essential: passthru() inherits the SAPI cwd (typically public/),
    // so without it composer would look for composer.json next to post-deploy.php
    // and fail with "No composer.json in current directory".
    $composerCmd = sprintf(
        'HOME=%s COMPOSER_HOME=%s COMPOSER_ALLOW_SUPERUSER=1 %s install --working-dir=%s --no-dev --optimize-autoloader --no-interaction 2>&1',
        escapeshellarg($composerHome),
        escapeshellarg($composerHome),
        escapeshellarg($composerBinary),
        escapeshellarg($projectRoot)
    );

    ob_start();
    passthru($composerCmd, $composerExitCode);
    $composerOutput = (string) ob_get_clean();

    if ($composerExitCode !== 0) {
        http_response_code(500);
        header('Content-Type: text/plain');
        echo "=== Composer install ===\n";
        echo "Using COMPOSER_HOME={$composerHome}\n";
        echo "Working dir: {$projectRoot}\n";
        echo $composerOutput . "\n";
        echo "✗ Composer install failed with exit code {$composerExitCode}\n";
        exit($composerExitCode);
    }
}

echo "<pre>\n";

if ($runComposer) {
    echo "=== Composer install ===\n";
    echo "Using COMPOSER_HOME={$composerHome}\n";
    echo "Working dir: {$projectRoot}\n";
    echo $composerOutput;
    echo "✓ Composer install finished\n";
}

// ── Reset PHP Opcache FIRST (otherwise old compiled PHP keeps running) ──
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✓ Opcache reset\n";
} else {
    echo "– Opcache not available\n";
}

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->call('migrate', ['--force' => true]);
echo $kernel->output();

$kernel->call('storage:link', ['--force' => true]);
echo $kernel->output();

$kernel->call('view:clear');
echo $kernel->output();

$kernel->call('cache:clear');
echo $kernel->output();

$kernel->call('config:clear');
echo $kernel->output();

$kernel->call('route:clear');
echo $kernel->output();

echo "Done.\n";

// ── Optional: diagnose a project's description_blocks ──
// Usage: ?token=tnd-pd-2026&diagnose=PROJECT_ID
$diagnoseId = $_GET['diagnose'] ?? null;
if ($diagnoseId) {
    echo "\n=== DIAGNOSTIC for project #{$diagnoseId} ===\n";

    $rawJson = \Illuminate\Support\Facades\DB::table('projects')
        ->where('id', $diagnoseId)
        ->value('description_blocks');

    if ($rawJson === null) {
        echo "description_blocks column is NULL (no data or column missing)\n";
    } else {
        $decoded = json_decode($rawJson, true);
        if ($decoded === null) {
            echo "JSON decode FAILED. Raw (first 500 chars):\n";
            echo mb_substr($rawJson, 0, 500) . "\n";
        } else {
            foreach (['en', 'de'] as $locale) {
                $blocks = $decoded[$locale] ?? null;
                if ($blocks === null) {
                    echo "  [{$locale}] NOT PRESENT in JSON\n";
                    continue;
                }
                echo "  [{$locale}] " . count($blocks) . " blocks:\n";
                foreach ($blocks as $bi => $block) {
                    $type = $block['type'] ?? '???';
                    echo "    block[{$bi}] type={$type}";
                    if (isset($block['items'])) {
                        foreach ($block['items'] as $ii => $item) {
                            $cs = $item['col_span'] ?? '-';
                            $cst = $item['col_start'] ?? '-';
                            echo " | item[{$ii}] col_span={$cs} col_start={$cst}";
                        }
                    }
                    echo "\n";
                }
            }
        }
    }

    // Show file modification time of key PHP files to verify deploy
    $files = [
        'Controller' => __DIR__ . '/../app/Http/Controllers/Admin/Portfolio/ProjectController.php',
        'Model' => __DIR__ . '/../app/Models/Project.php',
    ];
    echo "\n=== FILE TIMESTAMPS (deploy check) ===\n";
    foreach ($files as $label => $path) {
        if (file_exists($path)) {
            echo "  {$label}: " . date('Y-m-d H:i:s', filemtime($path)) . "\n";
        } else {
            echo "  {$label}: FILE NOT FOUND\n";
        }
    }
}

echo "</pre>";
