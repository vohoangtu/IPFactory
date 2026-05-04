from core.agent_wrapper import agent_node
from core.logging import get_logger
from typing import Any, Dict, List, Optional
from pydantic import BaseModel
from utils.llm_factory import get_llm
from langchain_core.prompts import ChatPromptTemplate

log = get_logger(__name__)

# ── Pydantic schemas ──────────────────────────────────────────────────────────

class UniverseContextIn(BaseModel):
    entropy: float = 0.0
    stability_index: float = 1.0
    myth_intensity: float = 0.0
    tick: int = 0

class ActorIntentRequest(BaseModel):
    model_config = {"protected_namespaces": ()}  # Fix pydantic warning for model_name field

    actor_id: int
    actor_name: str
    archetype: str
    traits: Dict[str, float]
    universe_context: UniverseContextIn
    world_era: str | None = "genesis"
    recent_biography: str = ""
    available_actions: List[str] = [
        "revolt", "form_contract", "migrate",
        "trade", "suppress_revolt", "propagate_myth"
    ]
    # Model config — defaults to local Ollama
    provider: str = "local"
    model_name: str = ""

class ActorIntentResponse(BaseModel):
    action: str
    intensity: float = 0.5
    target: Optional[str] = None
    reasoning: str
    confidence: float = 0.8

# ── Prompt ────────────────────────────────────────────────────────────────────

INTENT_SYSTEM = """Ngươi là tâm trí bên trong của một actor trong thế giới mô phỏng.
Nhiệm vụ: đọc trạng thái nội tâm và bối cảnh thế giới, sau đó quyết định hành động tiếp theo.
Chỉ trả về JSON thuần túy, không giải thích thêm."""

INTENT_HUMAN = """Actor: {actor_name}
Vai trò/Hệ tư tưởng: {archetype}
Kỷ nguyên hiện tại: {world_era}

Trait vector (thang 0-1):
{traits_formatted}

Bối cảnh thế giới tại tick {tick}:
- Entropy: {entropy} {entropy_label}
- Ổn định: {stability}
- Cường độ huyền thoại: {myth}

Tiểu sử và bối cảnh xã hội gần đây:
{biography}

Hành động có thể chọn:
{actions_formatted}

Quyết định hành động tiếp theo. Hãy đảm bảo:
1. Hành động và lý do (reasoning) phải TUÂN THỦ TUYỆT ĐỐI bối cảnh văn minh của Kỷ nguyên {world_era}.
2. Nếu là thời Tiền sử (Paleolithic), hãy dùng ngôn ngữ bản năng, tâm linh, bộ lạc.
3. Nếu là thời Trung cổ (Medieval), hãy dùng ngôn ngữ danh dự, đức tin, giai cấp.
4. Nếu là thời Cyberpunk, hãy dùng ngôn ngữ kỹ thuật số, thực dụng, tập đoàn.

Trả về JSON theo format chính xác này:
{{
  "action": "<một trong các hành động ở trên>",
  "intensity": <số thập phân 0.0-1.0, mức độ mạnh của hành động>,
  "target": "<tên actor mục tiêu nếu cần hoặc null>",
  "reasoning": "<1 câu tiếng Việt giải thích lý do hành động — phải khớp với vai trò {archetype} trong thời đại {world_era}>",
  "confidence": <số thập phân 0.0-1.0, mức độ chắc chắn của quyết định>
}}"""

_prompt = ChatPromptTemplate.from_messages([
    ("system", INTENT_SYSTEM),
    ("human", INTENT_HUMAN),
])

# ── Agent function ────────────────────────────────────────────────────────────

def _entropy_label(entropy: float) -> str:
    if entropy > 0.8: return "🔴 (khủng hoảng)"
    if entropy > 0.6: return "🟡 (bất ổn)"
    if entropy > 0.4: return "🟠 (căng thẳng)"
    return "🟢 (ổn định)"

@agent_node("intent_agent")

async def intent_agent(req: ActorIntentRequest) -> ActorIntentResponse:
    """Single-agent, no LangGraph pipeline needed — lightweight for real-time simulation."""
    import json, re, random, os

    traits_lines = "\n".join(
        f"  {k}: {v:.2f}" for k, v in sorted(req.traits.items(), key=lambda x: -x[1])
    )
    actions_formatted = "\n".join(f"  - {a}" for a in req.available_actions)
    ctx = req.universe_context

    # Build prompt values
    prompt_values = {
        "world_era": req.world_era or "genesis",
        "actor_name": req.actor_name,
        "archetype": req.archetype,
        "traits_formatted": traits_lines,
        "tick": ctx.tick,
        "entropy": f"{ctx.entropy:.2f}",
        "entropy_label": _entropy_label(ctx.entropy),
        "stability": f"{ctx.stability_index:.2f}",
        "myth": f"{ctx.myth_intensity:.2f}",
        "biography": req.recent_biography or "(chưa có tiểu sử)",
        "actions_formatted": actions_formatted,
    }

    # Use real LLM by default, but allow mock via env var
    use_mock = os.getenv("USE_MOCK_LLM", "false").lower() == "true"
    log.debug("agent.detail", agent="intent_agent", provider=req.provider, model=req.model_name, use_mock=use_mock)

    if use_mock:
        # Mock response for local/unconfigured environments
        action = random.choice(req.available_actions)
        raw = json.dumps({
            "action": action,
            "intensity": 0.85,
            "reasoning": f"Dùng ý chí sắt đá để thực thực thi {action} trong bối cảnh entropy {ctx.entropy:.2f}.",
            "confidence": 0.99
        })
    else:
        try:
            import asyncio
            llm = get_llm(
                provider=req.provider,
                model_name=req.model_name,
            )
            chain = _prompt | llm
            result = await asyncio.to_thread(chain.invoke, prompt_values)
            raw = result.content if hasattr(result, "content") else str(result)
        except Exception as e:
            import traceback
            traceback.print_exc()
            # Fallback on mock if LLM fails
            action = random.choice(req.available_actions)
            raw = json.dumps({
                "action": action,
                "intensity": 0.5,
                "reasoning": f"Fallback error: {repr(e)}",
                "confidence": 0.5
            })

    # Parse JSON — strip markdown fences if present
    raw = re.sub(r"```json|```", "", raw).strip()
    try:
        parsed = json.loads(raw)
    except json.JSONDecodeError:
        # Fallback: extract first JSON object
        match = re.search(r'\{.*\}', raw, re.DOTALL)
        parsed = json.loads(match.group()) if match else {}

    # Validate action is in available list
    chosen = parsed.get("action", req.available_actions[0])
    if chosen not in req.available_actions:
        chosen = req.available_actions[0]

    return ActorIntentResponse(
        action=chosen,
        intensity=float(parsed.get("intensity", 0.5)),
        target=parsed.get("target") or None,
        reasoning=parsed.get("reasoning", "Hành động vì bản năng sinh tồn."),
        confidence=float(parsed.get("confidence", 0.7)),
    )


