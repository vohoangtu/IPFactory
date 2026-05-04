FROM rust:latest AS chef
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates pkg-config protobuf-compiler \
    && rm -rf /var/lib/apt/lists/*
RUN cargo install cargo-chef
WORKDIR /app

# ── Stage 1: Planner ──────────────────────────────────────────────────────
FROM chef AS planner
COPY . .
RUN cargo chef prepare --recipe-path recipe.json

# ── Stage 2: Cacher (dependencies only, rarely changes) ───────────────────
FROM chef AS cacher
COPY --from=planner /app/recipe.json recipe.json
RUN cargo chef cook --release --recipe-path recipe.json

# ── Stage 3: Builder (only our code, deps from cache) ─────────────────────
FROM chef AS builder
COPY --from=cacher /app /app
COPY . .
RUN cargo build --release -p worldos-grpc --bin worldos-engine

# ── Stage 4: Runtime (minimal image) ─────────────────────────────────────
FROM debian:bookworm-slim AS runner
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /app
COPY --from=builder /app/target/release/worldos-engine .
EXPOSE 50051
EXPOSE 50052
ENV GRPC_ADDR=0.0.0.0:50051
ENV HTTP_ADDR=0.0.0.0:50052
CMD ["./worldos-engine"]
