<?php
/**
 * Exact Feature Ranges derived from model training dataset.
 */

declare(strict_types=1);

return [
    'Area_ha' => [
        'label' => 'Area (ha)',
        'name' => 'District cultivated area',
        'unit' => 'ha',
        'example' => '500',
        'min' => 1.0,
        'max' => 95311.0
    ],
    'Rainfall_mm_total' => [
        'label' => 'Total Rainfall (mm)',
        'name' => 'Total rainfall',
        'unit' => 'mm',
        'example' => '1200',
        'min' => 354.55,
        'max' => 2888.66
    ],
    'Avg_Temperature_C' => [
        'label' => 'Average Temperature (°C)',
        'name' => 'Average temperature',
        'unit' => '°C',
        'example' => '20',
        'min' => -5.54,
        'max' => 26.51
    ],
    'Mean_Daily_Max_Temperature_C' => [
        'label' => 'Mean Daily Max Temperature (°C)',
        'name' => 'Mean daily maximum temperature',
        'unit' => '°C',
        'example' => '26',
        'min' => 0.70,
        'max' => 32.89
    ],
    'Mean_Daily_Min_Temperature_C' => [
        'label' => 'Mean Daily Min Temperature (°C)',
        'name' => 'Mean daily minimum temperature',
        'unit' => '°C',
        'example' => '14',
        'min' => -11.01,
        'max' => 21.48
    ],
    'Relative_Humidity_pct' => [
        'label' => 'Relative Humidity (%)',
        'name' => 'Relative humidity',
        'unit' => '%',
        'example' => '65',
        'min' => 46.37,
        'max' => 82.33
    ],
    'Specific_Humidity_g_kg' => [
        'label' => 'Specific Humidity (g/kg)',
        'name' => 'Specific humidity',
        'unit' => 'g/kg',
        'example' => '10',
        'min' => 3.93,
        'max' => 14.81
    ],
    'Surface_Pressure_kPa' => [
        'label' => 'Surface Pressure (kPa)',
        'name' => 'Surface pressure',
        'unit' => 'kPa',
        'example' => '85',
        'min' => 55.51,
        'max' => 99.36
    ],
    'Wind_Speed_m_s' => [
        'label' => 'Wind Speed (m/s)',
        'name' => 'Wind speed',
        'unit' => 'm/s',
        'example' => '2.4',
        'min' => 1.81,
        'max' => 3.28
    ],
    'Solar_Radiation_kWh_m2_day' => [
        'label' => 'Solar Radiation (kWh/m²/day)',
        'name' => 'Solar radiation',
        'unit' => 'kWh/m²/day',
        'example' => '5',
        'min' => 3.84,
        'max' => 6.02
    ],
    'Latitude' => [
        'label' => 'Latitude (°N)',
        'name' => 'District latitude',
        'unit' => '°N',
        'example' => '27.7',
        'min' => 26.58,
        'max' => 30.03
    ],
    'Longitude' => [
        'label' => 'Longitude (°E)',
        'name' => 'District longitude',
        'unit' => '°E',
        'example' => '85.3',
        'min' => 80.32,
        'max' => 87.92
    ],
];
