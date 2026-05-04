"""Tests for /health and /metrics endpoints in routers/system.py."""
import sys
import os
import pytest
from unittest.mock import patch, MagicMock

# Add narrative-loom root to path so imports work
sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))


def _make_client():
    """Create a TestClient with all heavy deps mocked before app import."""
    mock_celery = MagicMock()
    mock_conn = MagicMock()
    mock_celery.connection.return_value = mock_conn

    mock_cache = MagicMock()
    mock_cache.redis_available = True
    mock_cache.redis_client.ping.return_value = True

    # chronicle.py imports celery_app at module level, so stub it out via
    # sys.modules before importing main.
    celery_module_mock = MagicMock()
    celery_module_mock.celery_app = mock_celery

    task_module_mock = MagicMock()

    with patch.dict("sys.modules", {
        "core.celery_app": celery_module_mock,
        "tasks.chronicle_task": task_module_mock,
    }):
        with patch("utils.cache_manager.cache_manager", mock_cache):
            # Drop previously cached modules so we get a fresh import
            for mod in ["main", "routers.system", "routers.chronicle",
                        "routers.actors", "routers.scribe"]:
                sys.modules.pop(mod, None)

            from fastapi.testclient import TestClient  # noqa: PLC0415
            from main import app  # noqa: PLC0415
            client = TestClient(app)

    return client, mock_cache, mock_celery


@pytest.fixture(scope="module")
def client_and_mocks():
    """Yield (TestClient, mock_cache, mock_celery)."""
    client, mock_cache, mock_celery = _make_client()
    yield client, mock_cache, mock_celery


@pytest.fixture(scope="module")
def client(client_and_mocks):
    """Yield just the TestClient."""
    c, _, _ = client_and_mocks
    return c


# ---------------------------------------------------------------------------
# /health tests
# ---------------------------------------------------------------------------

def test_health_healthy(client_and_mocks):
    """Health check returns healthy when all services are ok."""
    client, mock_cache, mock_celery = client_and_mocks

    mock_cache.redis_available = True
    mock_cache.redis_client.ping.return_value = True
    mock_conn = MagicMock()
    mock_celery.connection.return_value = mock_conn

    mock_httpx_response = MagicMock()
    mock_httpx_response.status_code = 200

    with patch("utils.cache_manager.cache_manager", mock_cache), \
         patch("core.celery_app.celery_app", mock_celery), \
         patch("httpx.get", return_value=mock_httpx_response):
        response = client.get("/health")

    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "healthy"
    assert data["checks"]["redis"] == "ok"
    assert data["checks"]["celery_broker"] == "ok"
    assert data["checks"]["llm_pool"] == "reachable"


def test_health_degraded_redis_down(client_and_mocks):
    """Health check returns degraded when Redis raises a ConnectionError."""
    client, mock_cache, mock_celery = client_and_mocks

    mock_cache.redis_available = True
    mock_cache.redis_client.ping.reset_mock(side_effect=True, return_value=True)
    mock_cache.redis_client.ping.side_effect = ConnectionError("Redis down")
    mock_conn = MagicMock()
    mock_celery.connection.return_value = mock_conn

    with patch("utils.cache_manager.cache_manager", mock_cache), \
         patch("core.celery_app.celery_app", mock_celery):
        response = client.get("/health")

    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "degraded"
    assert "error" in data["checks"]["redis"]

    # Reset side_effect for subsequent tests
    mock_cache.redis_client.ping.side_effect = None
    mock_cache.redis_client.ping.return_value = True


def test_health_redis_unavailable(client_and_mocks):
    """Health check marks redis as unavailable when not configured."""
    client, mock_cache, mock_celery = client_and_mocks

    mock_cache.redis_available = False
    mock_conn = MagicMock()
    mock_celery.connection.return_value = mock_conn

    with patch("utils.cache_manager.cache_manager", mock_cache), \
         patch("core.celery_app.celery_app", mock_celery):
        response = client.get("/health")

    assert response.status_code == 200
    data = response.json()
    assert data["checks"]["redis"] == "unavailable"

    # Restore for subsequent tests
    mock_cache.redis_available = True


def test_health_celery_down(client_and_mocks):
    """Health check returns degraded when Celery broker is unreachable."""
    client, mock_cache, mock_celery = client_and_mocks

    mock_cache.redis_available = True
    mock_cache.redis_client.ping.return_value = True
    mock_celery.connection.side_effect = Exception("Broker unavailable")

    with patch("utils.cache_manager.cache_manager", mock_cache), \
         patch("core.celery_app.celery_app", mock_celery):
        response = client.get("/health")

    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "degraded"
    assert "error" in data["checks"]["celery_broker"]

    # Reset side_effect
    mock_celery.connection.side_effect = None
    mock_conn = MagicMock()
    mock_celery.connection.return_value = mock_conn


def test_health_llm_pool_error(client_and_mocks):
    """Health check reflects LLM pool error when backend is unreachable."""
    client, mock_cache, mock_celery = client_and_mocks

    mock_cache.redis_available = True
    mock_cache.redis_client.ping.return_value = True
    mock_conn = MagicMock()
    mock_celery.connection.return_value = mock_conn

    mock_httpx_response = MagicMock()
    mock_httpx_response.status_code = 503

    with patch("utils.cache_manager.cache_manager", mock_cache), \
         patch("core.celery_app.celery_app", mock_celery), \
         patch("httpx.get", return_value=mock_httpx_response):
        response = client.get("/health")

    assert response.status_code == 200
    checks = response.json()["checks"]
    assert "llm_pool" in checks
    assert "error: HTTP 503" in checks["llm_pool"]


# ---------------------------------------------------------------------------
# /metrics tests
# ---------------------------------------------------------------------------

def test_metrics_empty(client):
    """Metrics endpoint returns a snapshot with pipeline and agents keys."""
    from core.metrics import MetricsCollector

    empty_collector = MetricsCollector()
    with patch("core.metrics.metrics", empty_collector):
        response = client.get("/metrics")

    assert response.status_code == 200
    data = response.json()
    assert "pipeline" in data
    assert "agents" in data
    assert data["pipeline"]["total_runs"] == 0
    assert data["pipeline"]["errors"] == 0


def test_metrics_with_data(client):
    """Metrics endpoint returns correct aggregated data after recording."""
    from core.metrics import MetricsCollector

    collector = MetricsCollector()
    collector.record_agent("historian", 150, True)
    collector.record_agent("historian", 200, True)
    collector.record_agent("critic", 300, False)
    collector.record_pipeline(True)

    snapshot = collector.snapshot()
    assert snapshot["pipeline"]["total_runs"] == 1
    assert snapshot["agents"]["historian"]["total_calls"] == 2
    assert snapshot["agents"]["historian"]["avg_duration_ms"] == 175
    assert snapshot["agents"]["critic"]["errors"] == 1


def test_metrics_pipeline_error_tracking(client):
    """Pipeline error count increments on failed runs."""
    from core.metrics import MetricsCollector

    collector = MetricsCollector()
    collector.record_pipeline(True)
    collector.record_pipeline(False)
    collector.record_pipeline(False)

    snapshot = collector.snapshot()
    assert snapshot["pipeline"]["total_runs"] == 3
    assert snapshot["pipeline"]["errors"] == 2
