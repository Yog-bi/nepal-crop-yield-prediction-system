<?php
/**
 * PHP Proxy Endpoint for District Averages.
 * Relays AJAX requests from client to Flask /district_averages endpoint.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/flask_client.php';

$district = trim($_GET['district'] ?? '');
$crop = trim($_GET['crop'] ?? '');
$scenario = trim($_GET['scenario'] ?? 'typical');

if (empty($district) || empty($crop)) {
    echo json_encode([
        'ok' => false,
        'error' => 'Both district and crop parameters are required.'
    ]);
    exit;
}

if (!in_array($scenario, ['typical', 'recent'], true)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'The requested climate-data option is not supported.'
    ]);
    exit;
}

$res = call_district_averages($district, $crop, $scenario);
if (!$res['ok']) {
    http_response_code(422);
}
echo json_encode($res);
