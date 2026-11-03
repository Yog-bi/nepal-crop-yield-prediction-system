<?php
/**
 * Redesigned Editorial Homepage
 * Clear, project-focused overview without heavy technical jargon.
 */

declare(strict_types=1);

$currentPage = 'home';
$pageTitle = 'Home';

require_once __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="app-container">
        
        <section class="home-hero">
            <div class="hero-copy">
                <span class="section-label">Nepal agricultural data observatory</span>
                <h1>Read the climate.<br><em>Estimate the harvest.</em></h1>
                <p class="hero-intro">
                    Choose a district on the map, select a crop and enter your land size. The system supplies the district climate profile and uses a tested Random Forest model to estimate yield for six staple crops.
                </p>
                <div class="hero-actions">
                    <a href="predict.php" class="btn btn-primary-custom">Start a prediction <span aria-hidden="true">&#8599;</span></a>
                    <a href="history.php" class="text-link">Explore historical evidence <span aria-hidden="true">&#8594;</span></a>
                </div>
                <p class="hero-note">Decision-support for research and learning. Not a guarantee of farm-level output.</p>
            </div>
            <aside class="hero-register" aria-label="Dataset coverage">
                <div class="register-heading">
                    <span>Dataset register</span>
                    <span class="status-mark"><i></i> Model online</span>
                </div>
                <dl class="coverage-grid">
                    <div><dt>77</dt><dd>districts</dd></div>
                    <div><dt>6</dt><dd>cereal crops</dd></div>
                    <div><dt>2015-24</dt><dd>study period</dd></div>
                    <div><dt>15</dt><dd>model inputs</dd></div>
                </dl>
                <div class="register-foot">
                    <span>Selected model</span>
                    <strong>Random Forest</strong>
                    <span>Test R<sup>2</sup></span>
                    <strong>0.8228</strong>
                </div>
            </aside>
        </section>

        <!-- Three-Column System Overview -->
        <section class="feature-section">
            <div class="section-heading-row">
                <div>
                    <span class="section-label">Three ways into the evidence</span>
                    <h2>From observation to estimate</h2>
                </div>
                <p>Each part of the system answers a different question and keeps the underlying evidence visible.</p>
            </div>
            <div class="row g-0 feature-row">
                <div class="col-md-4">
                    <a class="feature-entry" href="predict.php">
                        <span class="feature-number">01</span>
                        <h3>Estimate yield</h3>
                        <p>Choose a place, crop and land size. Technical climate and location values are supplied automatically.</p>
                        <span class="feature-link">Open predictor &#8594;</span>
                    </a>
                </div>
                <div class="col-md-4">
                    <a class="feature-entry" href="history.php">
                        <span class="feature-number">02</span>
                        <h3>Compare seasons</h3>
                        <p>Read yield beside rainfall and temperature across the available fiscal-year record.</p>
                        <span class="feature-link">View trends &#8594;</span>
                    </a>
                </div>
                <div class="col-md-4">
                    <a class="feature-entry" href="info.php">
                        <span class="feature-number">03</span>
                        <h3>Trace the sources</h3>
                        <p>Read contextual notes with links to the organisations behind the crop and climate evidence.</p>
                        <span class="feature-link">Browse information &#8594;</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- About Project Summary -->
        <section class="method-strip">
            <div>
                <span class="section-label">How the estimate is formed</span>
                <h2>Crop records meet climate observations</h2>
            </div>
            <ol class="method-steps">
                <li><span>01</span><strong>Choose a place</strong><small>Map, device location or district list</small></li>
                <li><span>02</span><strong>Add the crop</strong><small>Crop and your land size</small></li>
                <li><span>03</span><strong>Read the result</strong><small>Yield, production and historical context</small></li>
            </ol>
            <a href="about.php" class="text-link">Review the methodology and limitations &#8594;</a>
        </section>

    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
