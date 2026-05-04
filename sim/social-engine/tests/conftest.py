import sys
from unittest.mock import MagicMock

# Mock heavy/optional dependencies that are not installed in the test environment
# so that test imports of app.services submodules don't fail due to __init__.py
# eagerly pulling in everything.

_MISSING_MODULES = {
    "fastembed": MagicMock(),
    "fastembed.TextEmbedding": MagicMock(),
    "camel": MagicMock(),
    "camel.oasis": MagicMock(),
    "PyMuPDF": MagicMock(),
}

for name, mock_obj in _MISSING_MODULES.items():
    if name not in sys.modules:
        sys.modules[name] = mock_obj

# fastembed submodule used by worldos_cache
sys.modules["fastembed"] = MagicMock()
sys.modules["fastembed.fastembed"] = MagicMock()

# camel-ai / camel-oasis submodules
sys.modules["camel"] = MagicMock()
sys.modules["camel.oasis"] = MagicMock()
