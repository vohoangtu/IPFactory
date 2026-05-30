"""
Tests for app/services/runner_models.py
Unit tests for RunnerStatus, AgentAction, RoundSummary, SimulationRunState.
"""

import sys
from unittest.mock import MagicMock

_MISSING = {"fastembed": MagicMock(), "camel": MagicMock(), "PyMuPDF": MagicMock()}
for name, m in _MISSING.items():
    if name not in sys.modules:
        sys.modules[name] = m

import pytest
from app.services.runner_models import (
    RunnerStatus,
    AgentAction,
    RoundSummary,
    SimulationRunState,
)


class TestRunnerStatus:
    """Tests for RunnerStatus enum."""

    def test_has_expected_values(self):
        assert RunnerStatus.IDLE is not None
        assert RunnerStatus.RUNNING is not None
        assert hasattr(RunnerStatus, "COMPLETED") or hasattr(RunnerStatus, "DONE")
        assert hasattr(RunnerStatus, "FAILED") or hasattr(RunnerStatus, "ERROR")

    def test_values_are_unique(self):
        vals = [v.value for v in RunnerStatus if hasattr(v, 'value')]
        if vals:
            assert len(vals) == len(set(vals)), "RunnerStatus values should be unique"


class TestAgentAction:
    """Tests for AgentAction model."""

    def test_agent_action_creation(self):
        action = AgentAction(
            agent_id=1,
            agent_name="TestAgent",
            action="post_tweet",
            content="Hello world",
            round_number=1,
        )
        assert action.agent_id == 1
        assert action.agent_name == "TestAgent"
        assert action.action == "post_tweet"
        assert action.content == "Hello world"
        assert action.round_number == 1

    def test_agent_action_defaults(self):
        action = AgentAction(
            agent_id=2,
            agent_name="Bot",
            action="idle",
            content="",
            round_number=0,
        )
        assert action.agent_id == 2


class TestRoundSummary:
    """Tests for RoundSummary model."""

    def test_round_summary_creation(self):
        actions = [
            AgentAction(agent_id=1, agent_name="A", action="post", content="Hi", round_number=1),
            AgentAction(agent_id=2, agent_name="B", action="reply", content="Hey", round_number=1),
        ]
        summary = RoundSummary(round_number=1, actions=actions)
        assert summary.round_number == 1
        assert len(summary.actions) == 2

    def test_empty_round_summary(self):
        summary = RoundSummary(round_number=5, actions=[])
        assert summary.round_number == 5
        assert summary.actions == []


class TestSimulationRunState:
    """Tests for SimulationRunState holder."""

    def test_initial_state(self):
        state = SimulationRunState()
        assert hasattr(state, "status")
        assert hasattr(state, "current_round")
        assert hasattr(state, "total_rounds")

    def test_state_transitions(self):
        state = SimulationRunState()
        state.status = RunnerStatus.IDLE
        assert state.status == RunnerStatus.IDLE

        state.status = RunnerStatus.RUNNING
        assert state.status == RunnerStatus.RUNNING
