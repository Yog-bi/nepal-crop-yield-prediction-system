# Nepal Crop Yield Prediction System - Test Suite Documentation

This directory contains automated unit and integration tests for both the Python Flask REST API and the PHP Web Application logic.

---

## 1. Running Python Flask Service Tests

Ensure dependencies are installed and run `pytest`:

```bash
pip install -r requirements.txt
pytest tests/test_flask_api.py
```

### Covered Scenarios:
- `test_health_check_endpoint`: Verifies `/` GET status and JSON metadata.
- `test_predict_missing_content_type`: Verifies HTTP 400 when missing `application/json`.
- `test_predict_missing_fields`: Verifies validation when any of the 15 required features are omitted.
- `test_predict_invalid_numerical_range`: Verifies validation for invalid area/humidity ranges.
- `test_valid_prediction`: Verifies complete prediction payload returns HTTP 200 with float yield in `kg/ha`.
- District profile tests verify typical and latest completed-period inputs, source-period metadata, and fail-closed behaviour for unavailable selections.

---

## 2. Running PHP Validation Tests

Run using PHP CLI:

```bash
php tests/tests_php/test_validation.php
```

### Covered Scenarios:
- `e()` XSS escaping verification.
- `generate_csrf_token()` and `verify_csrf_token()` verification.
- `format_yield()` number formatting.
- `password_hash()` and `password_verify()` bcrypt authentication logic.
- Official local land-unit conversions used by the farmer-facing production estimate.
- Separation of farmer parcel size from the district cultivated-area model feature.
- Rejection of district-crop combinations without historical evidence.
