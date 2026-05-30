"""
Tests for app/services/swarm_profile_factory.py
Unit tests for SwarmAgentProfile generation, serialization, and diversity.
"""

import sys
from unittest.mock import MagicMock

# Mock heavy dependencies (same pattern as conftest.py).
_MISSING = {"fastembed": MagicMock(), "camel": MagicMock(), "PyMuPDF": MagicMock()}
for name, m in _MISSING.items():
    if name not in sys.modules:
        sys.modules[name] = m

import pytest
from app.services.swarm_profile_factory import (
    SwarmAgentProfile,
    SwarmProfileFactory,
)


class TestSwarmAgentProfile:
    """Unit tests for the SwarmAgentProfile dataclass."""

    def test_default_values(self):
        profile = SwarmAgentProfile(user_id=1, user_name="agent1", name="Alice", bio="A bio", persona="friendly")
        assert profile.age == 25
        assert profile.gender == "unknown"
        assert profile.profession == "commoner"
        assert profile.interested_topics == []

    def test_to_dict_includes_all_fields(self):
        profile = SwarmAgentProfile(
            user_id=42, user_name="hero", name="Arthur",
            bio="King of legend", persona="noble",
            age=35, gender="male", profession="warrior",
            interested_topics=["honor", "battle"],
        )
        d = profile.to_dict()
        assert d["user_id"] == 42
        assert d["user_name"] == "hero"
        assert d["name"] == "Arthur"
        assert d["bio"] == "King of legend"
        assert d["persona"] == "noble"
        assert d["age"] == 35
        assert d["gender"] == "male"
        assert d["profession"] == "warrior"
        assert d["interested_topics"] == ["honor", "battle"]

    def test_to_dict_excludes_private_fields(self):
        profile = SwarmAgentProfile(user_id=1, user_name="x", name="X", bio="b", persona="p")
        d = profile.to_dict()
        assert "_private" not in d
        assert "to_dict" not in d

    def test_to_twitter_format(self):
        profile = SwarmAgentProfile(
            user_id=99, user_name="twitter_user_99", name="Bob",
            bio="Chirping", persona="social",
        )
        tw = profile.to_twitter_format()
        assert "user_id" in tw or "user_name" in tw or "name" in tw
        assert isinstance(tw, dict)


class TestSwarmProfileFactory:
    """Unit tests for SwarmProfileFactory generation."""

    @staticmethod
    def _make_context(agents_count=5, era="Bronze Age", tech="basic tools",
                      social="tribal", comm="oral", event="a great flood"):
        """Build a minimal mock WorldContext for the factory."""
        ctx = MagicMock()
        ctx.agents_count = agents_count
        ctx.era = era
        ctx.tech_level = tech
        ctx.social_structure = social
        ctx.communication_method = comm
        ctx.event_trigger = event
        return ctx

    @pytest.fixture
    def factory(self):
        return SwarmProfileFactory()

    def test_generate_profiles_returns_correct_count(self, factory):
        ctx = self._make_context(agents_count=5)
        profiles = factory.generate_profiles(ctx)
        assert len(profiles) == 5

    def test_generate_profiles_caps_at_50(self, factory):
        ctx = self._make_context(agents_count=100)
        profiles = factory.generate_profiles(ctx)
        assert len(profiles) == 50

    def test_generate_profiles_returns_profiles_with_names(self, factory):
        ctx = self._make_context(agents_count=2)
        profiles = factory.generate_profiles(ctx)
        for p in profiles:
            assert p.name, "each profile should have a name"
            assert p.user_name, "each profile should have a user_name"
            assert p.persona, "each profile should have a persona"

    def test_generate_profiles_are_serializable(self, factory):
        ctx = self._make_context(agents_count=3)
        profiles = factory.generate_profiles(ctx)
        for p in profiles:
            d = p.to_dict()
            assert isinstance(d, dict)
            assert all(isinstance(k, str) for k in d.keys())
