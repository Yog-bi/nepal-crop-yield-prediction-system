"""
Flask Application Entry Point Alias for flask_mode directory
Runs prediction_service Flask app instance.
"""

import os
import sys

sys.path.insert(0, os.path.abspath(os.path.dirname(__file__)))

from prediction_service import app, PORT

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=PORT, debug=False)
