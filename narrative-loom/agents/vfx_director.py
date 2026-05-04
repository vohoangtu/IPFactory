from core.agent_wrapper import agent_node
from core.logging import get_logger
import os
import json
from typing import Dict, Any
from state import NarrativeState
from utils.llm_factory import get_llm, get_llm_for_agent
from nodes.universe_bridge import record_universe_whisper
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.output_parsers import JsonOutputParser
from pydantic import BaseModel, Field
from typing import List, Optional

from schemas import AnimationScript

log = get_logger(__name__)


class VFXConfig(BaseModel):
    primary_color: str = Field(description="Hex color code (e.g. #ff4500 for Paleo, #00f3ff for Sci-fi)")
    distortion: float = Field(description="Reality distortion level (0.0 - 1.0)")
    particle_density: int = Field(description="Particle density (40 - 200)")
    atmosphere_filter: str = Field(description="Atmosphere filter (none, mist, sepia, grain, glitch, aurora, dust)")


class VFXDirectorOutput(BaseModel):
    vfx_config: VFXConfig
    animation_script: AnimationScript


vfx_director_prompt = ChatPromptTemplate.from_messages([
    ("system", """You are the Visual Director & Cinematographer of NarrativeLoom.

Your dual mission:
1. Set VFX configuration (color palette, distortion, particles, atmosphere)
2. Create a cinematic animation script that breaks the narrative into visual scenes

Analyze the chronicle content, dramatic arc, entropy, and distortion to produce:
- A vfx_config with appropriate colors and effects for the genre/mood
- An animation_script with 2-6 scenes, each having background, atmosphere, camera movements, visual effects, and narration text

Scene type guidelines:
- "establishing": slow camera, atmospheric, sets the mood (beginning)
- "action": fast camera, screen shake, particles (conflict/battle)
- "tension": slow zoom, dark atmosphere, subtle effects (buildup)
- "climax": intense effects, camera shake, energy bursts (peak moment)
- "resolution": slow zoom out, mist/fade, calm (ending)

Genre aesthetic guidelines:
- Paleo/Ancient: earth tones (#8B4513, #CD853F), dust, sepia, slow
- Dark Fantasy: deep purples (#4a1942), fire_embers, grain, dramatic
- Sci-fi: neon (#00f3ff, #ff00ff), glitch, aurora, fast
- War/Conflict: reds (#8b0000), screen_shake, flash, intense

Return pure JSON matching the schema. No explanation text."""),
    ("human", """Simulation parameters:
- Entropy: {entropy}
- Reality Distortion: {distortion}
- Genre: {genre}
- Dramatic Arc: {dramatic_arc}

Chronicle content to visualize:
{chronicle_content}""")
])


@agent_node("vfx_director")
async def vfx_director_agent(state: NarrativeState, config: Dict[str, Any] = None) -> NarrativeState:
    log.info("agent.run", agent="vfx_director")

    # Read inputs from state
    entropy = state.get("event_scores", {}).get("total_entropy", 0.5)
    distortion = state.get("singularity", {}).get("distortion", 0.0) if isinstance(state.get("singularity"), dict) else 0.0
    genre = state.get("genre", "generic")
    chronicle_content = state.get("final_prose", "")
    dramatic_arc = json.dumps(state.get("dramatic_arc", {}))

    # Dynamic LLM routing
    llm = get_llm_for_agent(
        "vfx_director",
        world_id=state.get("world_id"),
        current_tick=state.get("tick_end"),
        ai_runtime=state.get("ai_runtime"),
    )

    parser = JsonOutputParser(pydantic_object=VFXDirectorOutput)
    chain = vfx_director_prompt | llm | parser

    try:
        result = await chain.ainvoke({
            "entropy": str(entropy),
            "distortion": str(distortion),
            "genre": genre,
            "chronicle_content": chronicle_content[:3000],  # Limit to avoid token overflow
            "dramatic_arc": dramatic_arc,
        })
        vfx_config = result.get("vfx_config", {})
        animation_script = result.get("animation_script", None)
    except Exception as e:
        log.debug("agent.detail", agent="vfx_director", stage="vfx_error", exc=str(e))
        vfx_config = {
            "primary_color": "#8b5cf6",
            "distortion": 0.4,
            "particle_density": 80,
            "atmosphere_filter": "none"
        }
        animation_script = None

    # Record cross-universe whisper if event is significant
    record_universe_whisper(state)

    return {
        "vfx_config": vfx_config,
        "animation_script": animation_script
    }
