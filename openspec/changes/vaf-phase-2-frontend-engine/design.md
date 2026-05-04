## Context

VAF Phase 1 da hoan thanh: 7 Pydantic models trong narrative-loom/schemas.py, VFX Director agent san xuat animation_script, backend co migration + chronicle detail API (GET /api/worldos/chronicles/{id} tra ve animation_script JSON). Frontend hien co ChronicleList component hien thi danh sach chronicles voi expand/collapse nhung khong co animation playback.

Tech stack frontend: Next.js 16 (App Router), React 19, Framer Motion 12, TanStack Query 5, Tailwind CSS 4, Canvas 2D native, Three.js (co san nhung khong can cho VAF).

## Goals / Non-Goals

**Goals:**
- Render animation_script thanh cinematic experience (background + atmosphere + camera + particles + narration overlay + scene transitions)
- Fullscreen cinematic page tai /narrative-cinema/[chronicleId]
- Playback controls: play, pause, seek, progress bar, scene indicator
- Graceful degradation khi animation_script null hoac invalid
- Smooth scene transitions (fade, dissolve, wipe, cut)
- Responsive: desktop-first, mobile-friendly controls

**Non-Goals:**
- Audio/sound effects (no audio API in Phase 2 — text-only narration)
- 3D rendering (CSS + Canvas 2D only — Three.js overkill cho 2D scenes)
- Real-time editing/authoring of animation scripts
- Server-side rendering of animations (client-only)
- Offline support / service worker caching

## Decisions

### D1: Rendering Architecture — Layered Compositor
**Decision:** Moi scene la stack cua 5 independent layers (bottom-to-top): Background (CSS gradient/solid), Atmosphere (CSS filter overlay), Particles (Canvas 2D), Camera transform (CSS wrapper), Narration (text overlay). SceneCompositor stacks them voi absolute positioning. Transitions giua scenes dung Framer Motion AnimatePresence.

**Why:** Tach layers cho phep render doc lap — particle Canvas khong anh huong background CSS. CSS transforms cho camera nhe hon 3D transforms. Framer Motion da co san trong project, khong can them dependency.

### D2: Timeline State Management — useReducer + requestAnimationFrame
**Decision:** Timeline state dung useReducer (play/pause/seek/tick actions). Animation loop dung requestAnimationFrame voi elapsed time tracking. Hook useVAFPlayer expose: state (playing, currentScene, elapsedMs, progress), controls (play, pause, seek, restart).

**Why:** useReducer cho predictable state transitions. rAF cho 60fps smooth animation. Khong can external state lib — scope contained trong player component.

### D3: Particle System — Canvas 2D with Object Pool
**Decision:** ParticleRenderer dung single Canvas element. Object pool pre-allocate particles, reuse khi lifecycle het. Particle types: rising embers, falling snow, horizontal drift, radial burst. Max 200 particles per scene de giu 60fps.

**Why:** Canvas 2D du manh cho 200 particles. Object pool tranh GC pressure. Khong can WebGL — 2D particles chi can position + alpha + size.

### D4: Script Parser — Validate + Fill Defaults
**Decision:** Parser validate animation_script structure (kiểu, bounds, required fields). Missing optional fields duoc fill voi sensible defaults (atmosphere: filter "none", camera: "static", effects: []). Invalid scripts return null + warning — player hien fallback UI (static chronicle text).

**Why:** LLM-generated scripts co the co loi hoac missing fields. Defensive parsing tranh runtime crashes. Fallback dam bao user van doc duoc chronicle.

### D5: Page URL — /narrative-cinema/[chronicleId]
**Decision:** Dedicated route, khong nested trong /dashboard. Fullscreen layout (khong DashboardShell). Back button quay ve chronicle list.

**Why:** Cinematic experience can fullscreen, khong share layout voi dashboard tabs. Separate route cho deep-linking va shareable URLs.

## Risks / Trade-offs

- **[Risk] LLM-generated scripts co structure unexpected** -> Mitigation: parser validate + fill defaults, fallback UI
- **[Risk] Canvas particles impact performance tren mobile** -> Mitigation: cap 200 particles, detect low-end devices via navigator.hardwareConcurrency, reduce to 50
- **[Risk] Scene transitions janky voi nhieu effects** -> Mitigation: Framer Motion layout animations, will-change CSS hints
- **[Trade-off] CSS transforms cho camera thay vi real 3D** -> Accept: du cho zoom/pan/shake, khong can perspective
- **[Trade-off] No audio** -> Accept: Phase 3 concern, text narration du cho MVP
