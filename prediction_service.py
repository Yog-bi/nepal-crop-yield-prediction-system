"""Canonical launcher for the trained crop-yield prediction service.

The implementation and packaged model live in ``flask_mode``. Keeping this
small launcher gives the project one documented command while avoiding a
second, divergent prediction implementation.
"""

import os

from flask_mode.prediction_service import app


if __name__ == "__main__":
    port = int(os.environ.get("PORT", 5000))
    app.run(host="0.0.0.0", port=port, debug=False)
