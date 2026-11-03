<?php
/**
 * Redesigned Editorial Historical Trends Page
 */

declare(strict_types=1);

$currentPage = 'history';
$pageTitle = 'Historical Trends';

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

$pdo = get_db_connection();

$coverageRows = $pdo->query("
    SELECT d.name AS district_name, c.name AS crop_name, COUNT(*) AS record_count
    FROM historical_yields h
    INNER JOIN districts d ON d.id = h.district_id
    INNER JOIN crops c ON c.id = h.crop_id
    GROUP BY d.id, d.name, c.id, c.name
    ORDER BY d.name, c.name
")->fetchAll();

$availability = [];
foreach ($coverageRows as $coverageRow) {
    $availability[$coverageRow['district_name']][$coverageRow['crop_name']] = (int)$coverageRow['record_count'];
}

$districts = array_keys($availability);
$requestedDistrict = trim((string)($_GET['district'] ?? 'Kathmandu'));
$selectedDistrict = isset($availability[$requestedDistrict])
    ? $requestedDistrict
    : ($districts[0] ?? 'Kathmandu');

$availableCrops = array_keys($availability[$selectedDistrict] ?? []);
$requestedCrop = trim((string)($_GET['crop'] ?? 'Paddy'));
$selectedCrop = in_array($requestedCrop, $availableCrops, true)
    ? $requestedCrop
    : ($availableCrops[0] ?? 'Paddy');

$stmt = $pdo->prepare("
    SELECT h.year, h.yield_kg_ha, h.rainfall_mm, h.avg_temp_c
    FROM historical_yields h
    INNER JOIN districts d ON d.id = h.district_id
    INNER JOIN crops c ON c.id = h.crop_id
    WHERE d.name = :district AND c.name = :crop
    ORDER BY h.year ASC
");
$stmt->execute([':district' => $selectedDistrict, ':crop' => $selectedCrop]);
$historyRows = $stmt->fetchAll();

$years = [];
$yields = [];
$rainfalls = [];
$temps = [];

foreach ($historyRows as $row) {
    $years[] = (int)$row['year'];
    $yields[] = (float)$row['yield_kg_ha'];
    $rainfalls[] = (float)$row['rainfall_mm'];
    $temps[] = (float)$row['avg_temp_c'];
}

$recordCount = count($historyRows);
$averageYield = $recordCount ? array_sum($yields) / $recordCount : 0;
$averageRainfall = $recordCount ? array_sum($rainfalls) / $recordCount : 0;
$averageTemperature = $recordCount ? array_sum($temps) / $recordCount : 0;
$yieldChange = ($recordCount > 1 && $yields[0] > 0)
    ? (($yields[$recordCount - 1] - $yields[0]) / $yields[0]) * 100
    : null;

require_once __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="app-container">
        
        <header class="page-intro history-intro">
            <div>
                <span class="section-label">Historical evidence / 2015-2024</span>
                <h1>Crop and climate<br><em>through time</em></h1>
                <p>Compare recorded yield with annual rainfall and average temperature for one district and crop. Filters only show combinations with available evidence.</p>
            </div>
            <?php if (!empty($historyRows)): ?>
                <button type="button" id="exportCsvBtn" class="btn btn-secondary-custom">Export selected data</button>
            <?php endif; ?>
        </header>

        <div class="filter-panel">
            <div class="filter-heading">
                <span class="filter-index">01</span>
                <div><strong>Choose an evidence series</strong><small><?= number_format(count($coverageRows)) ?> district-crop series available</small></div>
            </div>
            <form method="GET" action="history.php" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label" for="historyDistrict">District</label>
                    <select id="historyDistrict" name="district" class="form-select">
                        <?php foreach ($districts as $d): ?>
                            <option value="<?= htmlspecialchars($d) ?>" <?= $selectedDistrict === $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="historyCrop">Crop</label>
                    <select id="historyCrop" name="crop" class="form-select">
                        <?php foreach ($availableCrops as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= $selectedCrop === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary-custom w-100">Update Charts</button>
                </div>
            </form>
        </div>

        <?php if (empty($years)): ?>
            <div class="empty-state my-4">
                <span class="section-label">No historical data loaded</span>
                <h2>The chart archive is empty</h2>
                <p>Import the cleaned combined crop-and-climate dataset before using this page.</p>
            </div>
        <?php else: ?>
            <section class="metric-band" aria-label="Selected series summary">
                <div><span>Recorded seasons</span><strong><?= number_format($recordCount) ?></strong></div>
                <div><span>Average yield</span><strong><?= number_format($averageYield, 0) ?><small> kg/ha</small></strong></div>
                <div><span>Average rainfall</span><strong><?= number_format($averageRainfall, 0) ?><small> mm</small></strong></div>
                <div><span>Mean temperature</span><strong><?= number_format($averageTemperature, 1) ?><small> °C</small></strong></div>
                <div><span>First-to-last yield</span><strong><?= $yieldChange === null ? 'n/a' : (($yieldChange >= 0 ? '+' : '') . number_format($yieldChange, 1) . '%') ?></strong></div>
            </section>

            <div class="series-heading">
                <div>
                    <span class="section-label">02 / Selected series</span>
                    <h2><?= htmlspecialchars($selectedCrop) ?> in <?= htmlspecialchars($selectedDistrict) ?></h2>
                </div>
                <span class="series-period"><?= min($years) ?>—<?= max($years) ?></span>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-xl-7">
                    <div class="chart-panel chart-panel-primary h-100">
                        <div class="chart-heading"><strong>Yield progression</strong><span>kg per hectare</span></div>
                        <div class="chart-frame">
                            <canvas id="yieldTrendCanvas"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-xl-5">
                    <div class="chart-panel h-100">
                        <div class="chart-heading"><strong>Climate context</strong><span>rainfall and mean temperature</span></div>
                        <div class="chart-frame">
                            <canvas id="climateTrendCanvas"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <section class="data-table-panel">
                <div class="series-heading compact">
                    <div><span class="section-label">03 / Underlying records</span><h2>Year-by-year values</h2></div>
                    <span class="series-period">Source-aligned fields</span>
                </div>
                <div class="table-responsive">
                    <table id="historyTable" class="table-custom">
                        <thead>
                            <tr>
                                <th>Year</th>
                                <th>District</th>
                                <th>Crop</th>
                                <th>Yield (kg/ha)</th>
                                <th>Precipitation (mm)</th>
                                <th>Avg Temperature (°C)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historyRows as $r): ?>
                                <tr>
                                    <td class="monospace"><?= htmlspecialchars((string)$r['year']) ?></td>
                                    <td><?= htmlspecialchars($selectedDistrict) ?></td>
                                    <td><?= htmlspecialchars($selectedCrop) ?></td>
                                    <td class="fw-semibold text-success"><?= number_format((float)$r['yield_kg_ha'], 2) ?> kg/ha</td>
                                    <td><?= number_format((float)$r['rainfall_mm'], 1) ?> mm</td>
                                    <td><?= number_format((float)$r['avg_temp_c'], 1) ?> °C</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/charts.js"></script>
<script>
    window.historyAvailability = <?= json_encode($availability, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    document.addEventListener('DOMContentLoaded', function () {
        const districtSelect = document.getElementById('historyDistrict');
        const cropSelect = document.getElementById('historyCrop');
        if (!districtSelect || !cropSelect) return;

        districtSelect.addEventListener('change', function () {
            const crops = Object.keys(window.historyAvailability[this.value] || {});
            cropSelect.innerHTML = '';
            crops.forEach(function (crop) {
                const option = document.createElement('option');
                option.value = crop;
                option.textContent = crop;
                cropSelect.appendChild(option);
            });
        });
    });
</script>
<?php if (!empty($years)): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        renderYieldTrendChart(
            'yieldTrendCanvas',
            <?= json_encode($years) ?>,
            <?= json_encode($yields) ?>
        );
        renderClimateTrendChart(
            'climateTrendCanvas',
            <?= json_encode($years) ?>,
            <?= json_encode($rainfalls) ?>,
            <?= json_encode($temps) ?>
        );

        const exportBtn = document.getElementById("exportCsvBtn");
        if (exportBtn) {
            exportBtn.addEventListener("click", function() {
                let csv = "Year,District,Crop,Yield_kg_ha,Rainfall_mm,Avg_Temp_C\n";
                const rows = <?= json_encode($historyRows) ?>;
                rows.forEach(r => {
                    csv += `${r.year},"${<?= json_encode($selectedDistrict) ?>}","${<?= json_encode($selectedCrop) ?>}",${r.yield_kg_ha},${r.rainfall_mm},${r.avg_temp_c}\n`;
                });
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement("a");
                link.href = URL.createObjectURL(blob);
                link.setAttribute("download", `history_${<?= json_encode($selectedDistrict) ?>}_${<?= json_encode($selectedCrop) ?>}.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        }
    });
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
