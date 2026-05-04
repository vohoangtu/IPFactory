# syntax=docker/dockerfile:1

# Stage 1: Install dependencies with persistent npm cache
FROM node:20-bookworm-slim AS deps
WORKDIR /app
COPY package.json package-lock.json* ./
ENV npm_config_loglevel=warn \
    npm_config_audit=false \
    npm_config_fund=false \
    npm_config_update_notifier=false
RUN --mount=type=cache,target=/root/.npm \
    npm config set fetch-retries 5 && \
    npm config set fetch-retry-mintimeout 20000 && \
    (if [ -f package-lock.json ]; then npm ci; else npm install; fi)

# Stage 2: Build
FROM node:20-bookworm-slim AS builder
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY package.json package-lock.json* ./
COPY . .
ENV NEXT_TELEMETRY_DISABLED=1 \
    NODE_ENV=production
RUN npm run build

# Stage 3: Minimal standalone runner
FROM node:20-bookworm-slim AS runner
WORKDIR /app

ENV NODE_ENV=production \
    NEXT_TELEMETRY_DISABLED=1 \
    PORT=3000 \
    HOSTNAME="0.0.0.0"

RUN groupadd --system --gid 1001 nodejs && \
    useradd --system --uid 1001 --gid 1001 --create-home nextjs

COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static
COPY --from=builder --chown=nextjs:nodejs /app/public ./public

USER nextjs
EXPOSE 3000
CMD ["node", "server.js"]
