# Nepal-Focused Crop Yield Prediction & Agricultural Information System (NCYPS)

An academic research web application providing district-level crop yield prediction and agricultural data analysis for Nepal. The application bridges a trained **Random Forest machine learning model** with a secure **PHP/MySQL web application**, offering a farmer-friendly map workflow, historical trend visualisations and source-attributed agricultural information.

---

## 🎨 Design System & Palette

The user interface follows an editorial, research-institutional design system (HEX ONLY, no bright colors, no gradients, no emojis):

- **Primary Brand**: `#213D30` (Deep forest green)
- **Primary Dark**: `#142B21` (Navigation and footer)
- **Secondary Accent**: `#B85C38` (Terracotta)
- **Background**: `#F3F1E8` (Warm field paper)
- **Surface**: `#FFFDF8` (Warm white)
- **Border**: `#D7D4C8` (Stone grey)
- **Typography**: `Source Serif 4` for editorial headings and `Inter` for interface text

---

## 📂 Project Folder Structure

```text
crop/
├── prediction_service.py      # Canonical launcher for the packaged Flask service
├── flask_mode/                # Flask API and the trained Joblib pipeline
├── requirements.txt           # Python dependencies
├── README.md                  # Project documentation & setup guide
├── includes/                  # PHP core utilities & database helpers
│   ├── config.php             # Database credentials & Flask URL configuration
│   ├── db.php                 # PDO MySQL connection manager
│   ├── auth.php               # Session security, password_verify(), CSRF tokens
│   ├── helpers.php            # XSS sanitisation (e()), output formatting
│   ├── validate.php           # Server-side feature range & input validation
│   ├── reference_data.php     # Reads normalized province, district and crop lookups
│   ├── provinces.php          # 7 exact training province names
│   ├── districts.php          # 77 exact training district names
│   ├── ranges.php             # Feature training range bounds
│   ├── flask_client.php       # cURL REST client to Flask prediction service
│   ├── header.php             # Shared header navigation bar
│   └── footer.php             # Shared footer with NCYPS mark & attributions
├── sql/                       # Database DDL & seed scripts
│   ├── schema.sql             # CREATE TABLE DDL (users, predictions, posts, etc.)
│   ├── seed.sql               # Seed data (77 districts, 6 crops, admin user, historical dataset)
│   ├── migrate_to_normalized.sql # One-time migration for an existing database
│   └── import_history.php     # Imports cleaned model-ready history into MySQL
├── public/                    # User-facing web document root
│   ├── index.php              # Editorial Homepage / Overview
│   ├── predict.php            # Farmer-friendly map and prediction workflow
│   ├── history.php            # Historical trends (Chart.js + CSV Export)
│   ├── info.php               # Source-attributed information hub
│   ├── about.php              # Project methodology, dataset provenance, team
│   ├── proxy_averages.php     # AJAX proxy endpoint for district feature averages
│   ├── admin/                 # Secured admin portal
│   │   ├── login.php          # Admin login with CSRF token & rate limiting
│   │   ├── dashboard.php      # Admin control center & audit logs
│   │   ├── posts.php          # Manage information posts (CRUD)
│   │   └── logout.php         # Destroy session
│   └── assets/
│       ├── css/style.css      # Editorial stylesheet (HEX only, no gradients)
│       ├── js/app.js          # Map, automatic profiles, land conversion and validation
│       ├── js/charts.js       # Muted Chart.js multi-axis visualisations
│       ├── data/nepal-districts.geojson # Canonical 77-district boundary layer
│       └── vendor/leaflet/    # Locally bundled Leaflet 1.9.4 assets and licence
└── tests/                     # Test suite
    ├── test_flask_api.py      # Python unit tests (pytest)
    ├── test_flask_service_unittest.py # Standard library unittest
    ├── tests_php/             # PHP test scripts
    │   └── test_validation.php
    └── README.md              # Instructions for running tests
```

---

## 🚀 Setup & Installation Guide

### Step 1: Database Import
For a fresh installation, import the normalized schema and seed files:

Import the SQL files into phpMyAdmin or MySQL CLI:
```sql
SOURCE e:/softwares_/xammp/htdocs/crop/sql/schema.sql;
SOURCE e:/softwares_/xammp/htdocs/crop/sql/seed.sql;
```

For an existing installation created with the original text-based schema, first
export a database backup, then run:

```sql
SOURCE e:/softwares_/xammp/htdocs/crop/sql/migrate_to_normalized.sql;
```

The normalized schema stores provinces, districts and crops once. Predictions
and historical yield records reference district and crop identifiers through
foreign keys; province is obtained through each district. Information posts
retain their existing optional author relationship to users.

Replace the small demonstration history seed with the cleaned model-ready dataset:

```bash
php sql/import_history.php "path/to/model_dataset_audit_v1.csv"
```

The importer accepts only rows marked `included`, checks required columns, removes duplicate district-crop-year records and replaces the history table in one database transaction.

### Step 2: Launch Python Flask REST Service
```bash
pip install -r requirements.txt
python prediction_service.py
```
*(Runs on `http://127.0.0.1:5000`)*

The root launcher imports the single implementation in `flask_mode`, which loads the packaged Random Forest pipeline. The service checks the supplied sibling `model-traning/training/model_dataset_audit_v1.csv` for district feature profiles. Set `DATASET_PATH` only when using a different layout. Prediction stops with a clear error if the trained model is unavailable; no substitute calculation is returned.

### Step 3: Access Web Application
Open your browser to:
```text
http://localhost/crop/public/index.php
```

### Farmer-friendly prediction flow

The normal workflow asks for a location, crop and land size. A user can click a district on the map, use device location with permission, or use the province and district lists. The exact clicked/GPS point is not stored: it identifies the district, while the model receives the district-crop historical coordinates and climate profile used by the training method.

The user's land size is converted to hectares only to estimate total production. It does **not** replace the model's district cultivated-area feature. Supported conversions follow published Nepal agricultural area conversions: hectare, ropani, aana, bigha and kattha.

Climate choices are:

- `typical`: mean of the approved completed district-crop records;
- `recent`: the most recent approved completed record;
- `custom`: the same historical profile as a starting point, editable under Advanced options.

These are historical scenarios, not a live weather forecast. Crop choices are limited to combinations present in the imported historical table.

---

## Administrator setup

No administrator password or reusable account is shipped. See [SECURITY.md](SECURITY.md) for private administrator provisioning. Existing installations must rotate any previously published password.


## 📊 Data Source Attribution

- **MoALD**: Ministry of Agriculture and Livestock Development, Nepal
- **NASA POWER**: Prediction Of Worldwide Energy Resources (Meteorological & Solar Radiation)
- **Open Knowledge Nepal LocalBoundaries**: 77-district map layer, CC BY 4.0
- **OpenStreetMap contributors**: interactive background map, used under the tile usage and attribution requirements
