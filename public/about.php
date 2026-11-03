<?php
/**
 * Project Information & Overview Page
 * Clean, accessible explanation of the project without heavy technical jargon.
 */

declare(strict_types=1);

$currentPage = 'about';
$pageTitle = 'About Project';

require_once __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="app-container">
        
        <div class="mb-4">
            <span class="section-label">Project Background</span>
            <h2>About the Nepal Crop Yield Prediction System</h2>
            <p class="text-muted">
                An initiative designed to make crop yield estimation and agricultural climate data accessible across Nepal.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                
                <!-- Project Purpose & Context -->
                <section class="card-custom">
                    <span class="section-label">01. Our Purpose</span>
                    <h3>What is this Project?</h3>
                    <p class="text-muted">
                        Agriculture is the backbone of Nepal's economy and supports the livelihoods of millions. However, variations in seasonal rainfall, temperature, and regional topography directly affect crop output each year.
                    </p>
                    <p class="text-muted mb-0">
                        The <strong>Nepal Crop Yield Prediction System (NCYPS)</strong> is an academic web application created to help students, researchers, and agriculture enthusiasts explore how environmental conditions impact farming outcomes across Nepal's diverse geography.
                    </p>
                </section>

                <!-- Key Goals & Features -->
                <section class="card-custom">
                    <span class="section-label">02. Goals &amp; Features</span>
                    <h3>What Does the System Do?</h3>
                    <ul class="text-muted mb-0" style="padding-left: 1.2rem;">
                        <li class="mb-2"><strong>District-Level Forecasting:</strong> Choose a district using a map, device location or accessible lists, then estimate potential yield in kilograms per hectare.</li>
                        <li class="mb-2"><strong>Crops Supported:</strong> Focuses on six essential Nepalese staple crops — Paddy (Rice), Wheat, Maize, Millet, Barley, and Buckwheat.</li>
                        <li class="mb-2"><strong>Historical Trend Analysis:</strong> View past yield performance alongside annual rainfall and temperature changes over time.</li>
                        <li class="mb-2"><strong>Farmer-Friendly Inputs:</strong> Normal users provide only a location, crop and land size. The system supplies the district-level coordinates and historical climate profile, while researchers can inspect Advanced options.</li>
                        <li class="mb-0"><strong>Agricultural Information:</strong> Access curated summaries attributed to official agricultural reports and meteorological research.</li>
                    </ul>
                </section>

                <!-- Data Sources -->
                <section class="card-custom">
                    <span class="section-label">03. Information Sources</span>
                    <h3>Where Does the Information Come From?</h3>
                    <p class="text-muted mb-3">
                        To ensure reliable estimates, the system combines historical farming data with satellite weather observations provided by trusted organizations:
                    </p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light h-100">
                                <strong>MoALD</strong>
                                <p class="small text-muted mb-0 mt-1">Ministry of Agriculture and Livestock Development, Government of Nepal (District crop production records).</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light h-100">
                                <strong>NASA POWER</strong>
                                <p class="small text-muted mb-0 mt-1">Global satellite meteorological reanalysis providing temperature, rainfall, and solar radiation data.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light h-100">
                                <strong>ICIMOD</strong>
                                <p class="small text-muted mb-0 mt-1">International Centre for Integrated Mountain Development (Geospatial and elevation data).</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Important Disclaimer -->
                <section class="card-custom">
                    <span class="section-label">04. Usage Disclaimer</span>
                    <h3>Important Notice for Users</h3>
                    <div class="alert-custom alert-custom-warning mb-0">
                        <strong>Academic Project Scope:</strong> This website is an undergraduate Computer Science research project. All yield figures represent statistical estimations and should be viewed as general guidance rather than a guarantee of actual farm harvest or commercial agricultural advice.
                    </div>
                </section>

            </div>

            <!-- Right Sidebar Column -->
            <div class="col-lg-4">
                
                <!-- Quick Overview Card -->
                <div class="card-custom mb-4">
                    <span class="section-label">Project Summary</span>
                    <h4 class="mb-3">System Coverage</h4>
                    <ul class="list-unstyled small text-muted mb-0">
                        <li class="mb-2 pb-2 border-bottom"><strong>Geographic Scope:</strong> All 77 Districts across Nepal's 7 Provinces</li>
                        <li class="mb-2 pb-2 border-bottom"><strong>Crops Covered:</strong> Paddy, Wheat, Maize, Millet, Barley, Buckwheat</li>
                        <li class="mb-2 pb-2 border-bottom"><strong>Primary Output:</strong> Estimated Yield in kg/ha</li>
                        <li class="mb-0"><strong>Target Audience:</strong> Students, Researchers &amp; Agriculture Analysts</li>
                    </ul>
                </div>

                <!-- Academic Info -->
                <div class="card-custom mb-4">
                    <span class="section-label">Project Context</span>
                    <h4 class="mb-2">Academic Development</h4>
                    <p class="text-muted small mb-0">
                        Developed as a final-year Computer Science undergraduate thesis project focusing on data analysis for agriculture in Nepal.
                    </p>
                </div>

                <!-- Model transparency -->
                <div class="card-custom">
                    <span class="section-label">Model Transparency</span>
                    <h4 class="mb-2">Review the Evidence</h4>
                    <p class="text-muted small mb-3">
                        Compare recorded seasons before interpreting a model estimate. Predictions are decision-support outputs, not guaranteed farm results.
                    </p>
                    <a href="history.php" class="btn btn-secondary-custom btn-sm w-100">Explore Historical Evidence &rarr;</a>
                </div>

            </div>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
