"""
Structured logging setup for Narrative Loom.

Uses structlog for consistent, machine-readable JSON logs in production
and human-readable colored output in development.
"""
import logging
import os
import sys
import warnings

import structlog

# Suppress benign Pydantic v2 serialization warnings from LangChain structured output
warnings.filterwarnings(
    "ignore",
    message="Pydantic serializer warnings",
    category=UserWarning,
)
warnings.filterwarnings(
    "ignore",
    message="Expected `none`",
    category=UserWarning,
)

_ENV = os.getenv("APP_ENV", "development")
_LOG_LEVEL = os.getenv("LOG_LEVEL", "INFO").upper()


def configure_logging() -> None:
    """Call once at application startup."""
    shared_processors: list = [
        structlog.contextvars.merge_contextvars,
        structlog.processors.add_log_level,
        structlog.processors.TimeStamper(fmt="iso", utc=True),
        structlog.stdlib.PositionalArgumentsFormatter(),
        structlog.processors.StackInfoRenderer(),
    ]

    if _ENV == "production":
        # JSON output for log aggregators (Loki, Datadog, etc.)
        processors = shared_processors + [
            structlog.processors.ExceptionRenderer(),
            structlog.processors.JSONRenderer(),
        ]
    else:
        # Human-friendly colored output for local dev
        processors = shared_processors + [
            structlog.dev.ConsoleRenderer(colors=True),
        ]

    structlog.configure(
        processors=processors,
        wrapper_class=structlog.make_filtering_bound_logger(
            getattr(logging, _LOG_LEVEL, logging.INFO)
        ),
        context_class=dict,
        logger_factory=structlog.PrintLoggerFactory(file=sys.stderr),
        cache_logger_on_first_use=True,
    )


# Auto-configure on import
configure_logging()

# Public logger factory — use in every module:
#   from core.logging import get_logger
#   log = get_logger(__name__)
def get_logger(name: str | None = None) -> structlog.stdlib.BoundLogger:
    return structlog.get_logger(name)
