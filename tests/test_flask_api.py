"""
Python Unit Test Suite for Flask Crop Yield Prediction REST API
Run with: pytest tests/test_flask_api.py
"""

import os
import sys
import pytest

# Ensure root directory is accessible for imports during testing
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))
from prediction_service import app


@pytest.fixture
def client():
    app.config['TESTING'] = True
    with app.test_client() as client:
        yield client


def test_health_check_endpoint(client):
    """Test root health check GET endpoint."""
    response = client.get('/')
    assert response.status_code == 200
    json_data = response.get_json()
    assert json_data['status'] == 'healthy'
    assert 'model_loaded' in json_data


def test_predict_missing_content_type(client):
    """Test POST request without JSON content-type returns HTTP 400."""
    response = client.post('/predict', data="not json")
    assert response.status_code == 400
    json_data = response.get_json()
    assert json_data['success'] is False


def test_typical_district_profile_uses_approved_history(client):
    """Typical mode returns real historical means and provenance metadata."""
    response = client.get('/district_averages', query_string={
        'district': 'Bhaktapur',
        'crop': 'Barley',
        'scenario': 'typical',
    })
    assert response.status_code == 200
    data = response.get_json()
    assert data['success'] is True
    assert data['scenario'] == 'typical'
    assert data['record_count'] > 0
    assert data['source_period'] == 'Average of available completed periods'
    assert set(data['averages']) >= {'Area_ha', 'Rainfall_mm_total', 'Latitude', 'Longitude', 'Province'}


def test_recent_district_profile_identifies_source_period(client):
    """Recent mode returns one latest completed period, not invented defaults."""
    response = client.get('/district_averages', query_string={
        'district': 'Bhaktapur',
        'crop': 'Barley',
        'scenario': 'recent',
    })
    assert response.status_code == 200
    data = response.get_json()
    assert data['success'] is True
    assert data['scenario'] == 'recent'
    assert data['record_count'] > 0
    assert data['source_period']


def test_district_profile_rejects_unknown_or_unavailable_selection(client):
    """The service fails clearly instead of fabricating replacement values."""
    response = client.get('/district_averages', query_string={
        'district': 'Not a district',
        'crop': 'Barley',
        'scenario': 'typical',
    })
    assert response.status_code == 404
    assert response.get_json()['success'] is False


def test_predict_missing_fields(client):
    """Test prediction request with missing fields returns HTTP 400."""
    incomplete_payload = {
        "Area_ha": 1.5,
        "Province": "Bagmati Province",
        "District": "Bhaktapur"
        # Missing remaining features
    }
    response = client.post('/predict', json=incomplete_payload)
    assert response.status_code == 400
    json_data = response.get_json()
    assert json_data['success'] is False
    assert "Missing required fields" in json_data['error']


def test_predict_invalid_numerical_range(client):
    """Test prediction request with negative area or out-of-range humidity."""
    invalid_payload = {
        "Area_ha": -5.0,  # Invalid negative area
        "Rainfall_mm_total": 1200.0,
        "Avg_Temperature_C": 22.0,
        "Mean_Daily_Max_Temperature_C": 28.0,
        "Mean_Daily_Min_Temperature_C": 16.0,
        "Relative_Humidity_pct": 75.0,
        "Specific_Humidity_g_kg": 12.0,
        "Surface_Pressure_kPa": 85.0,
        "Wind_Speed_m_s": 2.1,
        "Solar_Radiation_kWh_m2_day": 5.2,
        "Latitude": 27.53,
        "Longitude": 84.45,
        "Province": "Bagmati Province",
        "District": "Bhaktapur",
        "Crop": "Paddy"
    }
    response = client.post('/predict', json=invalid_payload)
    assert response.status_code == 400
    json_data = response.get_json()
    assert json_data['success'] is False


def test_valid_prediction(client):
    """Test valid complete prediction payload returns HTTP 200 and yield prediction."""
    valid_payload = {
        "Area_ha": 46.0,
        "Rainfall_mm_total": 1205.39,
        "Avg_Temperature_C": 19.506421,
        "Mean_Daily_Max_Temperature_C": 24.574563,
        "Mean_Daily_Min_Temperature_C": 15.756694,
        "Relative_Humidity_pct": 59.639672,
        "Specific_Humidity_g_kg": 10.164891,
        "Surface_Pressure_kPa": 87.329672,
        "Wind_Speed_m_s": 2.208197,
        "Solar_Radiation_kWh_m2_day": 4.709586,
        "Latitude": 27.677426,
        "Longitude": 85.443076,
        "Province": "Bagmati Province",
        "District": "Bhaktapur",
        "Crop": "Barley"
    }
    response = client.post('/predict', json=valid_payload)
    assert response.status_code == 200
    json_data = response.get_json()
    assert json_data['success'] is True
    assert isinstance(json_data['prediction'], float)
    assert json_data['prediction'] > 0
    assert json_data['unit'] == 'kg/ha'
