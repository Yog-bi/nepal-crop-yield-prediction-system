<?php
/**
 * Farmer-friendly district crop-yield prediction page.
 */

declare(strict_types=1);

$currentPage = 'predict';
$pageTitle = 'Estimate Crop Yield';
$pageStylesheets = ['assets/vendor/leaflet/leaflet.css'];

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/flask_client.php';
require_once __DIR__ . '/../includes/reference_data.php';
require_once __DIR__ . '/../includes/validate.php';

$config = require __DIR__ . '/../includes/config.php';
$modelRanges = require __DIR__ . '/../includes/ranges.php';
$pdo = get_db_connection();
$referenceCatalog = get_reference_catalog($pdo);
$provincesList = $referenceCatalog['provinces'];
$districtsGrouped = $referenceCatalog['districts_by_province'];
$districtCentres = $referenceCatalog['district_centres'];
$cropsByDistrict = $referenceCatalog['crops_by_district'];
$cropRows = $referenceCatalog['crops'];

$error = null;
$validationErrors = [];
$result = null;
$historicalAvg = null;
$historicalCount = 0;
$inputsSent = null;

$provinceAliases = [
    'Bagmati' => 'Bagmati Province',
    'Madhesh' => 'Madhesh Province',
    'Province 1' => 'Koshi Province',
    'Koshi' => 'Koshi Province',
];
$districtAliases = [
    'Bhakthapur' => 'Bhaktapur',
    'Makwanpur' => 'Makawanpur',
];

$selProv = trim((string)($_POST['province'] ?? ''));
$selProv = $provinceAliases[$selProv] ?? $selProv;
$selDist = trim((string)($_POST['district'] ?? ''));
$selDist = $districtAliases[$selDist] ?? $selDist;
$selCrop = trim((string)($_POST['crop'] ?? ''));
$selScenario = trim((string)($_POST['climate_scenario'] ?? 'typical'));
$selLandUnit = trim((string)($_POST['land_unit'] ?? 'ropani'));

$advancedFields = [
    'area_ha', 'rainfall_mm_total', 'avg_temp_c', 'max_temp_c', 'min_temp_c',
    'humidity_pct', 'spec_humidity_g_kg', 'pressure_kpa', 'wind_speed_m_s',
    'solar_rad_kwh', 'latitude', 'longitude',
];
$hasAdvancedErrors = (bool)array_intersect($advancedFields, array_keys($validationErrors));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validRes = validate_inputs($_POST, $referenceCatalog);

    if (!$validRes['ok']) {
        $validationErrors = $validRes['errors'];
        $hasAdvancedErrors = (bool)array_intersect($advancedFields, array_keys($validationErrors));
        $errorCount = count($validationErrors);
        $firstValidationError = reset($validationErrors);
        $error = $errorCount === 1
            ? 'Please correct the highlighted field: ' . $firstValidationError
            : "Please correct {$errorCount} highlighted fields. Start with: {$firstValidationError}";
    } else {
        $apiPayload = $validRes['values'];
        $formMetadata = $validRes['metadata'];

        // For the two automatic modes, obtain the profile again on the server.
        // This prevents browser-side edits from silently changing the district
        // evidence supplied to the model. Custom mode deliberately keeps the
        // reviewed values entered under Advanced options.
        if ($formMetadata['climate_scenario'] !== 'custom') {
            $profileResponse = call_district_averages(
                $apiPayload['District'],
                $apiPayload['Crop'],
                $formMetadata['climate_scenario']
            );

            if (!$profileResponse['ok']) {
                $error = $profileResponse['error'];
            } else {
                $requiredProfileFields = [
                    'Area_ha', 'Rainfall_mm_total', 'Avg_Temperature_C',
                    'Mean_Daily_Max_Temperature_C', 'Mean_Daily_Min_Temperature_C',
                    'Relative_Humidity_pct', 'Specific_Humidity_g_kg',
                    'Surface_Pressure_kPa', 'Wind_Speed_m_s',
                    'Solar_Radiation_kWh_m2_day', 'Latitude', 'Longitude',
                ];
                foreach ($requiredProfileFields as $field) {
                    if (!array_key_exists($field, $profileResponse['averages'])) {
                        $error = 'The historical profile is missing a required model value.';
                        break;
                    }
                    $apiPayload[$field] = (float)$profileResponse['averages'][$field];
                }

                $profileProvince = (string)($profileResponse['averages']['Province'] ?? '');
                if ($error === null && $profileProvince !== '' && $profileProvince !== $apiPayload['Province']) {
                    $error = 'The historical profile does not match the selected province.';
                }

                if ($error === null) {
                    $formMetadata['climate_source_period'] = $profileResponse['source_period'];
                    $formMetadata['climate_record_count'] = $profileResponse['record_count'];
                }
            }
        }

        if ($error === null) {
            $apiResponse = call_prediction_api($apiPayload);

            if (!$apiResponse['ok']) {
                $error = $apiResponse['error'];
            } else {
            $predictedYield = $apiResponse['prediction'];
            $inputsSent = $apiResponse['inputs_received'] ?? $apiPayload;

            $result = [
                'predicted_yield' => $predictedYield,
                'province' => $apiPayload['Province'],
                'district' => $apiPayload['District'],
                'crop' => $apiPayload['Crop'],
                'model_area_ha' => $apiPayload['Area_ha'],
                'land_area_entered' => $formMetadata['land_area_entered'],
                'land_unit' => $formMetadata['land_unit'],
                'user_area_ha' => $formMetadata['user_area_ha'],
                'climate_scenario' => $formMetadata['climate_scenario'],
                'climate_source_period' => $formMetadata['climate_source_period'],
                'climate_record_count' => $formMetadata['climate_record_count'],
                'engine' => $apiResponse['engine'] ?? 'unknown',
                'model_version' => $apiResponse['model_version'] ?? $config['model_version'],
            ];

            try {
                $referenceIds = resolve_reference_ids(
                    $pdo,
                    $apiPayload['Province'],
                    $apiPayload['District'],
                    $apiPayload['Crop']
                );
                if ($referenceIds === null) {
                    throw new RuntimeException('The selected reference values could not be resolved.');
                }

                $auditInputs = $apiPayload;
                $auditInputs['_user_land_area'] = [
                    'entered_value' => $formMetadata['land_area_entered'],
                    'entered_unit' => $formMetadata['land_unit'],
                    'converted_hectares' => $formMetadata['user_area_ha'],
                ];
                $auditInputs['_interface_context'] = [
                    'climate_scenario' => $formMetadata['climate_scenario'],
                    'climate_source_period' => $formMetadata['climate_source_period'],
                    'climate_record_count' => $formMetadata['climate_record_count'],
                    'location_method' => $formMetadata['location_method'],
                ];

                $stmt = $pdo->prepare("
                    INSERT INTO predictions
                    (user_session, district_id, crop_id, area_ha, climate_inputs, predicted_yield, model_version)
                    VALUES (:sess, :district_id, :crop_id, :area, :json_inputs, :yield, :ver)
                ");
                $stmt->execute([
                    ':sess' => get_anonymised_session_id(),
                    ':district_id' => $referenceIds['district_id'],
                    ':crop_id' => $referenceIds['crop_id'],
                    ':area' => $apiPayload['Area_ha'],
                    ':json_inputs' => json_encode($auditInputs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ':yield' => $predictedYield,
                    ':ver' => $config['model_version'],
                ]);

                $histStmt = $pdo->prepare("
                    SELECT AVG(yield_kg_ha) AS avg_hist, COUNT(*) AS record_count
                    FROM historical_yields
                    WHERE district_id = :district_id AND crop_id = :crop_id
                ");
                $histStmt->execute([
                    ':district_id' => $referenceIds['district_id'],
                    ':crop_id' => $referenceIds['crop_id'],
                ]);
                $histRow = $histStmt->fetch();
                if ($histRow && $histRow['avg_hist'] !== null) {
                    $historicalAvg = (float)$histRow['avg_hist'];
                    $historicalCount = (int)$histRow['record_count'];
                }
            } catch (Throwable $dbError) {
                error_log('DB prediction audit failure: ' . $dbError->getMessage());
            }
            }
        }
    }
}

function posted_value(string $key, string $default = ''): string
{
    return htmlspecialchars((string)($_POST[$key] ?? $default), ENT_QUOTES, 'UTF-8');
}

function land_unit_label(string $unit): string
{
    return [
        'hectare' => 'hectares',
        'ropani' => 'ropani',
        'aana' => 'aana',
        'bigha' => 'bigha',
        'kattha' => 'kattha',
    ][$unit] ?? $unit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<main class="main-content prediction-page">
    <div class="app-container">
        <header class="prediction-intro">
            <span class="section-label">Crop yield estimate</span>
            <h1>Choose a place, crop and land size</h1>
            <p>
                The system supplies the technical location and climate information automatically.
                You can review or change those values under Advanced options.
            </p>
        </header>

        <?php if ($error): ?>
            <div id="formErrorSummary" class="alert-custom alert-custom-danger mb-4" role="alert" tabindex="-1">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="prediction-grid">
            <section class="card-custom simple-form-card" aria-labelledby="predictionFormTitle">
                <div class="step-heading">
                    <span>1</span>
                    <div>
                        <h2 id="predictionFormTitle">Tell us about the crop</h2>
                        <p>Only the fields in this section are needed for a normal estimate.</p>
                    </div>
                </div>

                <form id="predictForm" action="predict.php" method="POST" novalidate>
                    <input type="hidden" name="location_method" id="locationMethod" value="<?= posted_value('location_method', 'manual') ?>">
                    <input type="hidden" name="climate_source_period" id="climateSourcePeriod" value="<?= posted_value('climate_source_period') ?>">
                    <input type="hidden" name="climate_record_count" id="climateRecordCount" value="<?= posted_value('climate_record_count', '0') ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="provinceSelect" class="form-label">Province</label>
                            <select id="provinceSelect" name="province" class="form-select <?= isset($validationErrors['province']) ? 'is-invalid-input' : '' ?>" <?= isset($validationErrors['province']) ? 'aria-invalid="true" aria-describedby="provinceError"' : '' ?> required>
                                <option value="">Choose a province</option>
                                <?php foreach ($provincesList as $province): ?>
                                    <option value="<?= htmlspecialchars($province) ?>" <?= $selProv === $province ? 'selected' : '' ?>><?= htmlspecialchars($province) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($validationErrors['province'])): ?>
                                <small id="provinceError" class="range-warning" data-server-error="true"><?= htmlspecialchars($validationErrors['province']) ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="districtSelect" class="form-label">District</label>
                            <select id="districtSelect" name="district" class="form-select <?= isset($validationErrors['district']) ? 'is-invalid-input' : '' ?>" data-selected="<?= htmlspecialchars($selDist) ?>" <?= isset($validationErrors['district']) ? 'aria-invalid="true" aria-describedby="districtError"' : '' ?> required>
                                <option value="">Choose a district</option>
                            </select>
                            <?php if (isset($validationErrors['district'])): ?>
                                <small id="districtError" class="range-warning" data-server-error="true"><?= htmlspecialchars($validationErrors['district']) ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="cropSelect" class="form-label">Crop</label>
                            <select id="cropSelect" name="crop" class="form-select <?= isset($validationErrors['crop']) ? 'is-invalid-input' : '' ?>" data-selected="<?= htmlspecialchars($selCrop) ?>" <?= isset($validationErrors['crop']) ? 'aria-invalid="true" aria-describedby="cropError"' : '' ?> required>
                                <option value="">Choose a crop</option>
                                <?php foreach ($cropRows as $cropRow): ?>
                                    <option value="<?= htmlspecialchars($cropRow['name']) ?>"><?= htmlspecialchars($cropRow['display_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($validationErrors['crop'])): ?>
                                <small id="cropError" class="range-warning" data-server-error="true"><?= htmlspecialchars($validationErrors['crop']) ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="climateScenario" class="form-label">Climate information</label>
                            <select id="climateScenario" name="climate_scenario" class="form-select <?= isset($validationErrors['climate_scenario']) ? 'is-invalid-input' : '' ?>" <?= isset($validationErrors['climate_scenario']) ? 'aria-invalid="true" aria-describedby="climateScenarioError climateScenarioHelp"' : 'aria-describedby="climateScenarioHelp"' ?>>
                                <option value="typical" <?= $selScenario === 'typical' ? 'selected' : '' ?>>Typical completed periods</option>
                                <option value="recent" <?= $selScenario === 'recent' ? 'selected' : '' ?>>Most recent completed period</option>
                                <option value="custom" <?= $selScenario === 'custom' ? 'selected' : '' ?>>Enter a custom scenario</option>
                            </select>
                            <?php if (isset($validationErrors['climate_scenario'])): ?>
                                <small id="climateScenarioError" class="range-warning" data-server-error="true"><?= htmlspecialchars($validationErrors['climate_scenario']) ?></small>
                            <?php endif; ?>
                            <small id="climateScenarioHelp" class="field-help">This is historical climate information, not a live weather forecast.</small>
                        </div>
                    </div>

                    <div class="land-size-panel mt-4">
                        <div>
                            <label for="landArea" class="form-label">How much land will be cultivated?</label>
                            <div class="input-group land-input-group">
                                <input id="landArea" type="number" min="0.0001" max="100000" step="any" name="land_area" class="form-control <?= isset($validationErrors['land_area']) ? 'is-invalid-input' : '' ?>" value="<?= posted_value('land_area') ?>" placeholder="Enter land size" <?= isset($validationErrors['land_area']) ? 'aria-invalid="true" aria-describedby="landAreaError convertedLandArea"' : 'aria-describedby="convertedLandArea"' ?> required>
                                <select id="landUnit" name="land_unit" class="form-select <?= isset($validationErrors['land_unit']) ? 'is-invalid-input' : '' ?>" aria-label="Land unit">
                                    <option value="ropani" <?= $selLandUnit === 'ropani' ? 'selected' : '' ?>>Ropani</option>
                                    <option value="aana" <?= $selLandUnit === 'aana' ? 'selected' : '' ?>>Aana</option>
                                    <option value="bigha" <?= $selLandUnit === 'bigha' ? 'selected' : '' ?>>Bigha</option>
                                    <option value="kattha" <?= $selLandUnit === 'kattha' ? 'selected' : '' ?>>Kattha</option>
                                    <option value="hectare" <?= $selLandUnit === 'hectare' ? 'selected' : '' ?>>Hectare</option>
                                </select>
                            </div>
                            <?php if (isset($validationErrors['land_area'])): ?>
                                <small id="landAreaError" class="range-warning" data-server-error="true"><?= htmlspecialchars($validationErrors['land_area']) ?></small>
                            <?php endif; ?>
                            <?php if (isset($validationErrors['land_unit'])): ?>
                                <small class="range-warning"><?= htmlspecialchars($validationErrors['land_unit']) ?></small>
                            <?php endif; ?>
                            <small id="convertedLandArea" class="field-help" aria-live="polite"></small>
                        </div>
                    </div>

                    <div id="profileStatus" class="profile-status" role="status" aria-live="polite">
                        Choose a district and crop to load the model information.
                    </div>

                    <details id="advancedPanel" class="advanced-panel" <?= ($selScenario === 'custom' || $hasAdvancedErrors) ? 'open' : '' ?>>
                        <summary>
                            <span>Advanced options</span>
                            <small>Review the district and climate values supplied to the model</small>
                        </summary>
                        <div class="advanced-content">
                            <p class="advanced-note">
                                These values describe a district-level historical profile. They are filled automatically and are not the exact conditions of an individual farm.
                            </p>

                            <div class="row g-3">
                                <?php
                                $technicalInputs = [
                                            'area_ha' => ['District cultivated area used by model', 'ha', 'Area_ha'],
                                            'rainfall_mm_total' => ['Total rainfall', 'mm', 'Rainfall_mm_total'],
                                            'avg_temp_c' => ['Average temperature', '°C', 'Avg_Temperature_C'],
                                            'max_temp_c' => ['Mean daily maximum temperature', '°C', 'Mean_Daily_Max_Temperature_C'],
                                            'min_temp_c' => ['Mean daily minimum temperature', '°C', 'Mean_Daily_Min_Temperature_C'],
                                            'humidity_pct' => ['Relative humidity', '%', 'Relative_Humidity_pct'],
                                            'spec_humidity_g_kg' => ['Specific humidity', 'g/kg', 'Specific_Humidity_g_kg'],
                                            'pressure_kpa' => ['Surface pressure', 'kPa', 'Surface_Pressure_kPa'],
                                            'wind_speed_m_s' => ['Wind speed', 'm/s', 'Wind_Speed_m_s'],
                                            'solar_rad_kwh' => ['Solar radiation', 'kWh/m²/day', 'Solar_Radiation_kWh_m2_day'],
                                            'latitude' => ['District reference latitude', '°N', 'Latitude'],
                                            'longitude' => ['District reference longitude', '°E', 'Longitude'],
                                ];
                                foreach ($technicalInputs as $name => [$label, $unit, $modelField]):
                                    $range = $modelRanges[$modelField];
                                    $errorId = 'error-' . $name;
                                    $helpId = 'help-' . $name;
                                ?>
                                    <div class="col-md-6">
                                        <label class="form-label" for="field-<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($label) ?> (<?= htmlspecialchars($unit) ?>)</label>
                                        <input id="field-<?= htmlspecialchars($name) ?>" type="number" step="any" min="<?= htmlspecialchars((string)$range['min']) ?>" max="<?= htmlspecialchars((string)$range['max']) ?>" name="<?= htmlspecialchars($name) ?>" class="form-control technical-input <?= isset($validationErrors[$name]) ? 'is-invalid-input' : '' ?>" value="<?= posted_value($name) ?>" aria-describedby="<?= htmlspecialchars($helpId) ?><?= isset($validationErrors[$name]) ? ' ' . htmlspecialchars($errorId) : '' ?>" <?= isset($validationErrors[$name]) ? 'aria-invalid="true"' : '' ?> required>
                                        <small id="<?= htmlspecialchars($helpId) ?>" class="field-help field-range-hint">Accepted: <?= htmlspecialchars(format_validation_number((float)$range['min'])) ?> to <?= htmlspecialchars(format_validation_number((float)$range['max'])) ?> <?= htmlspecialchars($unit) ?>. Example: <?= htmlspecialchars((string)$range['example']) ?> <?= htmlspecialchars($unit) ?>.</small>
                                        <?php if (isset($validationErrors[$name])): ?>
                                            <small id="<?= htmlspecialchars($errorId) ?>" class="range-warning" data-server-error="true"><?= htmlspecialchars($validationErrors[$name]) ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </details>

                    <button id="predictButton" type="submit" class="btn btn-primary-custom prediction-submit" disabled>
                        Estimate crop yield
                    </button>
                    <p class="submission-note">The result is a district-level statistical estimate, not a harvest guarantee.</p>
                </form>
            </section>

            <aside class="map-and-result-column">
                <section class="card-custom map-card" aria-labelledby="mapTitle">
                    <div class="map-card-heading">
                        <div>
                            <span class="section-label">Location</span>
                            <h2 id="mapTitle">Choose on the map</h2>
                        </div>
                        <button type="button" id="useLocationBtn" class="btn btn-secondary-custom btn-sm" disabled>Use my location</button>
                    </div>
                    <p class="map-instruction">Click a district or use your device location. You can also use the province and district lists.</p>
                    <div id="locationMap" class="location-map" aria-label="Interactive map of Nepal's districts"></div>
                    <div id="mapLocationStatus" class="map-location-status" aria-live="polite">
                        No map location selected yet.
                    </div>
                    <p class="map-privacy">The exact point is used only to identify a district and is not saved. Boundary data © Open Knowledge Nepal, CC BY 4.0.</p>
                </section>

                <?php if ($result): ?>
                    <?php
                    $estimatedProduction = $result['predicted_yield'] * $result['user_area_ha'];
                    $differencePct = $historicalAvg && $historicalAvg > 0
                        ? (($result['predicted_yield'] - $historicalAvg) / $historicalAvg) * 100
                        : null;
                    $scenarioLabels = [
                        'typical' => 'typical completed periods',
                        'recent' => 'the most recent completed period',
                        'custom' => 'a custom climate scenario',
                    ];
                    ?>
                    <section class="result-panel farmer-result" aria-labelledby="resultTitle">
                        <span class="badge-tag"><?= htmlspecialchars($result['crop']) ?> · <?= htmlspecialchars($result['district']) ?></span>
                        <h2 id="resultTitle">Estimated district yield</h2>
                        <div class="result-number">
                            <?= number_format((float)$result['predicted_yield'], 0) ?>
                            <span>kg per hectare</span>
                        </div>

                        <div class="production-estimate">
                            <span>Approximate production for <?= htmlspecialchars((string)$result['land_area_entered']) ?> <?= htmlspecialchars(land_unit_label($result['land_unit'])) ?></span>
                            <strong><?= number_format($estimatedProduction, 0) ?> kg</strong>
                        </div>

                        <dl class="result-context">
                            <?php if ($historicalAvg): ?>
                                <div>
                                    <dt>Historical district average</dt>
                                    <dd><?= number_format($historicalAvg, 0) ?> kg/ha from <?= $historicalCount ?> record<?= $historicalCount === 1 ? '' : 's' ?></dd>
                                </div>
                            <?php endif; ?>
                            <?php if ($differencePct !== null): ?>
                                <div>
                                    <dt>Compared with that average</dt>
                                    <dd><?= number_format(abs($differencePct), 1) ?>% <?= $differencePct >= 0 ? 'above' : 'below' ?></dd>
                                </div>
                            <?php endif; ?>
                            <div>
                                <dt>Climate information used</dt>
                                <dd><?= htmlspecialchars($scenarioLabels[$result['climate_scenario']] ?? $result['climate_scenario']) ?><?= $result['climate_source_period'] !== '' ? ' · ' . htmlspecialchars($result['climate_source_period']) : '' ?></dd>
                            </div>
                        </dl>

                        <div class="alert-custom alert-custom-warning small mb-3">
                            This estimate uses district-level records and a Random Forest model. It is not a measurement of an individual field, a live weather forecast or a guaranteed harvest.
                        </div>

                        <a class="btn btn-secondary-custom w-100" href="history.php?district=<?= urlencode($result['district']) ?>&amp;crop=<?= urlencode($result['crop']) ?>">View historical evidence</a>

                        <?php if ($inputsSent): ?>
                            <details class="inputs-sent-panel mt-3">
                                <summary>Technical model inputs</summary>
                                <pre class="monospace"><?= htmlspecialchars(json_encode($inputsSent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                            </details>
                        <?php endif; ?>
                    </section>
                <?php else: ?>
                    <section class="card-custom expectation-card">
                        <span class="section-label">What you will receive</span>
                        <h2>A result with context</h2>
                        <ul>
                            <li>Estimated yield per hectare</li>
                            <li>Approximate production for your land size</li>
                            <li>Historical district comparison</li>
                            <li>The climate assumption used</li>
                        </ul>
                    </section>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</main>

<script>
window.NCYPS_REFERENCE_DATA = <?= json_encode([
    'districtsByProvince' => $districtsGrouped,
    'districtCentres' => $districtCentres,
    'cropsByDistrict' => $cropsByDistrict,
    'crops' => array_map(static fn(array $crop): array => [
        'name' => $crop['name'],
        'displayName' => $crop['display_name'],
    ], $cropRows),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="assets/vendor/leaflet/leaflet.js"></script>
<script src="assets/js/app.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
