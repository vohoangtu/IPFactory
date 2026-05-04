"""
Tests for app/services/simulation_manager.py
Mocks filesystem (via tmpdir) and Zep Cloud calls.
"""

import json
import os
import pytest
from unittest.mock import MagicMock, patch

from app.services.simulation_manager import (
    SimulationManager,
    SimulationState,
    SimulationStatus,
)
from app.services.zep_entity_reader import EntityNode, FilteredEntities


@pytest.fixture
def manager(tmpdir, monkeypatch):
    """Fixture providing a SimulationManager with mocked data dir."""
    sim_dir = str(tmpdir.mkdir("simulations"))
    monkeypatch.setattr(
        SimulationManager, "SIMULATION_DATA_DIR", sim_dir
    )
    mgr = SimulationManager()
    return mgr


class TestCreateSimulation:
    def test_create_simulation(self, manager):
        state = manager.create_simulation(
            project_id="proj-1",
            graph_id="graph-1",
            enable_twitter=True,
            enable_reddit=False,
        )
        assert state.project_id == "proj-1"
        assert state.graph_id == "graph-1"
        assert state.enable_twitter is True
        assert state.enable_reddit is False
        assert state.status == SimulationStatus.CREATED
        assert state.simulation_id.startswith("sim_")

        # State should be persisted
        loaded = manager.get_simulation(state.simulation_id)
        assert loaded is not None
        assert loaded.project_id == "proj-1"


class TestPrepareSimulation:
    def _mock_zep_reader(self, monkeypatch, entities):
        """Helper to mock ZepEntityReader."""
        mock_reader = MagicMock()
        filtered = FilteredEntities(
            entities=entities,
            entity_types={e.get_entity_type() for e in entities},
            total_count=len(entities),
            filtered_count=len(entities),
        )
        mock_reader.filter_defined_entities.return_value = filtered
        monkeypatch.setattr(
            "app.services.simulation_manager.ZepEntityReader",
            lambda: mock_reader,
        )
        return mock_reader

    def _mock_profile_generator(self, monkeypatch, profiles):
        mock_gen = MagicMock()
        mock_gen.generate_profiles_from_entities.return_value = profiles
        monkeypatch.setattr(
            "app.services.simulation_manager.OasisProfileGenerator",
            lambda **kwargs: mock_gen,
        )
        return mock_gen

    def _mock_config_generator(self, monkeypatch, sim_params):
        mock_gen = MagicMock()
        mock_gen.generate_config.return_value = sim_params
        monkeypatch.setattr(
            "app.services.simulation_manager.SimulationConfigGenerator",
            lambda: mock_gen,
        )
        return mock_gen

    def test_prepare_simulation_success(self, manager, monkeypatch):
        entities = [
            EntityNode(
                uuid="u1",
                name="Alice",
                labels=["Person", "Entity"],
                summary="A test person",
                attributes={},
            ),
            EntityNode(
                uuid="u2",
                name="Bob",
                labels=["Person", "Entity"],
                summary="Another test person",
                attributes={},
            ),
        ]
        self._mock_zep_reader(monkeypatch, entities)

        profiles = [
            {"user_id": 1, "username": "alice"},
            {"user_id": 2, "username": "bob"},
        ]
        self._mock_profile_generator(monkeypatch, profiles)

        mock_params = MagicMock()
        mock_params.to_json.return_value = json.dumps({"config": "ok"})
        mock_params.generation_reasoning = "mock reasoning"
        self._mock_config_generator(monkeypatch, mock_params)

        state = manager.create_simulation("proj-1", "graph-1")
        result = manager.prepare_simulation(
            simulation_id=state.simulation_id,
            simulation_requirement="Test simulation",
            document_text="Some document",
        )

        assert result.status == SimulationStatus.READY
        assert result.entities_count == 2
        assert result.profiles_count == 2
        assert result.config_generated is True

        # Config file should exist
        sim_dir = manager._get_simulation_dir(state.simulation_id)
        config_path = os.path.join(sim_dir, "simulation_config.json")
        assert os.path.exists(config_path)

    def test_prepare_simulation_no_entities(self, manager, monkeypatch):
        self._mock_zep_reader(monkeypatch, [])

        state = manager.create_simulation("proj-1", "graph-1")
        result = manager.prepare_simulation(
            simulation_id=state.simulation_id,
            simulation_requirement="Test simulation",
            document_text="Some document",
        )

        assert result.status == SimulationStatus.FAILED
        assert "没有找到符合条件的实体" in result.error

    def test_prepare_simulation_progress_callback(self, manager, monkeypatch):
        entities = [
            EntityNode(
                uuid="u1",
                name="Alice",
                labels=["Person", "Entity"],
                summary="A test person",
                attributes={},
            ),
        ]
        self._mock_zep_reader(monkeypatch, entities)
        self._mock_profile_generator(monkeypatch, [{"user_id": 1, "username": "alice"}])

        mock_params = MagicMock()
        mock_params.to_json.return_value = "{}"
        mock_params.generation_reasoning = ""
        self._mock_config_generator(monkeypatch, mock_params)

        progress_stages = []

        def progress(stage, progress_pct, message, **kwargs):
            progress_stages.append((stage, progress_pct, message))

        state = manager.create_simulation("proj-1", "graph-1")
        manager.prepare_simulation(
            simulation_id=state.simulation_id,
            simulation_requirement="Test",
            document_text="Doc",
            progress_callback=progress,
        )

        stages = [s[0] for s in progress_stages]
        assert "reading" in stages
        assert "generating_profiles" in stages
        assert "generating_config" in stages


class TestListAndGet:
    def test_list_simulations(self, manager):
        s1 = manager.create_simulation("proj-a", "graph-a")
        s2 = manager.create_simulation("proj-a", "graph-b")
        s3 = manager.create_simulation("proj-b", "graph-c")

        all_sims = manager.list_simulations()
        assert len(all_sims) == 3

        proj_a_sims = manager.list_simulations(project_id="proj-a")
        assert len(proj_a_sims) == 2
        ids = {s.simulation_id for s in proj_a_sims}
        assert s1.simulation_id in ids
        assert s2.simulation_id in ids

    def test_get_simulation_not_found(self, manager):
        assert manager.get_simulation("nonexistent") is None


class TestGetProfilesAndConfig:
    def test_get_profiles(self, manager, monkeypatch):
        s = manager.create_simulation("proj-1", "graph-1")
        sim_dir = manager._get_simulation_dir(s.simulation_id)

        profile_data = [{"user_id": 1, "username": "alice"}]
        with open(os.path.join(sim_dir, "reddit_profiles.json"), "w", encoding="utf-8") as f:
            json.dump(profile_data, f)

        profiles = manager.get_profiles(s.simulation_id, platform="reddit")
        assert profiles == profile_data

    def test_get_simulation_config(self, manager):
        s = manager.create_simulation("proj-1", "graph-1")
        sim_dir = manager._get_simulation_dir(s.simulation_id)

        config_data = {"max_rounds": 10}
        with open(os.path.join(sim_dir, "simulation_config.json"), "w", encoding="utf-8") as f:
            json.dump(config_data, f)

        cfg = manager.get_simulation_config(s.simulation_id)
        assert cfg == config_data

    def test_get_run_instructions(self, manager):
        s = manager.create_simulation("proj-1", "graph-1")
        instructions = manager.get_run_instructions(s.simulation_id)
        assert "simulation_dir" in instructions
        assert "scripts_dir" in instructions
        assert "commands" in instructions
        assert "twitter" in instructions["commands"]
        assert "reddit" in instructions["commands"]
