<?php
require_once __DIR__ . '/config.php';

header('Content-Type: text/plain; charset=utf-8');

$key = isset($_GET['key']) ? (string) $_GET['key'] : '';

if (!hash_equals(GA_CACHE_CLEAR_KEY, $key)) {
    http_response_code(403);
    echo "Forbidden.\n";
    exit;
}

$cleared = 0;
if (is_dir(GA_CACHE_DIR)) {
    foreach (glob(rtrim(GA_CACHE_DIR, '/\\') . '/*.json') as $file) {
        if (@unlink($file)) {
            $cleared++;
        }
    }
}

echo "Cache cleared: {$cleared} file(s) removed.\n";
