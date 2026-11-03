<?php
/**
 * Integration checks for the normalized MySQL schema.
 * Run with DB_NAME set to the database under test.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/reference_data.php';
require_once __DIR__ . '/../../includes/validate.php';

$pdo = get_db_connection();
$failures = [];

function check_database(bool $condition, string $message): void
{
    global $failures;
    if ($condition) {
        echo "PASS: {$message}\n";
        return;
    }
    echo "FAIL: {$message}\n";
    $failures[] = $message;
}

$catalog = get_reference_catalog($pdo);
$districtCount = array_sum(array_map('count', $catalog['districts_by_province']));

check_database(count($catalog['provinces']) === 7, 'Seven provinces are available');
check_database($districtCount === 77, 'Seventy-seven districts are available');
check_database(count($catalog['crops']) === 6, 'Six crops are available');
check_database(in_array('Makawanpur', $catalog['districts_by_province']['Bagmati Province'] ?? [], true), 'Canonical Makawanpur spelling is present');

$ids = resolve_reference_ids($pdo, 'Bagmati Province', 'Bhaktapur', 'Barley');
check_database($ids !== null, 'Province, district and crop resolve to foreign keys');

$mismatch = validate_inputs([
    'province' => 'Koshi Province',
    'district' => 'Bhaktapur',
    'crop' => 'Barley',
], $catalog);
check_database(isset($mismatch['errors']['district']), 'A district from the wrong province is rejected');

$foreignKeyCount = (int)$pdo->query("
    SELECT COUNT(*)
    FROM information_schema.referential_constraints
    WHERE constraint_schema = DATABASE()
")->fetchColumn();
check_database($foreignKeyCount === 6, 'All six foreign-key relationships exist');

$orphanHistory = (int)$pdo->query("
    SELECT COUNT(*)
    FROM historical_yields h
    LEFT JOIN districts d ON d.id = h.district_id
    LEFT JOIN crops c ON c.id = h.crop_id
    WHERE d.id IS NULL OR c.id IS NULL
")->fetchColumn();
check_database($orphanHistory === 0, 'Historical records have no orphaned references');

$orphanPredictions = (int)$pdo->query("
    SELECT COUNT(*)
    FROM predictions p
    LEFT JOIN districts d ON d.id = p.district_id
    LEFT JOIN crops c ON c.id = p.crop_id
    WHERE d.id IS NULL OR c.id IS NULL
")->fetchColumn();
check_database($orphanPredictions === 0, 'Prediction records have no orphaned references');

if ($ids !== null) {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("
        INSERT INTO predictions
          (user_session, district_id, crop_id, area_ha, climate_inputs, predicted_yield, model_version)
        VALUES
          ('normalization-test', :district_id, :crop_id, 1.00, '{}', 1000.00, 'test')
    ");
    $stmt->execute($ids);
    check_database((int)$pdo->lastInsertId() > 0, 'A normalized prediction audit row can be inserted');
    $pdo->rollBack();
}

if ($failures) {
    exit(1);
}

echo "Normalized database verification completed successfully.\n";
