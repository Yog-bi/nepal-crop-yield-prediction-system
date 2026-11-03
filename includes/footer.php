<?php
/**
 * Shared Footer Template
 * Clean, two-row footer with NCYPS mark and data source attributions.
 */

declare(strict_types=1);

$config = require __DIR__ . '/config.php';
?>
    <footer>
        <div class="app-container">
            <div class="footer-kicker">Research system / Nepal / 2015-2024</div>
            <div class="row g-4 footer-main">
                <!-- Column 1: Brand & Mark -->
                <div class="col-md-5">
                    <div class="footer-title">
                        Nepal Crop Yield Prediction System
                    </div>
                    <p class="text-muted small mb-0" style="max-width: 380px;">
                        A district-level research tool for exploring crop, climate and model evidence. Predictions support investigation; they are not farming guarantees.
                    </p>
                </div>

                <!-- Column 2: Quick Links -->
                <div class="col-md-3">
                    <div class="footer-title">Quick Navigation</div>
                    <ul class="footer-links small">
                        <li><a href="predict.php">Yield Predictor</a></li>
                        <li><a href="history.php">Historical Trends</a></li>
                        <li><a href="info.php">Agricultural Information</a></li>
                        <li><a href="about.php">About Project</a></li>
                    </ul>
                </div>

                <!-- Column 3: Data Sources -->
                <div class="col-md-4">
                    <div class="footer-title">Attributed Data Sources</div>
                    <ul class="footer-links small">
                        <li><a href="https://moald.gov.np/" target="_blank" rel="noopener noreferrer">MoALD — Ministry of Agriculture &amp; Livestock Development</a></li>
                        <li><a href="https://power.larc.nasa.gov/" target="_blank" rel="noopener noreferrer">NASA POWER — Meteorological Reanalysis Dataset</a></li>
                        <li><a href="https://localboundries.oknp.org/" target="_blank" rel="noopener noreferrer">Open Knowledge Nepal — Administrative Boundaries</a></li>
                    </ul>
                </div>
            </div>

            <!-- Footer Bottom Row -->
            <div class="footer-bottom">
                <div>
                    &copy; <?= date('Y') ?> <?= htmlspecialchars($config['app_name']) ?>. Academic Research Project.
                </div>
                <div class="text-muted">
                    Last Updated: <?= date('F Y') ?>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
