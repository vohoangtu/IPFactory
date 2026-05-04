"""
Celery application singleton for Narrative Loom.

Broker / backend both use Redis (database index 1 to keep separate from
the main WorldOS Redis cache on index 0).
"""
import os

from celery import Celery

BROKER_URL = os.getenv("CELERY_BROKER_URL", "redis://redis:6379/1")
RESULT_BACKEND = os.getenv("CELERY_RESULT_BACKEND", "redis://redis:6379/1")

celery_app = Celery(
    "narrative_loom",
    broker=BROKER_URL,
    backend=RESULT_BACKEND,
    include=["tasks.chronicle_task"],
)

celery_app.conf.update(
    # Route all narrative tasks to their own queue
    task_routes={"tasks.*": {"queue": "narrative"}},
    # Serialisation
    task_serializer="json",
    result_serializer="json",
    accept_content=["json"],
    # Keep results for 1 hour (enough for frontend to poll)
    result_expires=3600,
    # Visibility timeout: slightly above max expected pipeline duration
    broker_transport_options={"visibility_timeout": 10800},  # 3h
    # Worker settings
    worker_prefetch_multiplier=1,       # one task at a time per worker
    task_acks_late=True,                # ack after completion, not on pickup
    task_reject_on_worker_lost=True,    # re-queue on sudden worker death
    # Timezone
    timezone="UTC",
    enable_utc=True,
)
