import os
import sys

# Windows Unicode fix
if sys.platform == 'win32':
    os.environ.setdefault('PYTHONIOENCODING', 'utf-8')
    if hasattr(sys.stdout, 'reconfigure'):
        sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    if hasattr(sys.stderr, 'reconfigure'):
        sys.stderr.reconfigure(encoding='utf-8', errors='replace')

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
import uvicorn

from app.api.swarm_routes import router as swarm_router
from app.config import Config

def create_app() -> FastAPI:
    # Validate Core Dependencies (LLM, Zep)
    errors = Config.validate()
    if errors:
        print("!!! CONFIGURATION WARNING !!!")
        for err in errors:
            print(f"  - {err}")
        print("  Service starting anyway, but specific functions WILL fail.")

    app = FastAPI(
        title="WorldOS Social Engine",
        description="Multi-era crowd simulation microservice powered by MiroFish Core.",
        version="1.0.0"
    )

    # Allow CORS for observer portal if needed
    app.add_middleware(
        CORSMiddleware,
        allow_origins=os.environ.get("CORS_ORIGINS", "http://localhost").split(","),
        allow_credentials=False,
        allow_methods=["GET", "POST"],
        allow_headers=["*"],
    )

    # Mount routers
    app.include_router(swarm_router, prefix="/api/v1")

    @app.get("/health")
    async def health_check():
        return {"status": "healthy", "version": "1.0.0"}

    @app.get("/api/v1/health")
    async def api_v1_health_check():
        return {"status": "healthy", "version": "1.0.0"}

    return app

app = create_app()

if __name__ == "__main__":
    host = os.environ.get('HOST', '0.0.0.0')
    port = int(os.environ.get('PORT', 5001))
    uvicorn.run("main:app", host=host, port=port, reload=Config.DEBUG)
