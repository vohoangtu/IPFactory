"""
Error taxonomy for Narrative Loom.

Separates transient (retryable) from permanent (fail-fast) errors.
"""


class NarrativeLoomError(Exception):
    """Base exception for all Narrative Loom errors."""


class TransientLLMError(NarrativeLoomError):
    """Retryable LLM errors: timeout, rate limit, 5xx, connection reset."""


class PermanentLLMError(NarrativeLoomError):
    """Non-retryable: invalid API key, model not found, content policy violation."""


class PipelineError(NarrativeLoomError):
    """Pipeline-level failure (e.g., graph compilation error)."""
