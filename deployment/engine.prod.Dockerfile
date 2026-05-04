# ── Stage 1: Chef (Prepare recipe) ────────────────────────────────────────
FROM lukemathwalker/cargo-chef:latest-rust-slim-bookworm AS chef
WORKDIR /app
RUN apt-get update && apt-get install -y --no-install-recommends \
    pkg-config protobuf-compiler libssl-dev perl make g++ \
    && rm -rf /var/lib/apt/lists/*

# ── Stage 2: Planner ──────────────────────────────────────────────────────
FROM chef AS planner
# Copy everything in engine (assuming context is root or we copy from root/engine)
COPY engine/ .
RUN cargo chef prepare --recipe-path recipe.json

# ── Stage 3: Builder (dependencies + source) ──────────────────────────────
FROM chef AS builder
COPY --from=planner /app/recipe.json recipe.json
# Build dependencies - this layer is cached as long as recipe.json doesn't change
RUN --mount=type=cache,target=/usr/local/cargo/registry \
    --mount=type=cache,target=/app/target \
    cargo chef cook --release --recipe-path recipe.json

# Build actual source
COPY engine/ .
RUN --mount=type=cache,target=/usr/local/cargo/registry \
    --mount=type=cache,target=/app/target \
    cargo build --release -p worldos-grpc --bin worldos-engine && \
    cp target/release/worldos-engine /app/worldos-engine

# ── Stage 4: Runtime ──────────────────────────────────────────────────────
FROM debian:bookworm-slim
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates libssl3 \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /app
COPY --from=builder /app/worldos-engine .
RUN useradd -r -s /bin/false engine && chown engine:engine /app/worldos-engine
USER engine
EXPOSE 50051 50052
ENV GRPC_ADDR=0.0.0.0:50051
ENV HTTP_ADDR=0.0.0.0:50052
CMD ["./worldos-engine"]
