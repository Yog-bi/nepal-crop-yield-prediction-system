<?php
/**
 * CLI PHP Test Script for Input Validation & Helper Functions
 * Run with: php tests/tests_php/test_validation.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validate.php';

ini_set('session.save_path', sys_get_temp_dir());
start_secure_session();
echo "=== Running PHP Validation & Helper Tests ===\n\n";

$passCount = 0;
$failCount = 0;

function assert_test(bool $condition, string $testName) {
    global $passCount, $failCount;
    if ($condition) {
        echo "✅ PASS: {$testName}\n";
        $passCount++;
    } else {
        echo "❌ FAIL: {$testName}\n";
        $failCount++;
    }
}

// 1. Test HTML escaping (XSS Protection)
$rawXss = '<script>alert("xss")</script>';
$escaped = e($rawXss);
assert_test($escaped === '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', "HTML escaping converts special characters");

// 2. Test CSRF token generation & verification
$token = generate_csrf_token();
assert_test(!empty($token) && strlen($token) === 64, "CSRF token is valid 64-character hex string");
assert_test(verify_csrf_token($token) === true, "CSRF verification passes with matching token");
assert_test(verify_csrf_token("invalid_token") === false, "CSRF verification fails with invalid token");

// 3. Test Yield Formatting
$formattedYield = format_yield(3450.789);
assert_test($formattedYield === '3,450.79 kg/ha', "Yield numeric formatting formats to 2 decimal places with kg/ha");

// 4. Test Password Hashing Compatibility
$password = " " . bin2hex(random_bytes(24)) . " ";
$hash = password_hash($password, PASSWORD_BCRYPT);
assert_test(password_verify($password, $hash) === true, "Bcrypt password hash verifies correctly");
assert_test(password_verify("WrongPassword", $hash) === false, "Bcrypt password fails for invalid password");

// 5. Test farmer-facing land conversions used for production estimates
$landFactors = land_unit_factors();
assert_test(abs($landFactors['ropani'] - 0.05087) < 0.0000001, "Ropani converts to hectares correctly");
assert_test(abs(($landFactors['aana'] * 16) - 0.05087) < 0.0000001, "Sixteen aana equal one ropani");
assert_test(abs($landFactors['bigha'] - 0.6773) < 0.0000001, "Bigha converts to hectares correctly");
assert_test(abs(($landFactors['kattha'] * 20) - 0.6773) < 0.0000001, "Twenty kattha equal one bigha");

// 6. Verify that farmer land size is metadata and does not replace the
// district cultivated-area feature supplied to the model.
$catalog = [
    'provinces' => ['Bagmati Province'],
    'districts_by_province' => ['Bagmati Province' => ['Bhaktapur']],
    'crops' => [['name' => 'Barley', 'display_name' => 'Barley']],
    'crops_by_district' => ['Bhaktapur' => ['Barley']],
];
$validPost = [
    'province' => 'Bagmati Province',
    'district' => 'Bhaktapur',
    'crop' => 'Barley',
    'land_area' => '2',
    'land_unit' => 'ropani',
    'climate_scenario' => 'typical',
    'location_method' => 'map',
    'area_ha' => '46',
    'rainfall_mm_total' => '1205.39',
    'avg_temp_c' => '19.50',
    'max_temp_c' => '24.57',
    'min_temp_c' => '15.75',
    'humidity_pct' => '59.63',
    'spec_humidity_g_kg' => '10.16',
    'pressure_kpa' => '87.32',
    'wind_speed_m_s' => '2.20',
    'solar_rad_kwh' => '4.70',
    'latitude' => '27.67',
    'longitude' => '85.44',
];
$validResult = validate_inputs($validPost, $catalog);
assert_test($validResult['ok'] === true, "Farmer-facing form passes server-side validation");
assert_test(abs($validResult['metadata']['user_area_ha'] - 0.10174) < 0.0000001, "Farmer parcel is converted separately to hectares");
assert_test($validResult['values']['Area_ha'] === 46.0, "District cultivated area remains the model feature");

$invalidCropPost = $validPost;
$invalidCropPost['crop'] = 'Paddy';
$invalidCropResult = validate_inputs($invalidCropPost, $catalog);
assert_test($invalidCropResult['ok'] === false && isset($invalidCropResult['errors']['crop']), "Unavailable district-crop combination is rejected");

// 7. Field errors identify the exact value, field, limit and unit.
$tooHotPost = $validPost;
$tooHotPost['avg_temp_c'] = '50';
$tooHotResult = validate_inputs($tooHotPost, $catalog);
$temperatureError = $tooHotResult['errors']['avg_temp_c'] ?? '';
assert_test(
    str_contains($temperatureError, 'You entered 50 °C for Average temperature')
    && str_contains($temperatureError, 'maximum accepted value is 26.51 °C')
    && str_contains($temperatureError, 'from -5.54 to 26.51 °C'),
    "Temperature error explains the entered value and accepted range"
);

$emptyTemperaturePost = $validPost;
$emptyTemperaturePost['avg_temp_c'] = '';
$emptyTemperatureResult = validate_inputs($emptyTemperaturePost, $catalog);
assert_test(
    ($emptyTemperatureResult['errors']['avg_temp_c'] ?? '') === 'Average temperature is empty. Enter a number, for example 20 °C.',
    "Empty temperature error names the field and gives an example"
);

echo "\n============================================\n";
echo "Test Results: {$passCount} Passed, {$failCount} Failed.\n";

if ($failCount > 0) {
    exit(1);
}
