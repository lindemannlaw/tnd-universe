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
$phpBinary = null;
$composerBinary = null;
$runComposer = (($_GET['composer'] ?? '0') === '1');
if ($runComposer) {
    $projectRoot = realpath(__DIR__ . '/..');

    // Plesk's CLI default is often PHP 7.4, which can't satisfy Laravel 12's
    // ^8.2 platform constraint, so composer install bails on lock-file resolve.
    // Probe Plesk's per-version PHP locations and pick the first PHP >= 8.2 we find.
    $phpCandidates = [
        '/opt/plesk/php/8.4/bin/php',
        '/opt/plesk/php/8.3/bin/php',
        '/opt/plesk/php/8.2/bin/php',
        '/usr/local/psa/admin/sbin/modules/php-cli/php',
        PHP_BINARY,
        'php',
    ];
    foreach ($phpCandidates as $cand) {
        $cmd = sprintf('%s -r "echo PHP_VERSION_ID;" 2>/dev/null', escapeshellarg($cand));
        $vid = (int) trim((string) shell_exec($cmd));
        if ($vid >= 80200) {
            $phpBinary = $cand;
            break;
        }
    }

    if ($phpBinary === null) {
        http_response_code(500);
        header('Content-Type: text/plain');
        echo "✗ No PHP >= 8.2 binary found among:\n";
        foreach ($phpCandidates as $cand) echo "  - {$cand}\n";
        exit(1);
    }

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

    // Composer is itself a PHP phar — invoke it via the chosen PHP 8.x binary so
    // the platform check sees PHP 8.3, not the OS-default 7.4.
    //
    // Probe is done via the shell (`test -f`) instead of is_file()/file_exists()
    // because Plesk's open_basedir restricts those PHP filesystem calls to the
    // vhost root + /tmp, while shell_exec runs without that restriction. We must
    // point at the real .phar — calling /usr/local/bin/composer would invoke the
    // Plesk shell wrapper, which our chosen PHP would then mis-interpret as PHP
    // source (silently echoing the script and exiting 0 without running anything).
    $composerCandidates = [
        '/usr/local/psa/var/modules/composer/composer.phar',
        $projectRoot . '/composer.phar',
        '/opt/plesk/composer/bin/composer.phar',
        '/usr/local/bin/composer.phar',
    ];
    foreach ($composerCandidates as $cand) {
        $check = trim((string) shell_exec(sprintf('test -f %s && echo yes', escapeshellarg($cand))));
        if ($check === 'yes') {
            $composerBinary = $cand;
            break;
        }
    }

    if ($composerBinary === null) {
        http_response_code(500);
        header('Content-Type: text/plain');
        echo "✗ Composer phar not found among:\n";
        foreach ($composerCandidates as $cand) echo "  - {$cand}\n";
        exit(1);
    }

    // --working-dir is essential: passthru() inherits the SAPI cwd (typically public/),
    // so without it composer would look for composer.json next to post-deploy.php
    // and fail with "No composer.json in current directory".
    $composerCmd = sprintf(
        'HOME=%s COMPOSER_HOME=%s COMPOSER_ALLOW_SUPERUSER=1 %s %s install --working-dir=%s --no-dev --optimize-autoloader --no-interaction 2>&1',
        escapeshellarg($composerHome),
        escapeshellarg($composerHome),
        escapeshellarg($phpBinary),
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
        echo "PHP: {$phpBinary}\n";
        echo "Composer: {$composerBinary}\n";
        echo "Working dir: {$projectRoot}\n";
        echo "COMPOSER_HOME: {$composerHome}\n";
        echo $composerOutput . "\n";
        echo "✗ Composer install failed with exit code {$composerExitCode}\n";
        exit($composerExitCode);
    }
}

echo "<pre>\n";

if ($runComposer) {
    echo "=== Composer install ===\n";
    echo "PHP: {$phpBinary}\n";
    echo "Composer: {$composerBinary}\n";
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
