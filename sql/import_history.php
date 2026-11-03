<?php
/**
 * Import cleaned model-ready crop and climate records into historical_yields.
 * Usage: php sql/import_history.php "path/to/model_dataset_audit_v1.csv"
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$csvPath = $argv[1] ?? '';
if ($csvPath === '' || !is_file($csvPath) || !is_readable($csvPath)) {
    fwrite(STDERR, "Provide a readable combined dataset CSV path.\n");
    exit(1);
}

$handle = fopen($csvPath, 'rb');
if ($handle === false) {
    fwrite(STDERR, "Unable to open the dataset.\n");
    exit(1);
}

$headers = fgetcsv($handle);
$required = [
    'Training_Inclusion', 'District', 'Crop', 'Start_Year', 'Yield_kg_ha',
    'Rainfall_mm_total', 'Avg_Temperature_C'
];

if ($headers === false || array_diff($required, $headers)) {
    fclose($handle);
    fwrite(STDERR, "The CSV does not contain the required model-ready columns.\n");
    exit(1);
}

$positions = array_flip($headers);
$records = [];
$seen = [];

while (($row = fgetcsv($handle)) !== false) {
    if (($row[$positions['Training_Inclusion']] ?? '') !== 'included') {
        continue;
    }

    $record = [
        trim((string)$row[$positions['District']]),
        trim((string)$row[$positions['Crop']]),
        (int)$row[$positions['Start_Year']],
        (float)$row[$positions['Yield_kg_ha']],
        (float)$row[$positions['Rainfall_mm_total']],
        (float)$row[$positions['Avg_Temperature_C']],
    ];

    $key = $record[0] . '|' . $record[1] . '|' . $record[2];
    if ($record[0] === '' || $record[1] === '' || $record[2] < 1900 || isset($seen[$key])) {
        continue;
    }

    $seen[$key] = true;
    $records[] = $record;
}
fclose($handle);

if (count($records) < 100) {
    fwrite(STDERR, "Import stopped: fewer than 100 eligible records were found.\n");
    exit(1);
}

require_once __DIR__ . '/../includes/db.php';
$pdo = get_db_connection();

$districtAliases = [
    'Bhakthapur' => 'Bhaktapur',
    'Nawalpur' => 'Nawalparasi East',
    'Parasi' => 'Nawalparasi West',
    'Eastern Rukum' => 'Rukum East',
    'Western Rukum' => 'Rukum West',
    'Makwanpur' => 'Makawanpur',
];

$districtIds = $pdo->query('SELECT name, id FROM districts')->fetchAll(PDO::FETCH_KEY_PAIR);
$cropIds = $pdo->query('SELECT name, id FROM crops')->fetchAll(PDO::FETCH_KEY_PAIR);
$normalizedRecords = [];
$unresolved = [];

foreach ($records as $record) {
    [$districtName, $cropName, $year, $yield, $rainfall, $temperature] = $record;
    $districtName = $districtAliases[$districtName] ?? $districtName;

    if (!isset($districtIds[$districtName])) {
        $unresolved[] = "district: {$districtName}";
        continue;
    }
    if (!isset($cropIds[$cropName])) {
        $unresolved[] = "crop: {$cropName}";
        continue;
    }

    $normalizedRecords[] = [
        (int)$districtIds[$districtName],
        (int)$cropIds[$cropName],
        $year,
        $yield,
        $rainfall,
        $temperature,
    ];
}

if ($unresolved) {
    $unresolved = array_values(array_unique($unresolved));
    fwrite(STDERR, "Import stopped because reference values are missing: " . implode(', ', $unresolved) . "\n");
    exit(1);
}

$pdo->beginTransaction();

try {
    $pdo->exec('DELETE FROM historical_yields');
    $insert = $pdo->prepare("
        INSERT INTO historical_yields
            (district_id, crop_id, year, yield_kg_ha, rainfall_mm, avg_temp_c)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($normalizedRecords as $record) {
        $insert->execute($record);
    }

    $pdo->commit();
    echo sprintf("Imported %d verified historical records from %s.\n", count($normalizedRecords), basename($csvPath));
} catch (Throwable $error) {
    $pdo->rollBack();
    fwrite(STDERR, "Import failed and no database changes were kept: {$error->getMessage()}\n");
    exit(1);
}
