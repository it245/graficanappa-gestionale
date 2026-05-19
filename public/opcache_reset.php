<?php
// Trigger OPcache reset via web. Solo da localhost o IP interno.
$allowed = ['127.0.0.1', '::1', '192.168.1.', '192.168.0.'];
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ok = false;
foreach ($allowed as $a) {
    if (str_starts_with($ip, $a)) { $ok = true; break; }
}
if (!$ok) { http_response_code(403); exit('forbidden'); }

if (function_exists('opcache_reset')) {
    $r = opcache_reset();
    echo $r ? "OPcache reset OK\n" : "OPcache reset FAIL\n";
    if (function_exists('opcache_get_status')) {
        $s = opcache_get_status(false);
        echo "cached_scripts=" . ($s['opcache_statistics']['num_cached_scripts'] ?? '?') . "\n";
    }
} else {
    echo "OPcache extension non disponibile\n";
}
