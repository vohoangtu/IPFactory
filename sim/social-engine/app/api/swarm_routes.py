import uuid
import os
import json
import logging
from fastapi import APIRouter, HTTPException, BackgroundTasks
from pydantic import BaseModel
from typing import List, Optional
from datetime import datetime

from ..config import Config

logger = logging.getLogger('worldos.swarm_routes')

router = APIRouter()


# Vỏ bối cảnh từ Laravel truyền sang
class WorldContext(BaseModel):
    era: str             # vd: "Medieval Low-Fantasy"
    tech_level: str      # vd: "Swords and Magic, no electricity"
    social_structure: str # vd: "Feudalism, Serfdom"
    communication_method: str # vd: "Town Criers, Ravens, Taverns"
    event_trigger: str   # vd: "The Mad King has been assassinated by a rebel faction."
    agents_count: int = 10 # Số lượng Agent tối đa


# Dữ liệu trả về
class SpawnResponse(BaseModel):
    success: bool
    task_id: str
    message: str


def run_swarm_simulation_task(context: WorldContext, task_id: str):
    """
    Background task: generate agent profiles from WorldContext,
    write simulation config, and launch SimulationRunner.
    """
    try:
        from .swarm_profile_factory import SwarmProfileFactory

        sim_dir = os.path.join(Config.OASIS_SIMULATION_DATA_DIR, task_id)
        os.makedirs(sim_dir, exist_ok=True)

        # 1. Generate agent profiles from WorldContext
        factory = SwarmProfileFactory()
        agents = factory.generate_profiles(context)

        # 2. Write simulation config that SimulationRunner expects
        sim_config = {
            "simulation_id": task_id,
            "era": context.era,
            "tech_level": context.tech_level,
            "social_structure": context.social_structure,
            "communication_method": context.communication_method,
            "event_trigger": context.event_trigger,
            "agents_count": len(agents),
            "agents": [a.to_dict() for a in agents],
            "time_config": {
                "total_simulation_hours": 24,
                "minutes_per_round": 30,
            },
            "platform_config": {
                "twitter": {"enabled": True},
                "reddit": {"enabled": True},
            },
            "created_at": datetime.now().isoformat(),
        }

        config_path = os.path.join(sim_dir, "simulation_config.json")
        with open(config_path, 'w', encoding='utf-8') as f:
            json.dump(sim_config, f, ensure_ascii=False, indent=2)

        # 3. Attempt to start simulation via SimulationRunner
        try:
            from .simulation_runner import SimulationRunner

            max_rounds = Config.OASIS_DEFAULT_MAX_ROUNDS
            SimulationRunner.start_simulation(
                simulation_id=task_id,
                platform="parallel",
                max_rounds=max_rounds,
            )
            logger.info(f"Simulation started: {task_id} with {len(agents)} agents")
        except ValueError as e:
            # Config missing or simulation already running
            logger.warning(f"SimulationRunner could not start: {e}")
            # Write error state
            error_path = os.path.join(sim_dir, "error.json")
            with open(error_path, 'w', encoding='utf-8') as f:
                json.dump({"error": str(e), "timestamp": datetime.now().isoformat()}, f)
        except Exception as e:
            logger.error(f"SimulationRunner failed: {e}", exc_info=True)
            error_path = os.path.join(sim_dir, "error.json")
            with open(error_path, 'w', encoding='utf-8') as f:
                json.dump({"error": str(e), "timestamp": datetime.now().isoformat()}, f)

    except Exception as e:
        logger.error(f"Swarm simulation task failed: {e}", exc_info=True)


@router.post("/swarm/spawn", response_model=SpawnResponse)
async def spawn_swarm(context: WorldContext, background_tasks: BackgroundTasks):
    # Validate configuration
    config_errors = Config.validate()
    if config_errors:
        raise HTTPException(
            status_code=503,
            detail=f"Service not configured: {', '.join(config_errors)}"
        )

    try:
        task_id = str(uuid.uuid4())

        # Chạy giả lập trong Background
        background_tasks.add_task(run_swarm_simulation_task, context, task_id)

        return SpawnResponse(
            success=True,
            task_id=task_id,
            message=f"Simulation started for era: {context.era} with {context.agents_count} agents."
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
