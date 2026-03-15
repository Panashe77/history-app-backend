<?php
ob_start();
ini_set('display_errors', 0);
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
ob_clean();
echo json_encode([
    'SUPABASE_URL_set'      => !empty(getenv('SUPABASE_URL')),
    'SUPABASE_URL_value'    => getenv('SUPABASE_URL') ?: 'NOT SET',
    'SUPABASE_KEY_set'      => !empty(getenv('SUPABASE_SERVICE_KEY')),
    'SUPABASE_KEY_preview'  => getenv('SUPABASE_SERVICE_KEY') ? substr(getenv('SUPABASE_SERVICE_KEY'), 0, 20) . '...' : 'NOT SET',
    'php_version'           => PHP_VERSION,
    'curl_enabled'          => function_exists('curl_init'),
]);
?>
