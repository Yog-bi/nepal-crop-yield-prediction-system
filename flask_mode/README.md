# Flask Microservice Module (`flask_mode/`)

This directory contains the standalone Python Flask REST API microservice for serving crop yield predictions to the PHP web application.

## Execution Guide

### Option 1: Direct Python execution
```bash
cd flask_mode
pip install -r requirements.txt
python prediction_service.py
```

### Option 2: Running via `app.py`
```bash
python app.py
```

The Flask server will start listening on `http://127.0.0.1:5000`.

## Features

- `/predict` (POST): Accepts JSON payload with 15 input features (12 numeric, 3 categorical).
- Loads `final_model_pipeline.joblib` scikit-learn model from the parent or current directory.
- Features automatic fallback predictor if the 52.9 MB model file is missing.
- Implements CORS (`Flask-CORS`) to communicate cleanly with PHP on localhost.
