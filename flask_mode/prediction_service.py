import os
import sys
import logging
from flask import Flask, request, jsonify
from flask_cors import CORS

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s"
)
logger = logging.getLogger(__name__)

app = Flask(__name__)
CORS(app)

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
PARENT_DIR = os.path.dirname(BASE_DIR)

MODEL_PATH = os.environ.get(
    "MODEL_PATH",
    os.path.join(PARENT_DIR, "final_model_pipeline.joblib")
    if os.path.exists(os.path.join(PARENT_DIR, "final_model_pipeline.joblib"))
    else os.path.join(BASE_DIR, "final_model_pipeline.joblib")
)
DATASET_CANDIDATES = [
    os.path.join(PARENT_DIR, "pre_training_package_v1", "model_dataset_primary_v1.csv"),
    os.path.join(BASE_DIR, "pre_training_package_v1", "model_dataset_primary_v1.csv"),
    os.path.abspath(os.path.join(PARENT_DIR, "..", "..", "model-traning", "training", "model_dataset_audit_v1.csv")),
]
DATASET_PATH = os.environ.get("DATASET_PATH") or next(
    (path for path in DATASET_CANDIDATES if os.path.exists(path)),
    DATASET_CANDIDATES[0],
)
PORT = int(os.environ.get("PORT", 5000))

model_pipeline = None
df_dataset = None

NUMERIC_FEATURES = [
    "Area_ha",
    "Rainfall_mm_total",
    "Avg_Temperature_C",
    "Mean_Daily_Max_Temperature_C",
    "Mean_Daily_Min_Temperature_C",
    "Relative_Humidity_pct",
    "Specific_Humidity_g_kg",
    "Surface_Pressure_kPa",
    "Wind_Speed_m_s",
    "Solar_Radiation_kWh_m2_day",
    "Latitude",
    "Longitude"
]

CATEGORICAL_FEATURES = [
    "Province",
    "District",
    "Crop"
]

ALL_FEATURES = NUMERIC_FEATURES + CATEGORICAL_FEATURES

FEATURE_RANGES = {
    "Area_ha": (1.0, 95311.0),
    "Rainfall_mm_total": (354.55, 2888.66),
    "Avg_Temperature_C": (-5.54, 26.51),
    "Mean_Daily_Max_Temperature_C": (0.70, 32.89),
    "Mean_Daily_Min_Temperature_C": (-11.01, 21.48),
    "Relative_Humidity_pct": (46.37, 82.33),
    "Specific_Humidity_g_kg": (3.93, 14.81),
    "Surface_Pressure_kPa": (55.51, 99.36),
    "Wind_Speed_m_s": (1.81, 3.28),
    "Solar_Radiation_kWh_m2_day": (3.84, 6.02),
    "Latitude": (26.58, 30.03),
    "Longitude": (80.32, 87.92)
}


def load_resources():
    global model_pipeline, df_dataset
    if os.path.exists(MODEL_PATH):
        try:
            import joblib
            model_pipeline = joblib.load(MODEL_PATH)
            logger.info(f"Loaded Random Forest model from '{MODEL_PATH}'")
        except ModuleNotFoundError as mne:
            logger.error(f"Missing dependency while loading model '{MODEL_PATH}': {mne}. Please run: pip install scikit-learn pandas joblib")
            model_pipeline = None
        except Exception as e:
            logger.error(f"Error loading model '{MODEL_PATH}': {e}")
            model_pipeline = None
    else:
        logger.error(f"Model file '{MODEL_PATH}' not found. Prediction requests will be unavailable.")

    if os.path.exists(DATASET_PATH):
        try:
            import pandas as pd
            df_dataset = pd.read_csv(DATASET_PATH)
            logger.info(f"Loaded training dataset from '{DATASET_PATH}' ({len(df_dataset)} rows)")
        except Exception as e:
            logger.error(f"Error loading dataset '{DATASET_PATH}': {e}")
            df_dataset = None


load_resources()


@app.route("/", methods=["GET"])
def health_check():
    ready = model_pipeline is not None and df_dataset is not None
    return jsonify({
        "status": "healthy" if ready else "degraded",
        "service": "Nepal Crop Yield Prediction API (flask_mode)",
        "model_loaded": model_pipeline is not None,
        "dataset_loaded": df_dataset is not None,
        "prediction_ready": model_pipeline is not None,
        "defaults_ready": df_dataset is not None,
    }), 200


@app.route("/district_averages", methods=["GET"])
def get_district_averages():
    district = request.args.get("district", "").strip()
    crop = request.args.get("crop", "").strip()
    scenario = request.args.get("scenario", "typical").strip().lower()

    if not district or not crop:
        return jsonify({"success": False, "error": "Both district and crop parameters are required"}), 400

    if scenario not in {"typical", "recent"}:
        return jsonify({"success": False, "error": "Scenario must be 'typical' or 'recent'"}), 400

    if df_dataset is None:
        return jsonify({
            "success": False,
            "error": "Historical feature data is unavailable. No replacement values were generated."
        }), 503

    try:
        filtered = df_dataset[
            (df_dataset["District"] == district)
            & (df_dataset["Crop"] == crop)
        ].copy()
        if "Training_Inclusion" in filtered.columns:
            filtered = filtered[filtered["Training_Inclusion"] == "included"]

        if filtered.empty:
            return jsonify({
                "success": False,
                "error": f"No approved historical profile is available for {crop} in {district}."
            }), 404

        record_count = int(len(filtered))
        if scenario == "recent":
            if "Start_Year" in filtered.columns:
                filtered = filtered.sort_values("Start_Year", ascending=False)
            selected = filtered.iloc[0]
            averages = {feat: float(selected[feat]) for feat in NUMERIC_FEATURES}
            source_period = str(selected.get("Fiscal_Year", "Most recent completed period"))
        else:
            averages = {feat: float(filtered[feat].mean()) for feat in NUMERIC_FEATURES}
            source_period = "Average of available completed periods"

        if any(value != value for value in averages.values()):
            return jsonify({
                "success": False,
                "error": "The selected historical profile contains a missing model feature."
            }), 422

        averages["Province"] = str(filtered["Province"].iloc[0])
        averages = {
            key: round(value, 6) if isinstance(value, float) else value
            for key, value in averages.items()
        }
    except Exception as e:
        logger.error(f"Error calculating dataset profile: {e}", exc_info=True)
        return jsonify({
            "success": False,
            "error": "The historical feature profile could not be prepared."
        }), 500

    return jsonify({
        "success": True,
        "district": district,
        "crop": crop,
        "scenario": scenario,
        "record_count": record_count,
        "source_period": source_period,
        "averages": averages,
    }), 200


@app.route("/predict", methods=["POST"])
@app.route("/predict_debug", methods=["POST"])
def predict():
    if model_pipeline is None:
        return jsonify({
            "success": False,
            "error": "The trained Random Forest model is not loaded. Prediction has been stopped."
        }), 503

    if not request.is_json:
        return jsonify({"success": False, "error": "Content-Type must be application/json"}), 400

    data = request.get_json()
    if not isinstance(data, dict):
        return jsonify({"success": False, "error": "Request body must be a JSON object"}), 400

    missing_fields = [feat for feat in ALL_FEATURES if feat not in data or data[feat] is None or str(data[feat]).strip() == ""]
    if missing_fields:
        return jsonify({"success": False, "error": f"Missing required fields: {', '.join(missing_fields)}"}), 400

    parsed_inputs = {}
    for feat in NUMERIC_FEATURES:
        val = data[feat]
        try:
            parsed_val = float(val)
            min_v, max_v = FEATURE_RANGES[feat]
            if parsed_val < min_v or parsed_val > max_v:
                return jsonify({"success": False, "error": f"Field '{feat}' value {parsed_val} outside training bounds ({min_v} to {max_v})"}), 400
            parsed_inputs[feat] = parsed_val
        except (ValueError, TypeError):
            return jsonify({"success": False, "error": f"Field '{feat}' must be a valid number, received '{val}'"}), 400

    for feat in CATEGORICAL_FEATURES:
        parsed_inputs[feat] = str(data[feat]).strip()

    try:
        import pandas as pd
        input_df = pd.DataFrame([parsed_inputs])
        raw_pred = model_pipeline.predict(input_df)[0]
        predicted_yield = round(float(raw_pred), 2)
        engine = "random_forest_pipeline"

        return jsonify({
            "success": True,
            "prediction": predicted_yield,
            "unit": "kg/ha",
            "model_version": "audit_v1",
            "engine": engine,
            "inputs_received": parsed_inputs
        }), 200

    except Exception as e:
        logger.error(f"Prediction execution error: {e}", exc_info=True)
        return jsonify({"success": False, "error": f"Internal model inference failure: {str(e)}"}), 500


if __name__ == "__main__":
    logger.info(f"Starting Prediction Service (flask_mode) on port {PORT}...")
    app.run(host="0.0.0.0", port=PORT, debug=False)
