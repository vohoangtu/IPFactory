"""Tests for ChronicleRequest Pydantic validators in routers/chronicle.py."""
import sys
import os
import pytest
from unittest.mock import patch, MagicMock

# Add narrative-loom root to path
sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

# Stub heavy module-level imports in chronicle.py before importing it
_celery_stub = MagicMock()
_celery_stub.celery_app = MagicMock()

with patch.dict("sys.modules", {
    "core.celery_app": _celery_stub,
    "tasks.chronicle_task": MagicMock(),
}):
    sys.modules.pop("routers.chronicle", None)
    from routers.chronicle import ChronicleRequest  # noqa: E402

from pydantic import ValidationError


def test_valid_request():
    """A fully specified valid request is accepted."""
    req = ChronicleRequest(world_id=1, tick_start=100, tick_end=200)
    assert req.world_id == 1
    assert req.tick_start == 100
    assert req.tick_end == 200
    assert req.world_era == "genesis"
    assert req.genre == "generic"


def test_valid_request_minimal():
    """Only world_id is required; all other fields default correctly."""
    req = ChronicleRequest(world_id=1)
    assert req.world_id == 1
    assert req.tick_start is None
    assert req.tick_end is None


def test_world_id_zero_rejected():
    """world_id of 0 must be rejected."""
    with pytest.raises(ValidationError, match="world_id must be positive"):
        ChronicleRequest(world_id=0)


def test_world_id_negative_rejected():
    """Negative world_id must be rejected."""
    with pytest.raises(ValidationError, match="world_id must be positive"):
        ChronicleRequest(world_id=-5)


def test_tick_end_before_start_rejected():
    """tick_end earlier than tick_start must be rejected."""
    with pytest.raises(ValidationError, match="tick_end must be >= tick_start"):
        ChronicleRequest(world_id=1, tick_start=200, tick_end=100)


def test_tick_end_equal_start_ok():
    """tick_end equal to tick_start is valid."""
    req = ChronicleRequest(world_id=1, tick_start=100, tick_end=100)
    assert req.tick_end == 100


def test_tick_end_without_start_ok():
    """tick_end provided without tick_start is valid."""
    req = ChronicleRequest(world_id=1, tick_end=100)
    assert req.tick_end == 100


def test_tick_start_without_end_ok():
    """tick_start provided without tick_end is valid."""
    req = ChronicleRequest(world_id=1, tick_start=100)
    assert req.tick_start == 100


def test_defaults():
    """Default field values are set correctly."""
    req = ChronicleRequest(world_id=42)
    assert req.world_era == "genesis"
    assert req.genre == "generic"
    assert req.whispers == []
    assert req.power_system is None
    assert req.ai_runtime is None


def test_whispers_list():
    """whispers field accepts a list of strings."""
    req = ChronicleRequest(world_id=1, whispers=["hint1", "hint2"])
    assert req.whispers == ["hint1", "hint2"]


def test_ai_runtime_dict():
    """ai_runtime field accepts arbitrary dict."""
    req = ChronicleRequest(world_id=1, ai_runtime={"provider": "openai", "model": "gpt-4o"})
    assert req.ai_runtime["provider"] == "openai"
