"""
In-memory per-agent metrics collector for Narrative Loom.

Thread-safe singleton. Metrics are ephemeral (reset on process restart).
Access via the /metrics endpoint.
"""
from collections import defaultdict
from dataclasses import dataclass
from threading import Lock


@dataclass
class AgentMetric:
    total_calls: int = 0
    total_duration_ms: int = 0
    errors: int = 0
    retries: int = 0
    last_duration_ms: int = 0


class MetricsCollector:
    def __init__(self):
        self._lock = Lock()
        self._agents: dict[str, AgentMetric] = defaultdict(AgentMetric)
        self._pipeline_runs: int = 0
        self._pipeline_errors: int = 0

    def record_agent(self, name: str, duration_ms: int, success: bool, retries: int = 0):
        with self._lock:
            m = self._agents[name]
            m.total_calls += 1
            m.total_duration_ms += duration_ms
            m.last_duration_ms = duration_ms
            m.retries += retries
            if not success:
                m.errors += 1

    def record_pipeline(self, success: bool):
        with self._lock:
            self._pipeline_runs += 1
            if not success:
                self._pipeline_errors += 1

    def snapshot(self) -> dict:
        with self._lock:
            return {
                "pipeline": {
                    "total_runs": self._pipeline_runs,
                    "errors": self._pipeline_errors,
                },
                "agents": {
                    name: {
                        "total_calls": m.total_calls,
                        "avg_duration_ms": m.total_duration_ms // max(m.total_calls, 1),
                        "last_duration_ms": m.last_duration_ms,
                        "errors": m.errors,
                        "retries": m.retries,
                    }
                    for name, m in self._agents.items()
                },
            }


metrics = MetricsCollector()
