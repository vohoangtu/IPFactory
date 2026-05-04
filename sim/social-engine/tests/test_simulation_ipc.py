"""
Tests for app/services/simulation_ipc.py
"""

import json
import os
import time
import threading
import pytest

from app.services.simulation_ipc import (
    SimulationIPCClient,
    SimulationIPCServer,
    CommandType,
    CommandStatus,
    IPCCommand,
    IPCResponse,
)


class TestSimulationIPCClient:
    """Test SimulationIPCClient drop-file IPC protocol."""

    def test_init_creates_directories(self, tmpdir):
        sim_dir = str(tmpdir.mkdir("sim"))
        client = SimulationIPCClient(sim_dir)
        assert os.path.isdir(client.commands_dir)
        assert os.path.isdir(client.responses_dir)

    def test_send_command_writes_command_file(self, tmpdir):
        sim_dir = str(tmpdir.mkdir("sim"))
        client = SimulationIPCClient(sim_dir)

        # Start a background thread to write a response after a short delay
        def responder():
            time.sleep(0.2)
            # Read the actual command file to get the command_id
            command_files = [f for f in os.listdir(client.commands_dir) if f.endswith(".json")]
            assert len(command_files) == 1
            command_id = command_files[0].replace(".json", "")
            response = IPCResponse(
                command_id=command_id,
                status=CommandStatus.COMPLETED,
                result={"answer": 42},
            )
            response_file = os.path.join(client.responses_dir, f"{command_id}.json")
            with open(response_file, "w", encoding="utf-8") as f:
                json.dump(response.to_dict(), f)

        threading.Thread(target=responder, daemon=True).start()

        response = client.send_command(
            command_type=CommandType.INTERVIEW,
            args={"agent_id": 1, "prompt": "hello"},
            timeout=2.0,
            poll_interval=0.1,
        )

        assert response.status == CommandStatus.COMPLETED
        assert response.result == {"answer": 42}
        # Command file should be cleaned up
        assert not os.listdir(client.commands_dir)

    def test_send_command_timeout(self, tmpdir):
        sim_dir = str(tmpdir.mkdir("sim"))
        client = SimulationIPCClient(sim_dir)

        with pytest.raises(TimeoutError):
            client.send_command(
                command_type=CommandType.INTERVIEW,
                args={"agent_id": 1, "prompt": "hello"},
                timeout=0.3,
                poll_interval=0.05,
            )

        # Command file should be cleaned up after timeout
        assert not os.listdir(client.commands_dir)

    def test_send_interview(self, tmpdir):
        sim_dir = str(tmpdir.mkdir("sim"))
        client = SimulationIPCClient(sim_dir)

        def responder():
            time.sleep(0.2)
            import glob
            command_files = glob.glob(os.path.join(client.commands_dir, "*.json"))
            assert len(command_files) == 1
            with open(command_files[0], "r", encoding="utf-8") as f:
                cmd_data = json.load(f)
            assert cmd_data["command_type"] == "interview"
            assert cmd_data["args"]["agent_id"] == 7
            assert cmd_data["args"]["prompt"] == "What is your quest?"
            assert cmd_data["args"]["platform"] == "twitter"

            response = IPCResponse(
                command_id=cmd_data["command_id"],
                status=CommandStatus.COMPLETED,
                result={"reply": "To seek the Holy Grail."},
            )
            response_file = os.path.join(client.responses_dir, f"{cmd_data['command_id']}.json")
            with open(response_file, "w", encoding="utf-8") as f:
                json.dump(response.to_dict(), f)

        threading.Thread(target=responder, daemon=True).start()

        response = client.send_interview(
            agent_id=7, prompt="What is your quest?", platform="twitter", timeout=2.0
        )
        assert response.status == CommandStatus.COMPLETED

    def test_check_env_alive(self, tmpdir):
        sim_dir = str(tmpdir.mkdir("sim"))
        client = SimulationIPCClient(sim_dir)
        assert not client.check_env_alive()

        status_file = os.path.join(client.simulation_dir, "env_status.json")
        with open(status_file, "w", encoding="utf-8") as f:
            json.dump({"status": "alive"}, f)
        assert client.check_env_alive()


class TestSimulationIPCServer:
    """Test SimulationIPCServer drop-file IPC protocol."""

    def test_poll_commands_returns_none_when_empty(self, tmpdir):
        sim_dir = str(tmpdir.mkdir("sim"))
        server = SimulationIPCServer(sim_dir)
        assert server.poll_commands() is None

    def test_poll_commands_reads_command(self, tmpdir):
        sim_dir = str(tmpdir.mkdir("sim"))
        server = SimulationIPCServer(sim_dir)

        cmd = IPCCommand(
            command_id="cmd-1",
            command_type=CommandType.CLOSE_ENV,
            args={},
        )
        cmd_file = os.path.join(server.commands_dir, "cmd-1.json")
        with open(cmd_file, "w", encoding="utf-8") as f:
            json.dump(cmd.to_dict(), f)

        result = server.poll_commands()
        assert result is not None
        assert result.command_id == "cmd-1"
        assert result.command_type == CommandType.CLOSE_ENV

    def test_send_response_creates_file_and_deletes_command(self, tmpdir):
        sim_dir = str(tmpdir.mkdir("sim"))
        server = SimulationIPCServer(sim_dir)

        cmd = IPCCommand(
            command_id="cmd-2",
            command_type=CommandType.INTERVIEW,
            args={"agent_id": 1},
        )
        cmd_file = os.path.join(server.commands_dir, "cmd-2.json")
        with open(cmd_file, "w", encoding="utf-8") as f:
            json.dump(cmd.to_dict(), f)

        response = IPCResponse(
            command_id="cmd-2",
            status=CommandStatus.COMPLETED,
            result={"data": "ok"},
        )
        server.send_response(response)

        response_file = os.path.join(server.responses_dir, "cmd-2.json")
        assert os.path.exists(response_file)
        with open(response_file, "r", encoding="utf-8") as f:
            data = json.load(f)
        assert data["status"] == "completed"

        # Command file should be deleted
        assert not os.path.exists(cmd_file)

    def test_start_stop_env_status(self, tmpdir):
        sim_dir = str(tmpdir.mkdir("sim"))
        server = SimulationIPCServer(sim_dir)
        server.start()
        status_file = os.path.join(server.simulation_dir, "env_status.json")
        assert os.path.exists(status_file)
        with open(status_file, "r", encoding="utf-8") as f:
            data = json.load(f)
        assert data["status"] == "alive"

        server.stop()
        with open(status_file, "r", encoding="utf-8") as f:
            data = json.load(f)
        assert data["status"] == "stopped"
