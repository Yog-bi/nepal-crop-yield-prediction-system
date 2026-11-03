import os
import sys
import unittest

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))
from prediction_service import app



class TestFlaskPredictionAPI(unittest.TestCase):

    def setUp(self):
        app.config['TESTING'] = True
        self.client = app.test_client()

    def test_health_check(self):
        response = self.client.get('/')
        self.assertEqual(response.status_code, 200)
        data = response.get_json()
        self.assertEqual(data['status'], 'healthy')

    def test_missing_content_type(self):
        response = self.client.post('/predict', data="not json")
        self.assertEqual(response.status_code, 400)

    def test_missing_required_fields(self):
        response = self.client.post('/predict', json={"Area_ha": 1.5, "Crop": "Paddy"})
        self.assertEqual(response.status_code, 400)
        data = response.get_json()
        self.assertIn("Missing required fields", data['error'])

    def test_invalid_area(self):
        payload = {
            "Area_ha": -10.0,
            "Rainfall_mm_total": 1400.0,
            "Avg_Temperature_C": 23.5,
            "Mean_Daily_Max_Temperature_C": 28.5,
            "Mean_Daily_Min_Temperature_C": 17.0,
            "Relative_Humidity_pct": 75.0,
            "Specific_Humidity_g_kg": 13.0,
            "Surface_Pressure_kPa": 85.0,
            "Wind_Speed_m_s": 2.2,
            "Solar_Radiation_kWh_m2_day": 5.4,
            "Latitude": 27.53,
            "Longitude": 84.45,
            "Province": "Bagmati",
            "District": "Chitwan",
            "Crop": "Paddy"
        }
        response = self.client.post('/predict', json=payload)
        self.assertEqual(response.status_code, 400)

    def test_valid_prediction(self):
        payload = {
            "Area_ha": 1.5,
            "Rainfall_mm_total": 1400.0,
            "Avg_Temperature_C": 23.5,
            "Mean_Daily_Max_Temperature_C": 28.5,
            "Mean_Daily_Min_Temperature_C": 17.0,
            "Relative_Humidity_pct": 75.0,
            "Specific_Humidity_g_kg": 13.0,
            "Surface_Pressure_kPa": 85.0,
            "Wind_Speed_m_s": 2.2,
            "Solar_Radiation_kWh_m2_day": 5.4,
            "Latitude": 27.53,
            "Longitude": 84.45,
            "Province": "Bagmati",
            "District": "Chitwan",
            "Crop": "Paddy"
        }
        response = self.client.post('/predict', json=payload)
        self.assertEqual(response.status_code, 200)
        data = response.get_json()
        self.assertTrue(data['success'])
        self.assertGreater(data['prediction'], 0)
        self.assertEqual(data['unit'], 'kg/ha')


if __name__ == '__main__':
    unittest.main()
