<?php
/**
 * Server-side Input Validation Module.
 * Validates post parameters against exact model feature ranges and allowed options.
 * Includes automatic alias normalization for legacy/short province & district names.
 */

declare(strict_types=1);

function land_unit_factors(): array
{
    return [
        'hectare' => 1.0,
        'ropani' => 0.05087,
        'aana' => 0.05087 / 16,
        'bigha' => 0.6773,
        'kattha' => 0.6773 / 20,
    ];
}

function format_validation_number(float $value): string
{
    $formatted = number_format($value, 2, '.', '');
    return rtrim(rtrim($formatted, '0'), '.');
}

function validate_inputs(array $post, ?array $referenceCatalog = null): array
{
    if ($referenceCatalog === null) {
        $provinces = require __DIR__ . '/provinces.php';
        $districtMap = require __DIR__ . '/districts.php';
        $validCrops = ['Barley', 'Buckwheat', 'Maize', 'Millet', 'Paddy', 'Wheat'];
    } else {
        $provinces = $referenceCatalog['provinces'];
        $districtMap = $referenceCatalog['districts_by_province'];
        $validCrops = array_column($referenceCatalog['crops'], 'name');
    }
    $ranges = require __DIR__ . '/ranges.php';

    $errors = [];
    $parsedValues = [];

    // Province Alias Normalization Mapping
    $provinceAliases = [
        'Bagmati' => 'Bagmati Province',
        'Madhesh' => 'Madhesh Province',
        'Province 1' => 'Koshi Province',
        'Koshi' => 'Koshi Province',
        'Gandaki' => 'Gandaki Province',
        'Lumbini' => 'Lumbini Province',
        'Karnali' => 'Karnali Province',
        'Sudurpashchim' => 'Sudurpashchim Province'
    ];

    // District Alias Normalization Mapping
    $districtAliases = [
        'Bhakthapur' => 'Bhaktapur',
        'Kavre' => 'Kavrepalanchok',
        'Sindhupalchowk' => 'Sindhupalchok',
        'Nawalparasi' => 'Nawalparasi East',
        'Rukum' => 'Rukum East',
        'Tanahun' => 'Tanahu',
        'Makwanpur' => 'Makawanpur'
    ];

    // 1. Province validation & auto-normalization
    $provinceRaw = trim($post['province'] ?? '');
    $province = $provinceAliases[$provinceRaw] ?? $provinceRaw;

    if (empty($province)) {
        $errors['province'] = "Province selection is required.";
    } elseif (!in_array($province, $provinces, true)) {
        $errors['province'] = "Invalid Province '{$provinceRaw}'. Please select from the dropdown.";
    } else {
        $parsedValues['Province'] = $province;
    }

    // 2. District validation & auto-normalization
    $districtRaw = trim($post['district'] ?? '');
    $district = $districtAliases[$districtRaw] ?? $districtRaw;

    $allDistricts = [];
    foreach ($districtMap as $provDistricts) {
        $allDistricts = array_merge($allDistricts, $provDistricts);
    }

    if (empty($district)) {
        $errors['district'] = "District selection is required.";
    } elseif (!in_array($district, $allDistricts, true)) {
        $errors['district'] = "Invalid District '{$districtRaw}'. Please select from the dropdown.";
    } elseif (isset($parsedValues['Province'])
        && !in_array($district, $districtMap[$parsedValues['Province']] ?? [], true)) {
        $errors['district'] = "The selected district does not belong to the selected province.";
    } else {
        $parsedValues['District'] = $district;
    }

    // 3. Crop validation
    $crop = trim($post['crop'] ?? '');
    if (empty($crop)) {
        $errors['crop'] = "Crop selection is required.";
    } elseif (!in_array($crop, $validCrops, true)) {
        $errors['crop'] = "Invalid Crop '{$crop}'. Please select from the dropdown.";
    } elseif ($referenceCatalog !== null
        && isset($parsedValues['District'])
        && !in_array($crop, $referenceCatalog['crops_by_district'][$parsedValues['District']] ?? [], true)) {
        $errors['crop'] = "Historical records for this crop and district are not available.";
    } else {
        $parsedValues['Crop'] = $crop;
    }

    // 4. Farmer-entered land area is used only to estimate total production.
    // The model's Area_ha remains the district-crop cultivated area supplied by
    // the historical feature profile.
    $landAreaRaw = trim((string)($post['land_area'] ?? ''));
    $landUnit = trim((string)($post['land_unit'] ?? 'hectare'));
    $landFactors = land_unit_factors();
    $userAreaHectares = null;

    if ($landAreaRaw === '') {
        $errors['land_area'] = 'Land size is empty. Enter the amount of land you plan to cultivate.';
    } elseif (!is_numeric($landAreaRaw)) {
        $errors['land_area'] = "Land size '{$landAreaRaw}' is not a number. Enter a number such as 5 or 2.5.";
    } elseif ((float)$landAreaRaw <= 0) {
        $errors['land_area'] = "You entered {$landAreaRaw}. Land size must be greater than 0.";
    } elseif ((float)$landAreaRaw > 100000) {
        $errors['land_area'] = "You entered {$landAreaRaw} {$landUnit}. Enter 100,000 or less for this prototype.";
    } elseif (!isset($landFactors[$landUnit])) {
        $errors['land_unit'] = 'Select a supported land unit.';
    } else {
        $userAreaHectares = (float)$landAreaRaw * $landFactors[$landUnit];
    }

    $climateScenario = trim((string)($post['climate_scenario'] ?? 'typical'));
    if (!in_array($climateScenario, ['typical', 'recent', 'custom'], true)) {
        $errors['climate_scenario'] = 'Select a valid climate-data option.';
    }

    $locationMethod = trim((string)($post['location_method'] ?? 'manual'));
    if (!in_array($locationMethod, ['manual', 'map', 'device'], true)) {
        $locationMethod = 'manual';
    }

    // 5. Numerical model feature ranges validation
    $fieldMap = [
        'area_ha' => 'Area_ha',
        'rainfall_mm_total' => 'Rainfall_mm_total',
        'avg_temp_c' => 'Avg_Temperature_C',
        'max_temp_c' => 'Mean_Daily_Max_Temperature_C',
        'min_temp_c' => 'Mean_Daily_Min_Temperature_C',
        'humidity_pct' => 'Relative_Humidity_pct',
        'spec_humidity_g_kg' => 'Specific_Humidity_g_kg',
        'pressure_kpa' => 'Surface_Pressure_kPa',
        'wind_speed_m_s' => 'Wind_Speed_m_s',
        'solar_rad_kwh' => 'Solar_Radiation_kWh_m2_day',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude'
    ];

    foreach ($fieldMap as $formKey => $modelFeatureKey) {
        $valStr = trim((string)($post[$formKey] ?? ''));
        $range = $ranges[$modelFeatureKey];
        $fieldName = $range['name'] ?? $range['label'];
        $unit = trim((string)($range['unit'] ?? ''));
        $unitSuffix = $unit === '' ? '' : ' ' . $unit;
        $example = (string)($range['example'] ?? $range['min']);
        $minimum = format_validation_number((float)$range['min']);
        $maximum = format_validation_number((float)$range['max']);

        if ($valStr === '') {
            $errors[$formKey] = "{$fieldName} is empty. Enter a number, for example {$example}{$unitSuffix}.";
            continue;
        }

        if (!is_numeric($valStr)) {
            $errors[$formKey] = "{$fieldName} '{$valStr}' is not a number. Enter a number, for example {$example}{$unitSuffix}.";
            continue;
        }

        $valFloat = (float)$valStr;
        $entered = format_validation_number($valFloat);
        if ($valFloat < $range['min']) {
            $errors[$formKey] = "You entered {$entered}{$unitSuffix} for {$fieldName}. The minimum accepted value is {$minimum}{$unitSuffix}; enter a value from {$minimum} to {$maximum}{$unitSuffix}.";
        } elseif ($valFloat > $range['max']) {
            $errors[$formKey] = "You entered {$entered}{$unitSuffix} for {$fieldName}. The maximum accepted value is {$maximum}{$unitSuffix}; enter a value from {$minimum} to {$maximum}{$unitSuffix}.";
        } else {
            $parsedValues[$modelFeatureKey] = $valFloat;
        }
    }

    if (!empty($errors)) {
        return [
            'ok' => false,
            'errors' => $errors
        ];
    }

    return [
        'ok' => true,
        'values' => $parsedValues,
        'metadata' => [
            'land_area_entered' => (float)$landAreaRaw,
            'land_unit' => $landUnit,
            'user_area_ha' => $userAreaHectares,
            'climate_scenario' => $climateScenario,
            'climate_source_period' => trim((string)($post['climate_source_period'] ?? '')),
            'climate_record_count' => max(0, (int)($post['climate_record_count'] ?? 0)),
            'location_method' => $locationMethod,
        ]
    ];
}
