<?php
/**
 * Reads canonical province, district and crop values from the normalized schema.
 */

declare(strict_types=1);

function get_reference_catalog(PDO $pdo): array
{
    $districtRows = $pdo->query("
        SELECT
            p.name AS province_name,
            d.name AS district_name,
            d.latitude,
            d.longitude
        FROM districts d
        INNER JOIN provinces p ON p.id = d.province_id
        ORDER BY p.id, d.name
    ")->fetchAll();

    $districtsByProvince = [];
    $districtCentres = [];
    foreach ($districtRows as $row) {
        $districtsByProvince[$row['province_name']][] = $row['district_name'];
        $districtCentres[$row['district_name']] = [
            'province' => $row['province_name'],
            'latitude' => (float)$row['latitude'],
            'longitude' => (float)$row['longitude'],
        ];
    }

    $cropRows = $pdo->query("
        SELECT id, name, display_name
        FROM crops
        ORDER BY name
    ")->fetchAll();

    $availabilityRows = $pdo->query("
        SELECT DISTINCT d.name AS district_name, c.name AS crop_name
        FROM historical_yields h
        INNER JOIN districts d ON d.id = h.district_id
        INNER JOIN crops c ON c.id = h.crop_id
        ORDER BY d.name, c.name
    ")->fetchAll();

    $cropsByDistrict = [];
    foreach ($availabilityRows as $row) {
        $cropsByDistrict[$row['district_name']][] = $row['crop_name'];
    }

    return [
        'provinces' => array_keys($districtsByProvince),
        'districts_by_province' => $districtsByProvince,
        'district_centres' => $districtCentres,
        'crops' => $cropRows,
        'crops_by_district' => $cropsByDistrict,
    ];
}

function resolve_reference_ids(PDO $pdo, string $province, string $district, string $crop): ?array
{
    $stmt = $pdo->prepare("
        SELECT d.id AS district_id, c.id AS crop_id
        FROM districts d
        INNER JOIN provinces p ON p.id = d.province_id
        CROSS JOIN crops c
        WHERE p.name = :province
          AND d.name = :district
          AND c.name = :crop
        LIMIT 1
    ");
    $stmt->execute([
        ':province' => $province,
        ':district' => $district,
        ':crop' => $crop,
    ]);

    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }

    return [
        'district_id' => (int)$row['district_id'],
        'crop_id' => (int)$row['crop_id'],
    ];
}
