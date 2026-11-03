<?php
/**
 * Flask API REST Client Integration Layer.
 * Connects PHP application to Python Flask REST API.
 */

declare(strict_types=1);

function call_prediction_api(array $inputs): array
{
    $config = require __DIR__ . '/config.php';
    $url = rtrim($config['flask_url'], '/') . '/predict';

    $payload = json_encode($inputs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Content-Length: ' . strlen($payload)
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return [
            'ok' => false,
            'error' => 'Unable to connect to prediction service: ' . ($curlError ?: 'Connection timed out')
        ];
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        $errorMessage = $data['error'] ?? ('Prediction service returned HTTP code ' . $httpCode);
        return [
            'ok' => false,
            'error' => $errorMessage
        ];
    }

    if (!isset($data['success']) || $data['success'] !== true || !isset($data['prediction'])) {
        return [
            'ok' => false,
            'error' => 'Invalid response structure from prediction service'
        ];
    }

    return [
        'ok' => true,
        'prediction' => (float)$data['prediction'],
        'engine' => $data['engine'] ?? 'unknown',
        'model_version' => $data['model_version'] ?? 'v1',
        'inputs_received' => $data['inputs_received'] ?? $inputs
    ];
}

/**
 * Fetches historical feature averages for selected district + crop from Flask REST API.
 */
function call_district_averages(string $district, string $crop, string $scenario = 'typical'): array
{
    $config = require __DIR__ . '/config.php';
    $url = rtrim($config['flask_url'], '/') . '/district_averages?' . http_build_query([
        'district' => $district,
        'crop' => $crop,
        'scenario' => $scenario,
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return [
            'ok' => false,
            'error' => 'Unable to reach averages service: ' . ($curlError ?: 'Connection failed')
        ];
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200 || !isset($data['success']) || $data['success'] !== true) {
        return [
            'ok' => false,
            'error' => $data['error'] ?? ('Averages endpoint returned HTTP code ' . $httpCode)
        ];
    }

    return [
        'ok' => true,
        'averages' => $data['averages'] ?? [],
        'record_count' => (int)($data['record_count'] ?? 0),
        'source_period' => (string)($data['source_period'] ?? ''),
        'scenario' => (string)($data['scenario'] ?? $scenario),
    ];
}
